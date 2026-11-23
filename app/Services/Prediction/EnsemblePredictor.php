<?php

declare(strict_types=1);

namespace App\Services\Prediction;

/**
 * Class EnsemblePredictor
 * 
 * 5-Model Multi-Algorithm Ensemble Engine.
 * 
 * Aggregates predictions from:
 *   1. Rule-Based Baseline (Deterministic Physics)
 *   2. Decision Tree (CART)
 *   3. Random Forest (Bagging)
 *   4. Support Vector Machine (RBF Kernel)
 *   5. Logistic Regression (Multinomial Softmax)
 * 
 * Consensus Rules:
 *   - Majority Voting: Class receiving >= 3 of 5 votes wins outright.
 *   - Tie-Breaking Strategy: In the event of a tie (e.g., 2-2-1 or 2-2),
 *     the final class is decided by the model exhibiting the highest confidence
 *     among the tied categories.
 *   - Comprehensive Audit Trail: Preserves individual model outputs, confidence scores,
 *     vote tallies, and agreement rates for every prediction.
 */
class EnsemblePredictor implements PredictorInterface
{
    /** @var array<string, PredictorInterface> */
    protected array $models;

    protected array $classes = ['LOW', 'MEDIUM', 'HIGH'];

    /**
     * @param array<string, PredictorInterface>|null $models
     */
    public function __construct(?array $models = null)
    {
        $this->models = $models ?? [
            'rule_based'          => new RuleBasedPredictor(),
            'decision_tree'       => new DecisionTreePredictor(),
            'random_forest'       => new RandomForestPredictor(),
            'svm'                 => new SvmPredictor(),
            'logistic_regression' => new LogisticRegressionPredictor(),
        ];
    }

    public function getKey(): string
    {
        return 'ensemble';
    }

    public function getName(): string
    {
        return '5-Model Consensus Ensemble';
    }

    /**
     * Get the individual model instances.
     *
     * @return array<string, PredictorInterface>
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * Fit all underlying statistical and machine learning models.
     */
    public function fit(array $samples, array $labels): void
    {
        foreach ($this->models as $model) {
            $model->fit($samples, $labels);
        }
    }

    public function predict(array $sample): string
    {
        return $this->predictDetailed($sample)['final_urgency'];
    }

    public function predictProba(array $sample): array
    {
        $res = $this->predictDetailed($sample);

        return [
            'prediction'    => $res['final_urgency'],
            'confidence'    => $res['confidence'],
            'probabilities' => $res['probabilities'],
        ];
    }

    /**
     * Full research-grade inference evaluation across all 5 models.
     *
     * @param array<string, mixed> $sample
     * @return array{
     *     final_urgency: string,
     *     consensus_label: string,
     *     confidence: float,
     *     agreement_rate: string,
     *     agreement_fraction: string,
     *     votes: array<string, int>,
     *     tie_broken: bool,
     *     tie_breaking_model: ?string,
     *     probabilities: array<string, float>,
     *     model_predictions: array<string, array>
     * }
     */
    public function predictDetailed(array $sample): array
    {
        $predictions = [];
        $votes = array_fill_keys($this->classes, 0);
        $probSums = array_fill_keys($this->classes, 0.0);

        // 1. Execute inference on each of the 5 models
        foreach ($this->models as $key => $model) {
            $out = $model->predictProba($sample);
            $pred = strtoupper((string) ($out['prediction'] ?? 'LOW'));
            $conf = (float) ($out['confidence'] ?? 50.0);
            $probs = (array) ($out['probabilities'] ?? []);

            $predictions[$key] = [
                'model_key'     => $key,
                'model_name'    => $model->getName(),
                'prediction'    => $pred,
                'confidence'    => round($conf, 1),
                'probabilities' => $probs,
            ];

            if (isset($votes[$pred])) {
                $votes[$pred]++;
            }

            foreach ($this->classes as $cls) {
                $probSums[$cls] += ($probs[$cls] ?? 0.0);
            }
        }

        $totalModels = count($this->models);

        // 2. Average ensemble probabilities
        $avgProbs = [];
        foreach ($this->classes as $cls) {
            $avgProbs[$cls] = round($probSums[$cls] / max(1, $totalModels), 1);
        }

        // 3. Majority vote determination
        $maxVotes = max($votes);
        $topClasses = [];
        foreach ($votes as $cls => $vCount) {
            if ($vCount === $maxVotes) {
                $topClasses[] = $cls;
            }
        }

        $tieBroken = false;
        $tieBreakingModel = null;
        $finalClass = 'LOW';
        $finalConfidence = 50.0;

        if (count($topClasses) === 1) {
            // Unambiguous winner
            $finalClass = $topClasses[0];
            $agreementRate = "{$maxVotes}/{$totalModels}";

            // Confidence is average confidence of models voting for the winner
            $winningConfs = [];
            foreach ($predictions as $p) {
                if ($p['prediction'] === $finalClass) {
                    $winningConfs[] = $p['confidence'];
                }
            }
            $finalConfidence = ! empty($winningConfs)
                ? round(array_sum($winningConfs) / count($winningConfs), 1)
                : $avgProbs[$finalClass];
        } else {
            // Tie condition (e.g. 2 vs 2): resolve by selecting class with the highest-confidence individual model
            $tieBroken = true;
            $bestCandidateConf = -1.0;
            $bestCandidateClass = $topClasses[0];

            foreach ($predictions as $key => $p) {
                if (in_array($p['prediction'], $topClasses, true)) {
                    if ($p['confidence'] > $bestCandidateConf) {
                        $bestCandidateConf = $p['confidence'];
                        $bestCandidateClass = $p['prediction'];
                        $tieBreakingModel = $p['model_name'];
                    }
                }
            }

            $finalClass = $bestCandidateClass;
            $finalConfidence = $bestCandidateConf;
            $agreementRate = "{$maxVotes}/{$totalModels} (Tie-broken by {$tieBreakingModel})";
        }

        return [
            'final_urgency'      => $finalClass,
            'consensus_label'    => $finalClass,
            'confidence'         => min(99.0, max(50.0, $finalConfidence)),
            'agreement_rate'     => $agreementRate,
            'agreement_fraction' => "{$maxVotes}/{$totalModels}",
            'votes'              => $votes,
            'tie_broken'         => $tieBroken,
            'tie_breaking_model' => $tieBreakingModel,
            'probabilities'      => $avgProbs,
            'model_predictions'  => $predictions,
        ];
    }
}
