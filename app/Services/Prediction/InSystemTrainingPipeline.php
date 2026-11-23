<?php

declare(strict_types=1);

namespace App\Services\Prediction;

use App\Models\MlDataset;
use App\Models\MlExperiment;
use App\Models\MlModelMetric;
use App\Models\MlPredictionComparison;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Class InSystemTrainingPipeline
 * 
 * In-system training, testing, cross-validation, and metrics evaluation orchestrator.
 * Executes purely in PHP without external Python or PECL dependencies.
 */
class InSystemTrainingPipeline
{
    protected DataPreprocessingService $preprocessor;
    protected EvaluationMetricsService $metricsEvaluator;
    protected DatasetImportService $importService;

    public function __construct(
        ?DataPreprocessingService $preprocessor = null,
        ?EvaluationMetricsService $metricsEvaluator = null,
        ?DatasetImportService $importService = null
    ) {
        $this->preprocessor = $preprocessor ?? new DataPreprocessingService();
        $this->metricsEvaluator = $metricsEvaluator ?? new EvaluationMetricsService();
        $this->importService = $importService ?? new DatasetImportService($this->preprocessor);
    }

    /**
     * Run full in-system experiment: load -> preprocess -> split -> train -> test -> persist.
     *
     * @param MlDataset $dataset
     * @param array<int, string> $algorithms
     * @param float $splitRatio
     * @param int $seed
     * @param array<string, mixed> $hyperparams
     * @param int|null $userId
     * @return MlExperiment
     */
    public function run(
        MlDataset $dataset,
        array $algorithms,
        float $splitRatio = 0.80,
        int $seed = 42,
        array $hyperparams = [],
        ?int $userId = null
    ): MlExperiment {
        $fullPath = $dataset->absolute_path;
        if (! file_exists($fullPath)) {
            throw new InvalidArgumentException("Dataset file does not exist at [{$fullPath}].");
        }

        // 1. Read raw rows from file
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $fileData = ($ext === 'csv')
            ? $this->importService->readCsv($fullPath, null)
            : $this->importService->readXlsx($fullPath, null);

        $rawRows = $fileData['rows'];
        if (empty($rawRows)) {
            throw new InvalidArgumentException('Dataset file is empty.');
        }

        // Apply column mapping if dataset was uploaded with mapping
        if (! empty($dataset->feature_mapping_json) && is_array($dataset->feature_mapping_json)) {
            $mapping = $dataset->feature_mapping_json['mapping'] ?? $dataset->feature_mapping_json;
            if (is_array($mapping)) {
                $mappedRows = [];
                foreach ($rawRows as $r) {
                    $mapped = $r;
                    foreach ($mapping as $targetKey => $srcCol) {
                        // Skip non-string column names or metadata entries (e.g. 'features' => [...], 'noise' => 0.08)
                        if (! is_string($srcCol)) {
                            continue;
                        }
                        if (is_array($r) && isset($r[$srcCol])) {
                            $mapped[$targetKey] = $r[$srcCol];
                        }
                    }
                    $mappedRows[] = $mapped;
                }
                $rawRows = $mappedRows;
            }
        }

        // 2. Preprocess dataset (Imputation + IQR Outlier Clipping)
        $prepResult = $this->preprocessor->preprocess($rawRows, [
            'impute'        => true,
            'clip_outliers' => true,
            'scale'         => false,
        ]);
        $rows = $prepResult['clean_data'];
        $n = count($rows);

        // Ensure each row has ground truth label (if missing in unlabeled data, rule-based assigns it)
        $ruleBasedFallback = new RuleBasedPredictor();
        foreach ($rows as $i => $r) {
            $lbl = $r['urgency_label'] ?? null;
            if ($lbl === null || ! in_array(strtoupper(trim((string) $lbl)), ['LOW', 'MEDIUM', 'HIGH'], true)) {
                $rows[$i]['urgency_label'] = $ruleBasedFallback->predict($r);
            } else {
                $rows[$i]['urgency_label'] = strtoupper(trim((string) $lbl));
            }
        }

        // 3. Deterministic train/test split via seed
        mt_srand($seed);
        $indices = range(0, $n - 1);
        shuffle($indices);

        $trainCount = max(2, (int) round($n * $splitRatio));
        $trainIndices = array_slice($indices, 0, $trainCount);
        $testIndices = array_slice($indices, $trainCount);

        if (empty($testIndices)) {
            $testIndices = $trainIndices; // fallback if tiny dataset
        }

        $trainSamples = [];
        $trainLabels = [];
        foreach ($trainIndices as $idx) {
            $trainSamples[] = $rows[$idx];
            $trainLabels[] = $rows[$idx]['urgency_label'];
        }

        $testSamples = [];
        $testLabels = [];
        foreach ($testIndices as $idx) {
            $testSamples[] = $rows[$idx];
            $testLabels[] = $rows[$idx]['urgency_label'];
        }

        // 4. Instantiate and fit selected algorithms
        $instantiatedModels = [];
        foreach ($algorithms as $algoKey) {
            $instantiatedModels[$algoKey] = match ($algoKey) {
                'rule_based'          => new RuleBasedPredictor(),
                'decision_tree'       => new DecisionTreePredictor(
                    maxDepth: isset($hyperparams['dt_max_depth']) ? (int) $hyperparams['dt_max_depth'] : null
                ),
                'random_forest'       => new RandomForestPredictor(
                    nEstimators: isset($hyperparams['rf_n_estimators']) ? (int) $hyperparams['rf_n_estimators'] : null,
                    maxDepth: isset($hyperparams['rf_max_depth']) ? (int) $hyperparams['rf_max_depth'] : null
                ),
                'svm'                 => new SvmPredictor(
                    c: isset($hyperparams['svm_c']) ? (float) $hyperparams['svm_c'] : null,
                    gamma: isset($hyperparams['svm_gamma']) && is_numeric($hyperparams['svm_gamma']) ? (float) $hyperparams['svm_gamma'] : null
                ),
                'logistic_regression' => new LogisticRegressionPredictor(
                    learningRate: 0.05,
                    maxIterations: isset($hyperparams['lr_max_iter']) ? (int) $hyperparams['lr_max_iter'] : 200,
                    l2Penalty: isset($hyperparams['lr_c']) ? (1.0 / max(0.01, (float) $hyperparams['lr_c'])) * 0.01 : 0.01
                ),
                default               => null,
            };
        }
        $instantiatedModels = array_filter($instantiatedModels);

        foreach ($instantiatedModels as $key => $model) {
            $model->fit($trainSamples, $trainLabels);
        }

        // 5. Test set inference and performance metrics calculation
        $modelsSummary = [];
        $testPredictionsPerModel = [];

        foreach ($instantiatedModels as $algoKey => $model) {
            $predLabels = [];
            $predConfidences = [];
            $predProbas = [];

            foreach ($testSamples as $sample) {
                $out = $model->predictProba($sample);
                $predLabels[] = $out['prediction'];
                $predConfidences[] = $out['confidence'];
                $predProbas[] = $out['probabilities'];
            }

            $testPredictionsPerModel[$algoKey] = [
                'labels'      => $predLabels,
                'confidences' => $predConfidences,
                'probas'      => $predProbas,
            ];

            $metrics = $this->metricsEvaluator->calculate($testLabels, $predLabels, $testSamples);
            $modelsSummary[$algoKey] = array_merge($metrics, [
                'model_name' => $model->getName(),
            ]);
        }

        // 6. 5-Model Ensemble Consensus on test set
        $ensemble = new EnsemblePredictor($instantiatedModels);
        $ensemblePredLabels = [];
        $ensembleDetailedRows = [];

        foreach ($testSamples as $i => $sample) {
            $ensRes = $ensemble->predictDetailed($sample);
            $ensemblePredLabels[] = $ensRes['final_urgency'];
            $ensembleDetailedRows[] = $ensRes;
        }

        $ensembleMetrics = $this->metricsEvaluator->calculate($testLabels, $ensemblePredLabels, $testSamples);
        $modelsSummary['ensemble'] = array_merge($ensembleMetrics, [
            'model_name' => '5-Model Ensemble Consensus',
        ]);

        // 7. Persist run to Database in a transaction
        return DB::transaction(function () use (
            $dataset,
            $splitRatio,
            $seed,
            $algorithms,
            $hyperparams,
            $userId,
            $rows,
            $testSamples,
            $testLabels,
            $modelsSummary,
            $testPredictionsPerModel,
            $ensembleDetailedRows,
            $prepResult
        ) {
            $runNumber = $dataset->experiments()->count() + 1;
            $experiment = MlExperiment::create([
                'dataset_id'       => $dataset->id,
                'run_name'         => "Run #{$runNumber} — {$dataset->name}",
                'split_ratio'      => $splitRatio,
                'cv_folds'         => 5,
                'status'           => 'completed',
                'summary_json'     => [
                    'algorithms_run' => $algorithms,
                    'row_count'      => count($rows),
                    'test_count'     => count($testSamples),
                    'has_labels'     => true,
                    'seed'           => $seed,
                    'models'         => $modelsSummary,
                    'preprocessing'  => $prepResult['report'],
                ],
                'hyperparams_json' => array_merge($hyperparams, ['seed' => $seed, 'algorithms' => $algorithms]),
                'run_by'           => $userId,
            ]);

            // Save per-model metrics
            foreach ($modelsSummary as $algo => $mData) {
                MlModelMetric::create([
                    'experiment_id'         => $experiment->id,
                    'algorithm'             => $algo,
                    'accuracy'              => $mData['accuracy'] ?? 0.0,
                    'precision_macro'       => $mData['precision_macro'] ?? 0.0,
                    'recall_macro'          => $mData['recall_macro'] ?? 0.0,
                    'f1_macro'              => $mData['f1_macro'] ?? 0.0,
                    'high_urgency_recall'   => $mData['high_urgency_recall'] ?? 0.0,
                    'confusion_matrix_json' => $mData['confusion_matrix'] ?? [],
                    'yearly_accuracy_json'  => $mData['yearly_accuracy'] ?? [],
                    'extra_meta_json'       => ['class_metrics' => $mData['class_metrics'] ?? []],
                ]);
            }

            // Save row-by-row prediction comparisons (chunked)
            $compRows = [];
            $now = now();

            foreach ($testSamples as $i => $sample) {
                $ens = $ensembleDetailedRows[$i];
                $plate = $sample['plate_number'] ?? ($sample['vehicle_id'] ?? sprintf('TEST-%04d', $i + 1));
                $svc = $sample['service_type'] ?? 'Scheduled Maintenance';
                $scanYear = isset($sample['scan_year']) ? (int) $sample['scan_year'] : null;
                $groundTruth = $testLabels[$i] ?? null;

                $rbOut = $testPredictionsPerModel['rule_based'] ?? null;
                $dtOut = $testPredictionsPerModel['decision_tree'] ?? null;
                $rfOut = $testPredictionsPerModel['random_forest'] ?? null;
                $svmOut = $testPredictionsPerModel['svm'] ?? null;
                $lrOut = $testPredictionsPerModel['logistic_regression'] ?? null;

                $compRows[] = [
                    'experiment_id'             => $experiment->id,
                    'vehicle_identifier'        => $plate,
                    'service_type'              => $svc,
                    'scan_year'                 => $scanYear,
                    'ground_truth_label'        => $groundTruth,
                    'rb_prediction'             => $rbOut['labels'][$i] ?? ($ens['model_predictions']['rule_based']['prediction'] ?? null),
                    'rb_confidence'             => $rbOut['confidences'][$i] ?? ($ens['model_predictions']['rule_based']['confidence'] ?? null),
                    'rb_days_remaining'         => $sample['days_since_service'] !== null ? max(0, 180 - (int) $sample['days_since_service']) : null,
                    'dt_prediction'             => $dtOut['labels'][$i] ?? ($ens['model_predictions']['decision_tree']['prediction'] ?? null),
                    'dt_confidence'             => $dtOut['confidences'][$i] ?? ($ens['model_predictions']['decision_tree']['confidence'] ?? null),
                    'rf_prediction'             => $rfOut['labels'][$i] ?? ($ens['model_predictions']['random_forest']['prediction'] ?? null),
                    'rf_confidence'             => $rfOut['confidences'][$i] ?? ($ens['model_predictions']['random_forest']['confidence'] ?? null),
                    'svm_prediction'            => $svmOut['labels'][$i] ?? ($ens['model_predictions']['svm']['prediction'] ?? null),
                    'svm_confidence'            => $svmOut['confidences'][$i] ?? ($ens['model_predictions']['svm']['confidence'] ?? null),
                    'lr_prediction'             => $lrOut['labels'][$i] ?? ($ens['model_predictions']['logistic_regression']['prediction'] ?? null),
                    'lr_confidence'             => $lrOut['confidences'][$i] ?? ($ens['model_predictions']['logistic_regression']['confidence'] ?? null),
                    'consensus_label'           => $ens['final_urgency'],
                    'agreement_rate'            => $ens['agreement_fraction'],
                    'features_json'             => json_encode($sample),
                    'reasoning_json'            => json_encode($ens),
                    'pushed_to_alerts'          => false,
                    'staff_verification_status' => 'pending',
                    'created_at'                => $now,
                    'updated_at'                => $now,
                ];
            }

            foreach (array_chunk($compRows, 100) as $chunk) {
                MlPredictionComparison::insert($chunk);
            }

            return $experiment;
        });
    }
}
