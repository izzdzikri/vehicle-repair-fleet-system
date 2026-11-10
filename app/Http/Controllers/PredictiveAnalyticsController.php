<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceAlert;
use App\Models\MlDataset;
use App\Models\MlExperiment;
use App\Models\MlModelMetric;
use App\Models\MlPredictionComparison;
use App\Models\ServiceHistory;
use App\Models\TripLog;
use App\Models\Vehicle;
use App\Models\JobType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PredictiveAnalyticsController extends Controller
{
    // ─────────────────────────── Main Dashboard View ──────────────────────────

    public function index()
    {
        $datasets   = MlDataset::with('uploader')->latest()->get();
        $experiments = MlExperiment::with(['dataset', 'runner', 'metrics'])
            ->latest()->limit(20)->get();

        $latestCompleted = MlExperiment::with(['metrics', 'dataset'])
            ->where('status', 'completed')
            ->latest()->first();

        $metrics = $latestCompleted
            ? $latestCompleted->metrics->keyBy('algorithm')
            : collect();

        $predictions = collect();
        if ($latestCompleted) {
            $predictions = MlPredictionComparison::where('experiment_id', $latestCompleted->id)
                ->latest()->paginate(50);
        }

        // Comparison datasets for the benchmark widget
        $syntheticExp = MlExperiment::whereHas('dataset', fn($q) => $q->where('source_type', 'synthetic'))
            ->where('status', 'completed')->with('metrics')->latest()->first();
        $kaggleExp = MlExperiment::whereHas('dataset', fn($q) => $q->where('source_type', 'kaggle_benchmark'))
            ->where('status', 'completed')->with('metrics')->latest()->first();

        // Staff verification audit
        $verificationStats = $this->getVerificationStats();

        return view('admin.predictive-analytics.index', compact(
            'datasets',
            'experiments',
            'latestCompleted',
            'metrics',
            'predictions',
            'syntheticExp',
            'kaggleExp',
            'verificationStats',
        ));
    }

    // ─────────────────────────── Dataset Upload ───────────────────────────────

    public function uploadDataset(Request $request)
    {
        $request->validate([
            'dataset_file'  => 'required|file|mimes:csv,xlsx,xls|max:20480',
            'dataset_name'  => 'required|string|max:100',
            'source_type'   => 'required|in:synthetic,kaggle_benchmark,system_live,unlabeled_test',
        ]);

        $file = $request->file('dataset_file');
        $filename = Str::slug($request->dataset_name) . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('ml_datasets', $filename);

        // Quick row count
        $fullPath = Storage::path($path);
        $rowCount = 0;
        if (($handle = fopen($fullPath, 'r')) !== false) {
            while (fgetcsv($handle) !== false) {
                $rowCount++;
            }
            fclose($handle);
        }
        $rowCount = max(0, $rowCount - 1); // subtract header

        $dataset = MlDataset::create([
            'name'        => $request->dataset_name,
            'source_type' => $request->source_type,
            'file_path'   => $path,
            'row_count'   => $rowCount,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', "Dataset \"{$dataset->name}\" uploaded successfully ({$rowCount} rows).");
    }

    // ─────────────────────────── Export System Data ───────────────────────────

    public function exportSystemData(Request $request)
    {
        $rows = $this->buildSystemExportRows();

        if ($rows->isEmpty()) {
            return back()->with('error', 'No vehicle service data available to export.');
        }

        $filename = 'system_live_export_' . now()->format('Ymd_His') . '.csv';
        $path = 'ml_datasets/' . $filename;
        $fullPath = storage_path('app/' . $path);

        $headers = array_keys($rows->first());
        $fp = fopen($fullPath, 'w');
        fputcsv($fp, $headers, ',', '"', "\\");
        foreach ($rows as $row) {
            fputcsv($fp, $row, ',', '"', "\\");
        }
        fclose($fp);

        $dataset = MlDataset::create([
            'name'        => 'System Live Export ' . now()->format('Y-m-d H:i'),
            'source_type' => 'system_live',
            'file_path'   => $path,
            'row_count'   => $rows->count(),
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', "System data exported as dataset \"{$dataset->name}\" ({$rows->count()} rows).");
    }

    // ─────────────────────────── Generate Synthetic Data ─────────────────────

    public function generateSyntheticData(Request $request)
    {
        $rows = (int) $request->input('rows', 600);
        $rows = max(100, min($rows, 2000));

        $outputDir = storage_path('app/ml_datasets');
        $scriptPath = storage_path('app/ml_engine/generate_sample_datasets.py');

        $result = Process::timeout(120)->run([
            'python3', $scriptPath,
            '--output_dir', $outputDir,
            '--rows', (string) $rows,
        ]);

        if (! $result->successful()) {
            Log::error('ML dataset generator failed', ['stderr' => $result->errorOutput()]);
            return back()->with('error', 'Dataset generator failed: ' . Str::limit($result->errorOutput(), 200));
        }

        // Parse the JSON output section
        $output = $result->output();
        $jsonStart = strpos($output, '[JSON_OUTPUT]');
        if ($jsonStart !== false) {
            $jsonStr = trim(substr($output, $jsonStart + strlen('[JSON_OUTPUT]')));
            $parsed = json_decode($jsonStr, true);

            if ($parsed) {
                foreach ($parsed as $type => $info) {
                    $relPath = str_replace(storage_path('app/'), '', $info['path']);
                    MlDataset::create([
                        'name'        => ucfirst(str_replace('_', ' ', $type)) . ' - ' . now()->format('Y-m-d H:i'),
                        'source_type' => $type === 'kaggle_benchmark' ? 'kaggle_benchmark' : 'synthetic',
                        'file_path'   => $relPath,
                        'row_count'   => $info['row_count'],
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            }
        }

        return back()->with('success', "Generated {$rows}-row synthetic + Kaggle benchmark datasets.");
    }

    // ─────────────────────────── Run ML Experiment ────────────────────────────

    public function runExperiment(Request $request)
    {
        $request->validate([
            'dataset_id'    => 'required|exists:ml_datasets,id',
            'algorithms'    => 'required|array|min:1',
            'algorithms.*'  => 'in:rule_based,decision_tree,random_forest,svm',
            'split_ratio'   => 'numeric|between:0.6,0.95',
            'cv_folds'      => 'integer|between:3,10',
        ]);

        $dataset   = MlDataset::findOrFail($request->dataset_id);
        $splitRatio = (float) $request->input('split_ratio', 0.80);
        $cvFolds    = (int) $request->input('cv_folds', 5);
        $algorithms = $request->input('algorithms');

        // Hyperparameter overrides
        $hyperparams = [
            'dt_max_depth'     => $request->input('dt_max_depth') ?: null,
            'rf_n_estimators'  => (int) $request->input('rf_n_estimators', 100),
            'rf_max_depth'     => $request->input('rf_max_depth') ?: null,
            'svm_c'            => (float) $request->input('svm_c', 1.0),
            'svm_gamma'        => $request->input('svm_gamma', 'scale'),
        ];

        $runNumber = $dataset->experiments()->count() + 1;
        $experiment = MlExperiment::create([
            'dataset_id'       => $dataset->id,
            'run_name'         => "Run #{$runNumber} — {$dataset->name}",
            'split_ratio'      => $splitRatio,
            'cv_folds'         => $cvFolds,
            'status'           => 'running',
            'hyperparams_json' => $hyperparams,
            'run_by'           => Auth::id(),
        ]);

        $outputDir = storage_path("app/ml_models/exp_{$experiment->id}");
        @mkdir($outputDir, 0755, true);

        $payload = [
            'dataset_path'  => $dataset->absolute_path,
            'dataset_type'  => $dataset->source_type,
            'models_to_run' => $algorithms,
            'output_dir'    => $outputDir,
            'split_ratio'   => $splitRatio,
            'cv_folds'      => $cvFolds,
            'hyperparams'   => $hyperparams,
        ];

        $payloadFile = $outputDir . '/payload.json';
        file_put_contents($payloadFile, json_encode($payload));

        $scriptPath = storage_path('app/ml_engine/run_experiment.py');

        // Run Python ML engine (synchronous for now — can be queued in production)
        $result = Process::timeout(300)->run([
            'python3', $scriptPath, '--payload', json_encode($payload),
        ]);

        if (! $result->successful()) {
            $experiment->update([
                'status'        => 'failed',
                'error_message' => Str::limit($result->errorOutput() ?: 'Unknown error', 1000),
            ]);
            Log::error('ML experiment failed', [
                'experiment_id' => $experiment->id,
                'stderr'        => $result->errorOutput(),
            ]);
            return back()->with('error', 'ML experiment failed. Check logs.');
        }

        $output = json_decode($result->output(), true);

        if (! $output || ($output['status'] ?? 'failed') === 'failed') {
            $experiment->update([
                'status'        => 'failed',
                'error_message' => $output['error'] ?? 'Python script returned an error',
            ]);
            return back()->with('error', 'ML engine returned an error: ' . ($output['error'] ?? 'Unknown'));
        }

        // Persist results
        DB::transaction(function () use ($experiment, $output, $algorithms) {
            // Build summary
            $summary = [
                'algorithms_run' => $algorithms,
                'row_count'      => $output['row_count'],
                'has_labels'     => $output['has_labels'],
                'models'         => $output['models'],
            ];
            $experiment->update([
                'status'       => 'completed',
                'summary_json' => $summary,
            ]);

            // Persist per-model metrics
            foreach ($output['models'] as $algo => $modelData) {
                MlModelMetric::updateOrCreate(
                    ['experiment_id' => $experiment->id, 'algorithm' => $algo],
                    [
                        'accuracy'             => $modelData['accuracy'] ?? null,
                        'precision_macro'      => $modelData['precision_macro'] ?? null,
                        'recall_macro'         => $modelData['recall_macro'] ?? null,
                        'f1_macro'             => $modelData['f1_macro'] ?? null,
                        'high_urgency_recall'  => $modelData['high_urgency_recall'] ?? null,
                        'confusion_matrix_json' => $modelData['confusion_matrix'] ?? null,
                        'yearly_accuracy_json'  => $modelData['yearly_accuracy'] ?? null,
                        'extra_meta_json'       => $modelData['extra_meta'] ?? null,
                    ]
                );
            }

            // Persist row-by-row predictions (chunked for memory efficiency)
            $chunks = array_chunk($output['predictions'] ?? [], 100);
            foreach ($chunks as $chunk) {
                $rows = [];
                foreach ($chunk as $pred) {
                    $rows[] = [
                        'experiment_id'      => $experiment->id,
                        'vehicle_identifier' => $pred['vehicle_identifier'] ?? 'UNKNOWN',
                        'service_type'       => $pred['service_type'] ?? '',
                        'scan_year'          => $pred['scan_year'] ?? null,
                        'ground_truth_label' => $pred['ground_truth_label'] ?? null,
                        'rb_prediction'      => $pred['rb_prediction'] ?? null,
                        'rb_confidence'      => $pred['rb_confidence'] ?? null,
                        'rb_days_remaining'  => $pred['rb_days_remaining'] ?? null,
                        'dt_prediction'      => $pred['dt_prediction'] ?? null,
                        'dt_confidence'      => $pred['dt_confidence'] ?? null,
                        'rf_prediction'      => $pred['rf_prediction'] ?? null,
                        'rf_confidence'      => $pred['rf_confidence'] ?? null,
                        'svm_prediction'     => $pred['svm_prediction'] ?? null,
                        'svm_confidence'     => $pred['svm_confidence'] ?? null,
                        'consensus_label'    => $pred['consensus_label'] ?? null,
                        'agreement_rate'     => $pred['agreement_rate'] ?? null,
                        'features_json'      => isset($pred['features']) ? json_encode($pred['features']) : null,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                }
                DB::table('ml_prediction_comparisons')->insert($rows);
            }
        });

        return redirect()
            ->route('admin.predictive-analytics.index')
            ->with('success', "Experiment \"{$experiment->run_name}\" completed successfully.");
    }

    // ─────────────────────────── Push to Live Alerts ──────────────────────────

    public function pushToAlerts(Request $request, MlPredictionComparison $prediction)
    {
        $request->validate([
            'algorithm' => 'required|in:rule_based,decision_tree,random_forest,svm',
        ]);

        $algo  = $request->input('algorithm');
        $field = $algo . '_prediction';
        $confField = $algo . '_confidence';
        $label = $prediction->$field;

        if (! $label) {
            return back()->with('error', 'No prediction available for selected algorithm.');
        }

        // Map urgency label
        $urgencyMap = ['HIGH' => 'high', 'MEDIUM' => 'medium', 'LOW' => 'low'];
        $urgency = $urgencyMap[$label] ?? 'low';

        // Try to find the vehicle by plate number
        $vehicle = Vehicle::where('plate_number', $prediction->vehicle_identifier)->first();

        $alert = MaintenanceAlert::create([
            'vehicle_id'         => $vehicle?->id ?? 1, // fallback for synthetic data
            'source'             => 'predicted',
            'alert_type'        => $prediction->service_type,
            'urgency'            => $urgency,
            'recommendation'    => "ML prediction ({$algo}): {$label} urgency for {$prediction->service_type}. Confidence: {$prediction->$confField}%",
            'predicted_by_algo'  => $algo,
            'confidence_score'   => $prediction->$confField,
            'ml_staff_verified'  => 'pending',
        ]);

        $prediction->update([
            'pushed_to_alerts' => true,
            'pushed_alert_id'  => $alert->id,
            'pushed_algo'      => $algo,
        ]);

        return back()->with('success', "Prediction pushed to Maintenance Alerts (Alert #{$alert->id}).");
    }

    // ─────────────────────────── Staff Verification ───────────────────────────

    public function predictionAuditDetail(MlPredictionComparison $prediction)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $prediction->getExplainability(),
        ]);
    }

    public function verifyPrediction(Request $request, MlPredictionComparison $prediction)
    {
        $request->validate([
            'status' => 'required|in:verified_correct,verified_incorrect',
            'notes'  => 'nullable|string|max:500',
        ]);

        $prediction->update([
            'staff_verification_status' => $request->input('status'),
            'verified_by'   => Auth::id(),
            'verified_at'   => now(),
            'staff_notes'   => $request->input('notes'),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Prediction verification recorded.',
                'data'    => $prediction->fresh()->getExplainability(),
            ]);
        }

        return back()->with('success', 'Prediction verification recorded.');
    }

    // ─────────────────────────── Delete Dataset ───────────────────────────────

    public function deleteDataset(MlDataset $dataset)
    {
        if ($dataset->experiments()->exists()) {
            return back()->with('error', 'Cannot delete dataset — it has associated experiments.');
        }

        Storage::delete($dataset->file_path);
        $dataset->delete();

        return back()->with('success', 'Dataset deleted.');
    }

    // ─────────────────────────── Get Experiment Predictions (AJAX) ───────────

    public function experimentPredictions(Request $request, MlExperiment $experiment): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 50), 200);
        $page    = (int) $request->input('page', 1);
        $search  = $request->input('search', '');
        $filter  = $request->input('urgency', '');

        $query = $experiment->predictions();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_identifier', 'like', "%{$search}%")
                  ->orWhere('service_type', 'like', "%{$search}%");
            });
        }

        if ($filter) {
            $query->where('consensus_label', strtoupper($filter));
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated);
    }

    // ─────────────────────────── Chart Data (AJAX) ────────────────────────────

    public function chartData(MlExperiment $experiment): JsonResponse
    {
        $metrics = $experiment->metrics->keyBy('algorithm');

        $data = [];
        foreach (['rule_based', 'decision_tree', 'random_forest', 'svm'] as $algo) {
            $m = $metrics->get($algo);
            $data[$algo] = [
                'accuracy'            => $m ? round($m->accuracy * 100, 2) : null,
                'f1_macro'            => $m ? round($m->f1_macro * 100, 2) : null,
                'high_urgency_recall' => $m ? round($m->high_urgency_recall * 100, 2) : null,
                'confusion_matrix'    => $m?->confusion_matrix_json,
                'yearly_accuracy'     => $m?->yearly_accuracy_json,
                'extra_meta'          => $m?->extra_meta_json,
            ];
        }

        return response()->json($data);
    }

    // ─────────────────────────── Helper: System Export Rows ───────────────────

    private function buildSystemExportRows(): \Illuminate\Support\Collection
    {
        $today = Carbon::today();

        $rows = collect();

        $vehicles = Vehicle::with(['serviceHistory.jobCard.jobType', 'tripLogs', 'owner'])->get();

        foreach ($vehicles as $vehicle) {
            $vehicleAge = $today->year - (int) $vehicle->year;
            $totalMileage = (float) $vehicle->mileage;
            $ownerType = ($vehicle->owner && $vehicle->owner->role === 'corporate') ? 'corporate' : 'individual';
            $fuelType = strtolower($vehicle->fuel_type ?? 'petrol');

            // Estimate daily KM from trip logs
            $tripLogs = $vehicle->tripLogs->sortBy('trip_date');
            $dailyKm = 40.0;
            if ($tripLogs->count() >= 2) {
                $totalKm = $tripLogs->sum('distance_km');
                $spanDays = $tripLogs->first()->trip_date->diffInDays($tripLogs->last()->trip_date);
                if ($spanDays > 0) {
                    $dailyKm = round($totalKm / $spanDays, 1);
                }
            }
            $dailyKm = max(10, min($dailyKm, 150));

            // Dominant terrain from trip logs
            $terrainCounts = $tripLogs->groupBy('terrain_type')->map->count()->sortDesc();
            $dominantTerrain = $terrainCounts->keys()->first() ?? 'urban';
            $terrainSeverityMap = [
                'urban' => 1.15, 'highway' => 0.90, 'rural' => 1.20,
                'mountain' => 1.30, 'mixed' => 1.00
            ];
            $terrainSeverity = $terrainSeverityMap[$dominantTerrain] ?? 1.00;

            // Per service type
            $jobTypes = JobType::whereNotNull('interval_km')->orWhereNotNull('interval_months')->get();

            foreach ($jobTypes as $jobType) {
                // Find last service of this type
                $lastService = $vehicle->serviceHistory
                    ->filter(fn($sh) => $sh->jobCard && $sh->jobCard->job_type_id == $jobType->id)
                    ->sortByDesc('service_date')
                    ->first();

                if (! $lastService) {
                    continue;
                }

                $lastDate = Carbon::parse($lastService->service_date);
                $daysSince = $lastDate->diffInDays($today);
                $kmSince = round($dailyKm * $daysSince);

                $intervalKm = $jobType->interval_km ?? 999999;
                $intervalMonths = $jobType->interval_months ?? 12;

                $rows->push([
                    'vehicle_id'       => $vehicle->plate_number,
                    'plate_number'     => $vehicle->plate_number,
                    'service_type'     => $jobType->name,
                    'year'             => $today->year,
                    'scan_date'        => $today->toDateString(),
                    'days_since_service' => $daysSince,
                    'km_since_service'   => $kmSince,
                    'interval_km'        => $intervalKm,
                    'interval_months'    => $intervalMonths,
                    'daily_km'           => $dailyKm,
                    'terrain_severity'   => $terrainSeverity,
                    'vehicle_age'        => max(1, $vehicleAge),
                    'total_mileage'      => $totalMileage,
                    'fuel_type'          => $fuelType,
                    'owner_type'         => $ownerType,
                ]);
            }
        }

        return $rows;
    }

    // ─────────────────────────── Helper: Verification Stats ──────────────────

    private function getVerificationStats(): array
    {
        $total = MlPredictionComparison::count();
        if ($total === 0) {
            return ['total' => 0, 'verified' => 0, 'incorrect' => 0, 'pending' => 0, 'accuracy_rate' => null];
        }

        $verified   = MlPredictionComparison::where('staff_verification_status', 'verified_correct')->count();
        $incorrect  = MlPredictionComparison::where('staff_verification_status', 'verified_incorrect')->count();
        $reviewed   = $verified + $incorrect;
        $rate       = $reviewed > 0 ? round(($verified / $reviewed) * 100, 1) : null;

        return [
            'total'         => $total,
            'verified'      => $verified,
            'incorrect'     => $incorrect,
            'pending'       => $total - $reviewed,
            'accuracy_rate' => $rate,
        ];
    }

    // ─────────────────────────── Dedicated Researcher Panel ───────────────────

    public function researcherPanel(Request $request)
    {
        $experiments = MlExperiment::with(['dataset', 'metrics'])
            ->where('status', 'completed')
            ->latest()
            ->get();

        $selectedExpId = $request->input('experiment_id');
        $selectedExperiment = null;

        if ($selectedExpId) {
            $selectedExperiment = $experiments->firstWhere('id', (int) $selectedExpId);
        }
        if (! $selectedExperiment) {
            $selectedExperiment = $experiments->first();
        }

        if (! $selectedExperiment) {
            return view('admin.predictive-analytics.researcher-panel', [
                'experiments'        => $experiments,
                'selectedExperiment' => null,
                'metrics'            => collect(),
                'predictions'        => collect(),
                'confStats'          => [],
                'verificationStats'  => $this->getVerificationStats(),
                'filters'            => $request->all(),
            ]);
        }

        $metrics = $selectedExperiment->metrics->keyBy('algorithm');

        // Detailed Confidence Statistics across all 4 models
        $allPredsQuery = $selectedExperiment->predictions();
        $totalCount = (clone $allPredsQuery)->count();

        $avgRbConf  = (float) (clone $allPredsQuery)->whereNotNull('rb_confidence')->avg('rb_confidence');
        $avgDtConf  = (float) (clone $allPredsQuery)->whereNotNull('dt_confidence')->avg('dt_confidence');
        $avgRfConf  = (float) (clone $allPredsQuery)->whereNotNull('rf_confidence')->avg('rf_confidence');
        $avgSvmConf = (float) (clone $allPredsQuery)->whereNotNull('svm_confidence')->avg('svm_confidence');

        $overallMeanConf = round(($avgRbConf + $avgDtConf + $avgRfConf + $avgSvmConf) / 4, 1);

        // Confidence Tiers (benchmarked against ensemble/RF confidence)
        $highConfCount = (clone $allPredsQuery)->where('rf_confidence', '>=', 85)->count();
        $modConfCount  = (clone $allPredsQuery)->whereBetween('rf_confidence', [65, 84.99])->count();
        $lowConfCount  = (clone $allPredsQuery)->where('rf_confidence', '<', 65)->count();

        // Agreement & Consensus Breakdown
        $unanimousCount = (clone $allPredsQuery)->where('agreement_rate', '4/4')->count();
        $majorityCount  = (clone $allPredsQuery)->where('agreement_rate', '3/4')->count();
        $splitCount     = (clone $allPredsQuery)->where('agreement_rate', 'not like', '4/%')
                                               ->where('agreement_rate', 'not like', '3/%')
                                               ->count();

        // Model Divergence (where ML spotted something Rule-Based didn't)
        $mlVsRbDiffCount = (clone $allPredsQuery)
            ->whereNotNull('rb_prediction')
            ->where(function ($q) {
                $q->whereColumn('rf_prediction', '!=', 'rb_prediction')
                  ->orWhereColumn('dt_prediction', '!=', 'rb_prediction')
                  ->orWhereColumn('svm_prediction', '!=', 'rb_prediction');
            })->count();

        $confStats = [
            'total_rows'       => $totalCount,
            'overall_mean'     => $overallMeanConf,
            'avg_rb'           => round($avgRbConf, 1),
            'avg_dt'           => round($avgDtConf, 1),
            'avg_rf'           => round($avgRfConf, 1),
            'avg_svm'          => round($avgSvmConf, 1),
            'high_count'       => $highConfCount,
            'high_pct'         => $totalCount > 0 ? round(($highConfCount / $totalCount) * 100, 1) : 0,
            'mod_count'        => $modConfCount,
            'mod_pct'          => $totalCount > 0 ? round(($modConfCount / $totalCount) * 100, 1) : 0,
            'low_count'        => $lowConfCount,
            'low_pct'          => $totalCount > 0 ? round(($lowConfCount / $totalCount) * 100, 1) : 0,
            'unanimous_count'  => $unanimousCount,
            'unanimous_pct'    => $totalCount > 0 ? round(($unanimousCount / $totalCount) * 100, 1) : 0,
            'majority_count'   => $majorityCount,
            'majority_pct'     => $totalCount > 0 ? round(($majorityCount / $totalCount) * 100, 1) : 0,
            'split_count'      => $splitCount,
            'split_pct'        => $totalCount > 0 ? round(($splitCount / $totalCount) * 100, 1) : 0,
            'ml_vs_rb_diff'    => $mlVsRbDiffCount,
        ];

        // Filtered Predictions Query
        $pQuery = $selectedExperiment->predictions();

        // Search Filter
        if ($search = $request->input('search')) {
            $pQuery->where(function ($q) use ($search) {
                $q->where('vehicle_identifier', 'like', "%{$search}%")
                  ->orWhere('service_type', 'like', "%{$search}%");
            });
        }

        // Confidence Tier Filter
        if ($tier = $request->input('confidence_tier')) {
            if ($tier === 'high') {
                $pQuery->where('rf_confidence', '>=', 85);
            } elseif ($tier === 'moderate') {
                $pQuery->whereBetween('rf_confidence', [65, 84.99]);
            } elseif ($tier === 'uncertain') {
                $pQuery->where('rf_confidence', '<', 65);
            }
        }

        // Agreement Filter
        if ($agreement = $request->input('agreement')) {
            if ($agreement === 'unanimous') {
                $pQuery->where('agreement_rate', '4/4');
            } elseif ($agreement === 'majority') {
                $pQuery->where('agreement_rate', '3/4');
            } elseif ($agreement === 'split') {
                $pQuery->where('agreement_rate', 'not like', '4/%')
                       ->where('agreement_rate', 'not like', '3/%');
            }
        }

        // Urgency Filter
        if ($urgency = $request->input('urgency')) {
            $pQuery->where('consensus_label', strtoupper($urgency));
        }

        // Divergence Filter
        if ($divergence = $request->input('divergence')) {
            if ($divergence === 'ml_vs_rb') {
                $pQuery->whereNotNull('rb_prediction')
                       ->where(function ($q) {
                           $q->whereColumn('rf_prediction', '!=', 'rb_prediction')
                             ->orWhereColumn('dt_prediction', '!=', 'rb_prediction')
                             ->orWhereColumn('svm_prediction', '!=', 'rb_prediction');
                       });
            } elseif ($divergence === 'actual_diff') {
                $pQuery->whereNotNull('ground_truth_label')
                       ->whereColumn('consensus_label', '!=', 'ground_truth_label');
            }
        }

        // Staff Verification Filter
        if ($verifyStatus = $request->input('verification_status')) {
            $pQuery->where('staff_verification_status', $verifyStatus);
        }

        // Sorting
        $sort = $request->input('sort', 'default');
        if ($sort === 'conf_desc') {
            $pQuery->orderByDesc('rf_confidence');
        } elseif ($sort === 'conf_asc') {
            $pQuery->orderBy('rf_confidence');
        } elseif ($sort === 'conflict_first') {
            $pQuery->orderBy('agreement_rate', 'asc');
        } else {
            $pQuery->latest();
        }

        $predictions = $pQuery->paginate(50)->withQueryString();

        return view('admin.predictive-analytics.researcher-panel', [
            'experiments'        => $experiments,
            'selectedExperiment' => $selectedExperiment,
            'metrics'            => $metrics,
            'predictions'        => $predictions,
            'confStats'          => $confStats,
            'verificationStats'  => $this->getVerificationStats(),
            'filters'            => $request->all(),
        ]);
    }

    // ─────────────────────────── Export Predictions CSV ───────────────────────

    public function exportPredictionsCsv(MlExperiment $experiment)
    {
        $filename = 'predictive_report_exp_' . $experiment->id . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($experiment) {
            $fp = fopen('php://output', 'w');

            // Metadata Comments
            fputcsv($fp, ['# =============================================================================='], ',', '"', "\\");
            fputcsv($fp, ['# FLEET PREDICTIVE ANALYTICS & XAI MODEL AUDIT REPORT'], ',', '"', "\\");
            fputcsv($fp, ['# Experiment Name:', $experiment->run_name], ',', '"', "\\");
            fputcsv($fp, ['# Experiment ID:', $experiment->id], ',', '"', "\\");
            fputcsv($fp, ['# Dataset Used:', $experiment->dataset?->name ?? 'Fleet Benchmark'], ',', '"', "\\");
            fputcsv($fp, ['# Generated At:', now()->toDateTimeString()], ',', '"', "\\");
            fputcsv($fp, ['# Total Predictions:', $experiment->predictions()->count()], ',', '"', "\\");
            fputcsv($fp, ['# =============================================================================='], ',', '"', "\\");
            fputcsv($fp, [], ',', '"', "\\");

            // Column Headers
            fputcsv($fp, [
                'Vehicle Identifier',
                'Plate Number',
                'Service Type',
                'Scan Year',
                'Ground Truth Urgency',
                // Physical Telemetry Factors
                'Km Since Service',
                'Days Since Service',
                'Interval Km',
                'Interval Months',
                'Daily Km Rate (km/d)',
                'Terrain Severity Multiplier',
                'Total Vehicle Mileage (km)',
                'Vehicle Age (years)',
                'Fuel Type',
                'Effective Days Remaining (d)',
                'Governing Bottleneck Limit',
                // Model 1: Rule-Based Baseline
                'Rule-Based Prediction',
                'Rule-Based Confidence (%)',
                'Rule-Based Days Remaining',
                'Rule-Based Decision Reason',
                // Model 2: Decision Tree (CART)
                'Decision Tree Prediction',
                'Decision Tree Confidence (%)',
                'Decision Tree Reason & Purity',
                // Model 3: Random Forest
                'Random Forest Prediction',
                'Random Forest Confidence (%)',
                'Random Forest Vote Count (out of 100)',
                'Random Forest Reason',
                // Model 4: SVM (RBF)
                'SVM Prediction',
                'SVM Confidence (%)',
                'SVM Posterior Probability',
                'SVM Reason',
                // Consensus & Diagnostics
                'Consensus Label',
                'Agreement Rate',
                'Divergence Diagnostic',
                // Verification & Staff Audit
                'Pushed To Live Alerts',
                'Staff Verification Status',
                'Audited By',
                'Audited At',
                'Staff Inspection Notes',
            ], ',', '"', "\\");

            $experiment->predictions()->with('verifier')->chunk(150, function ($preds) use ($fp) {
                foreach ($preds as $p) {
                    $ex = $p->getExplainability();
                    $feat = $ex['features'] ?? [];
                    $models = $ex['models'] ?? [];
                    $div = $ex['divergence'] ?? [];
                    $ver = $ex['verification'] ?? [];

                    fputcsv($fp, [
                        $p->vehicle_identifier,
                        $ex['plate_number'] ?? $p->vehicle_identifier,
                        $p->service_type,
                        $p->scan_year,
                        $p->ground_truth_label ?? 'N/A',
                        // Physical Telemetry
                        $feat['km_since_service'] ?? 0,
                        $feat['days_since_service'] ?? 0,
                        $feat['interval_km'] ?? 10000,
                        $feat['interval_months'] ?? 12,
                        $feat['daily_km'] ?? 40.0,
                        $feat['terrain_severity'] ?? 1.0,
                        $feat['total_mileage'] ?? 0,
                        $feat['vehicle_age'] ?? 5,
                        $feat['fuel_type'] ?? 'Diesel',
                        $feat['days_remaining'] ?? $p->rb_days_remaining,
                        $feat['governing_limit'] ?? 'Mileage Limit',
                        // Model 1: Rule-Based
                        $p->rb_prediction,
                        $p->rb_confidence,
                        $p->rb_days_remaining,
                        $models['rule_based']['reason'] ?? '',
                        // Model 2: Decision Tree
                        $p->dt_prediction,
                        $p->dt_confidence,
                        $models['decision_tree']['reason'] ?? '',
                        // Model 3: Random Forest
                        $p->rf_prediction,
                        $p->rf_confidence,
                        $models['random_forest']['vote_count'] ?? '',
                        $models['random_forest']['reason'] ?? '',
                        // Model 4: SVM
                        $p->svm_prediction,
                        $p->svm_confidence,
                        $models['svm']['posterior_prob'] ?? '',
                        $models['svm']['reason'] ?? '',
                        // Consensus & Diagnostics
                        $p->consensus_label,
                        $p->agreement_rate,
                        ($div['nature'] ?? '') . ': ' . ($div['summary'] ?? ''),
                        // Verification & Audit
                        $p->pushed_to_alerts ? 'Yes' : 'No',
                        $p->staff_verification_status,
                        $ver['verified_by'] ?? ($p->verifier?->name ?? 'Unassigned'),
                        $ver['verified_at'] ?? ($p->verified_at?->format('Y-m-d H:i') ?? 'N/A'),
                        $p->staff_notes ?? '',
                    ], ',', '"', "\\");
                }
            });

            fclose($fp);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ─────────────────────────── Export LaTeX Snippet ─────────────────────────

    public function exportLatexSnippet(MlExperiment $experiment): JsonResponse
    {
        $metrics = $experiment->metrics->keyBy('algorithm');
        $preds = $experiment->predictions();
        $summaryModels = $experiment->summary_json['models'] ?? [];

        $rows = [];
        $algoLabels = [
            'rule_based'    => 'Rule-Based Deterministic Baseline',
            'decision_tree' => 'Decision Tree Classifier (CART)',
            'random_forest' => 'Random Forest Ensemble (100 Trees)',
            'svm'           => 'Support Vector Machine (RBF Kernel)',
        ];

        foreach ($algoLabels as $key => $label) {
            $m = $metrics->get($key);
            $confCol = match($key) {
                'rule_based'    => 'rb_confidence',
                'decision_tree' => 'dt_confidence',
                'random_forest' => 'rf_confidence',
                'svm'           => 'svm_confidence',
            };
            $avgConf = (clone $preds)->whereNotNull($confCol)->avg($confCol);

            $acc = $m ? round($m->accuracy * 100, 2) : '—';
            $f1  = $m ? round($m->f1_macro * 100, 2) : '—';
            $rec = $m ? round($m->high_urgency_recall * 100, 2) : '—';
            
            $cvVal = $summaryModels[$key]['cv_accuracy'] ?? ($m?->extra_meta_json['cv_accuracy'] ?? null);
            $cv = ($cvVal !== null && $key !== 'rule_based') ? round($cvVal * 100, 2) : 'N/A';
            $conf = $avgConf ? round($avgConf, 2) : '—';

            $rows[] = "{$label} & {$acc} & {$f1} & {$rec} & {$cv} & {$conf} \\\\";
        }

        $rowsString = implode("\n", $rows);
        $latexTable = <<<LATEX
% Academic Table: 4-Model Predictive Maintenance Comparison
% Generated automatically by Teraju Setia R&D Researcher Panel
\\begin{table}[htbp]
\\centering
\\caption{Comparative Performance of the 4 Predictive Maintenance Engines on Experiment #{$experiment->id} ({$experiment->dataset?->name})}
\\label{tab:fleet_predictive_models_exp{$experiment->id}}
\\begin{tabular}{lccccc}
\\hline
\\textbf{Algorithm} & \\textbf{Accuracy (\\%)} & \\textbf{Macro F1 (\\%)} & \\textbf{HIGH Recall (\\%)} & \\textbf{5-Fold CV (\\%)} & \\textbf{Mean Conf. (\\%)} \\\\
\\hline
{$rowsString}
\\hline
\\end{tabular}
\\end{table}
LATEX;

        $methodologySnippet = <<<LATEX
% Academic Methodology & Mathematical Formulation
% Ready for IEEE / Elsevier / Scopus Manuscript Submission

\\section{Methodology \\& Mathematical Formulation}

\\subsection{Urgency Class Formulation}
Maintenance urgency is framed as a 3-class operational classification problem based on remaining operational days \$d\$:
\\begin{equation}
y = 
\\begin{cases} 
\\text{HIGH}, & \\text{if } d \\le 0 \\quad (\\text{Overdue / Immediate Workshop Maintenance}), \\\\
\\text{MEDIUM}, & \\text{if } 0 < d \\le 14 \\quad (\\text{Preventive Booking Window}), \\\\
\\text{LOW}, & \\text{if } d > 14 \\quad (\\text{Routine Operational Monitoring}).
\\end{cases}
\\end{equation}

\\subsection{Model 1: Deterministic Hybrid Time/Mileage Baseline}
The baseline heuristic combines wear-distance thresholds and temporal intervals weighted by terrain severity factor \$\\alpha\$ (Urban=1.15, Highway=0.90, Rural=1.20, Mountain=1.30, Mixed=1.00):
\\begin{align}
\\text{eff\\_km} &= \\frac{\\text{interval\\_km}}{\\alpha}, \\\\
\\text{remaining\\_km} &= \\text{eff\\_km} - \\text{km\\_since\\_service}, \\\\
d_{\\text{km}} &= \\left\\lfloor \\frac{\\text{remaining\\_km}}{\\max(\\text{daily\\_km}, 1)} \\right\\rfloor, \\\\
d_{\\text{time}} &= (\\text{interval\\_months} \\times 30.44) - \\text{days\\_since\\_service}, \\\\
d &= \\min(d_{\\text{km}}, d_{\\text{time}}).
\\end{align}

Confidence for the deterministic baseline is calculated as the normalized distance from the nearest boundary:
\\begin{equation}
\\text{Conf}_{\\text{RB}}(d) = 
\\begin{cases}
50 + 50 \\times \\min\\left(\\frac{|d|}{60}, 1\\right), & \\text{if } d \\le 0 \\text{ (HIGH)}, \\\\
50 + 50 \\times \\frac{\\min(d, 14 - d)}{7}, & \\text{if } 0 < d \\le 14 \\text{ (MEDIUM)}, \\\\
50 + 50 \\times \\min\\left(\\frac{d - 14}{60}, 1\\right), & \\text{if } d > 14 \\text{ (LOW)}.
\\end{cases}
\\end{equation}

\\subsection{Model 2, 3, and 4: Machine Learning Classifiers}
Evaluated strictly on the 80/20 stratified split with 5-fold cross-validation:
\\begin{enumerate}
    \\item \\textbf{Decision Tree (CART)}: Gini index criterion \$I_G(t) = 1 - \\sum_{i=1}^3 p(i|t)^2\$. Confidence is maximum leaf class probability.
    \\item \\textbf{Random Forest (Ensemble)}: 100 bagging trees with feature subspace selection \$m = \\sqrt{p}\$. Confidence is fraction of tree votes for winning class.
    \\item \\textbf{Support Vector Machine (RBF)}: Kernel \$K(x, x') = \\exp(-\\gamma ||x - x'||^2)\$ with Platt-scaled probabilistic calibration.
\\end{enumerate}
LATEX;

        $bibtex = <<<BIBTEX
@article{breiman2001random,
  title={Random forests},
  author={Breiman, Leo},
  journal={Machine Learning},
  volume={45},
  number={1},
  pages={5--32},
  year={2001},
  publisher={Springer}
}

@article{quinlan1986induction,
  title={Induction of decision trees},
  author={Quinlan, J. Ross},
  journal={Machine Learning},
  volume={1},
  number={1},
  pages={81--106},
  year={1986},
  publisher={Springer}
}

@article{cortes1995support,
  title={Support-vector networks},
  author={Cortes, Corinna and Vapnik, Vladimir},
  journal={Machine Learning},
  volume={20},
  number={3},
  pages={273--297},
  year={1995},
  publisher={Springer}
}

@standard{iso13374,
  title={Condition monitoring and diagnostics of machines -- Data processing, communication and presentation},
  organization={International Organization for Standardization},
  number={ISO 13374-1:2003},
  year={2003}
}
BIBTEX;

        return response()->json([
            'latex'               => $latexTable,
            'latex_table'         => $latexTable,
            'methodology_snippet' => $methodologySnippet,
            'bibtex'              => $bibtex,
        ]);
    }

    // ── Interactive Google Colab Notebook Lab ────────────────────────────

    public function colabNotebook(Request $request)
    {
        $datasets = MlDataset::latest()->get();
        $selectedDatasetId = $request->input('dataset_id');
        $selectedDataset = $selectedDatasetId
            ? $datasets->firstWhere('id', (int) $selectedDatasetId)
            : $datasets->first();

        $defaultCells = $this->getDefaultColabCells();

        return view('admin.predictive-analytics.colab', [
            'datasets'        => $datasets,
            'selectedDataset' => $selectedDataset,
            'defaultCells'    => $defaultCells,
        ]);
    }

    public function executeColabCell(Request $request): JsonResponse
    {
        $request->validate([
            'dataset_id' => 'required|exists:ml_datasets,id',
            'action'     => 'required|in:run_single_cell,run_all',
            'cells'      => 'required|array',
            'cell_key'   => 'nullable|string',
            'cell_order' => 'nullable|array',
        ]);

        $dataset = MlDataset::findOrFail($request->dataset_id);
        $scriptPath = storage_path('app/ml_engine/run_colab_cells.py');

        $payload = [
            'dataset_path' => $dataset->absolute_path,
            'action'       => $request->action,
            'cell_key'     => $request->cell_key,
            'cells'        => $request->cells,
            'cell_order'   => $request->cell_order ?? array_keys($request->cells),
        ];

        $res = Process::timeout(180)->run([
            'python3', $scriptPath, '--payload', json_encode($payload),
        ]);

        if (! $res->successful()) {
            return response()->json([
                'status'       => 'error',
                'stderr'       => $res->errorOutput() ?: 'Process execution failure',
                'stdout'       => '',
                'cell_results' => [],
            ], 500);
        }

        $parsed = json_decode($res->output(), true);

        return response()->json($parsed ?: [
            'status' => 'error',
            'stderr' => 'Invalid JSON output from Python execution engine',
            'raw'    => $res->output(),
        ]);
    }

    public function commitColabExperiment(Request $request): JsonResponse
    {
        $request->validate([
            'dataset_id' => 'required|exists:ml_datasets,id',
            'cells'      => 'required|array',
            'run_name'   => 'nullable|string|max:100',
        ]);

        $dataset = MlDataset::findOrFail($request->dataset_id);
        $runName = $request->input('run_name') ?: ('Colab Custom Run #' . ($dataset->experiments()->count() + 1) . ' — ' . $dataset->name);

        $experiment = MlExperiment::create([
            'dataset_id'       => $dataset->id,
            'run_name'         => $runName,
            'split_ratio'      => 0.80,
            'cv_folds'         => 5,
            'status'           => 'running',
            'hyperparams_json' => ['source' => 'colab_interactive_notebook'],
            'run_by'           => Auth::id(),
        ]);

        $outputDir = storage_path("app/ml_models/exp_{$experiment->id}");
        @mkdir($outputDir, 0755, true);

        // Run full experiment pipeline
        $payload = [
            'dataset_path'  => $dataset->absolute_path,
            'dataset_type'  => $dataset->source_type,
            'models_to_run' => ['rule_based', 'decision_tree', 'random_forest', 'svm'],
            'output_dir'    => $outputDir,
            'split_ratio'   => 0.80,
            'cv_folds'      => 5,
            'hyperparams'   => [],
        ];

        $scriptPath = storage_path('app/ml_engine/run_experiment.py');
        $res = Process::timeout(300)->run([
            'python3', $scriptPath, '--payload', json_encode($payload),
        ]);

        if (! $res->successful()) {
            $experiment->update([
                'status'        => 'failed',
                'error_message' => Str::limit($res->errorOutput(), 1000),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Experiment failed'], 500);
        }

        $output = json_decode($res->output(), true);

        DB::transaction(function () use ($experiment, $output) {
            $experiment->update([
                'status'       => 'completed',
                'summary_json' => [
                    'algorithms_run' => ['rule_based', 'decision_tree', 'random_forest', 'svm'],
                    'row_count'      => $output['row_count'],
                    'has_labels'     => $output['has_labels'],
                    'models'         => $output['models'],
                    'colab_source'   => true,
                ],
            ]);

            foreach ($output['models'] as $algo => $modelData) {
                MlModelMetric::updateOrCreate(
                    ['experiment_id' => $experiment->id, 'algorithm' => $algo],
                    [
                        'accuracy'             => $modelData['accuracy'] ?? null,
                        'precision_macro'      => $modelData['precision_macro'] ?? null,
                        'recall_macro'         => $modelData['recall_macro'] ?? null,
                        'f1_macro'             => $modelData['f1_macro'] ?? null,
                        'high_urgency_recall'  => $modelData['high_urgency_recall'] ?? null,
                        'confusion_matrix_json' => $modelData['confusion_matrix'] ?? null,
                        'yearly_accuracy_json'  => $modelData['yearly_accuracy'] ?? null,
                        'extra_meta_json'       => $modelData['extra_meta'] ?? null,
                    ]
                );
            }

            foreach (array_chunk($output['predictions'] ?? [], 100) as $chunk) {
                $rows = [];
                foreach ($chunk as $pred) {
                    $rows[] = [
                        'experiment_id'      => $experiment->id,
                        'vehicle_identifier' => $pred['vehicle_identifier'] ?? 'UNKNOWN',
                        'service_type'       => $pred['service_type'] ?? '',
                        'scan_year'          => $pred['scan_year'] ?? null,
                        'ground_truth_label' => $pred['ground_truth_label'] ?? null,
                        'rb_prediction'      => $pred['rb_prediction'] ?? null,
                        'rb_confidence'      => $pred['rb_confidence'] ?? null,
                        'rb_days_remaining'  => $pred['rb_days_remaining'] ?? null,
                        'dt_prediction'      => $pred['dt_prediction'] ?? null,
                        'dt_confidence'      => $pred['dt_confidence'] ?? null,
                        'rf_prediction'      => $pred['rf_prediction'] ?? null,
                        'rf_confidence'      => $pred['rf_confidence'] ?? null,
                        'svm_prediction'     => $pred['svm_prediction'] ?? null,
                        'svm_confidence'     => $pred['svm_confidence'] ?? null,
                        'consensus_label'    => $pred['consensus_label'] ?? null,
                        'agreement_rate'     => $pred['agreement_rate'] ?? null,
                        'features_json'      => isset($pred['features']) ? json_encode($pred['features']) : null,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                }
                DB::table('ml_prediction_comparisons')->insert($rows);
            }
        });

        return response()->json([
            'status'        => 'success',
            'experiment_id' => $experiment->id,
            'redirect_url'  => route('admin.researcher-panel', ['experiment_id' => $experiment->id]),
        ]);
    }

    private function getDefaultColabCells(): array
    {
        return [
            'cell_1' => [
                'title'       => 'Cell 1: Environment Setup & Dataset Ingestion Pipeline',
                'short_title' => 'Pipeline',
                'badge'       => 'Pipeline & Ingestion',
                'badge_color' => 'bg-sky-50 text-sky-700 border-sky-200',
                'icon'        => 'database',
                'description' => 'Load selected dataset, clean features, and prepare 80/20 stratified split.',
                'code'        => "# [Cell 1] Dataset Ingestion & Preprocessing Pipeline\nimport pandas as pd\nimport numpy as np\nfrom sklearn.model_selection import train_test_split\nfrom sklearn.preprocessing import StandardScaler, OneHotEncoder\nfrom sklearn.compose import ColumnTransformer\n\n# Load selected Teraju Setia dataset\ndf = pd.read_csv(DATASET_PATH)\ndf.columns = [c.strip().lower().replace(' ', '_') for c in df.columns]\nif 'urgency' in df.columns:\n    df.rename(columns={'urgency': 'urgency_label'}, inplace=True)\ndf['urgency_label'] = df['urgency_label'].str.upper()\n\nNUMERIC_FEATURES = ['days_since_service', 'km_since_service', 'interval_km', 'interval_months', 'daily_km', 'terrain_severity', 'vehicle_age', 'total_mileage']\nCATEGORICAL_FEATURES = ['fuel_type', 'owner_type']\n\nfor col in NUMERIC_FEATURES:\n    df[col] = pd.to_numeric(df[col], errors='coerce').fillna(0)\n\npreprocessor = ColumnTransformer(transformers=[\n    ('num', StandardScaler(), NUMERIC_FEATURES),\n    ('cat', OneHotEncoder(handle_unknown='ignore', sparse_output=False), CATEGORICAL_FEATURES)\n])\n\nX = df[NUMERIC_FEATURES + CATEGORICAL_FEATURES]\ny = df['urgency_label'].values\n\nX_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.20, stratify=y, random_state=42)\nX_train_pp = preprocessor.fit_transform(X_train)\nX_test_pp = preprocessor.transform(X_test)\n\nprint(f\"✓ Dataset Loaded: {len(df)} total rows\")\nprint(f\"✓ Train Split: {len(X_train)} samples | Test Split: {len(X_test)} samples\")\nprint(f\"✓ Target Class Distribution: {dict(pd.Series(y).value_counts())}\")",
            ],
            'cell_2' => [
                'title'       => 'Cell 2: Model 1 — Deterministic Hybrid Rule-Based Model',
                'short_title' => '1. Rule-Based',
                'badge'       => 'Deterministic Rule',
                'badge_color' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon'        => 'cpu',
                'description' => 'Hybrid time/mileage calculation with terrain severity adjustment factor.',
                'code'        => "# [Cell 2] Model 1: Deterministic Hybrid Time/Mileage + Terrain Severity Rule\ndef run_rule_based(X_subset):\n    preds, confs = [], []\n    for _, row in X_subset.iterrows():\n        eff_km = max(float(row.get('interval_km', 5000)) / max(float(row.get('terrain_severity', 1.0)), 0.1), 1)\n        remaining_km = eff_km - float(row.get('km_since_service', 0))\n        daily = max(float(row.get('daily_km', 40)), 1)\n        days_via_km = remaining_km / daily\n        days_via_time = (float(row.get('interval_months', 6)) * 30.44) - float(row.get('days_since_service', 0))\n        d = round(min(days_via_km, days_via_time))\n\n        if d <= 0:\n            pred = \"HIGH\"\n            margin = min(abs(d), 60)\n            conf = 50 + 50 * (margin / 60)\n        elif d <= 14:\n            pred = \"MEDIUM\"\n            conf = 50 + 50 * (min(d, 14 - d) / 7)\n        else:\n            pred = \"LOW\"\n            conf = 50 + 50 * (min(d - 14, 60) / 60)\n\n        preds.append(pred)\n        confs.append(round(min(max(conf, 50.0), 100.0), 2))\n    return np.array(preds), np.array(confs)\n\nrb_preds, rb_confs = run_rule_based(X_test)\nrb_acc = (rb_preds == y_test).mean() * 100\nprint(f\"✓ Model 1: Rule-Based Evaluation Complete\")\nprint(f\"  • Test Accuracy: {rb_acc:.2f}%\")\nprint(f\"  • Average Margin Confidence: {rb_confs.mean():.2f}%\")",
            ],
            'cell_3' => [
                'title'       => 'Cell 3: Model 2 — Decision Tree Classifier (CART)',
                'short_title' => '2. Decision Tree',
                'badge'       => 'Decision Tree (CART)',
                'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon'        => 'git-fork',
                'description' => 'Configurable CART tree with Gini impurity criterion and tree depth controls.',
                'code'        => "# [Cell 3] Model 2: Decision Tree Classifier (CART)\nfrom sklearn.tree import DecisionTreeClassifier\n\n# Hyperparameters: adjust max_depth, criterion, min_samples_split\ndt_model = DecisionTreeClassifier(\n    criterion='gini',\n    max_depth=6,\n    min_samples_split=2,\n    random_state=42\n)\n\ndt_model.fit(X_train_pp, y_train)\ndt_preds = dt_model.predict(X_test_pp)\ndt_confs = dt_model.predict_proba(X_test_pp).max(axis=1) * 100\ndt_acc = (dt_preds == y_test).mean() * 100\n\nprint(f\"✓ Model 2: Decision Tree Trained (Depth: {dt_model.get_depth()}, Leaves: {dt_model.get_n_leaves()})\")\nprint(f\"  • Test Accuracy: {dt_acc:.2f}%\")\nprint(f\"  • Mean Leaf Confidence: {dt_confs.mean():.2f}%\")",
            ],
            'cell_4' => [
                'title'       => 'Cell 4: Model 3 — Random Forest Ensemble (Bagging)',
                'short_title' => '3. Random Forest',
                'badge'       => 'Ensemble (100 Trees)',
                'badge_color' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'icon'        => 'layers',
                'description' => '100 Bootstrap trees ensemble with Out-of-Bag (OOB) scoring.',
                'code'        => "# [Cell 4] Model 3: Random Forest Classifier (100 Trees Ensemble)\nfrom sklearn.ensemble import RandomForestClassifier\n\n# Hyperparameters: adjust n_estimators, max_features, max_depth\nrf_model = RandomForestClassifier(\n    n_estimators=100,\n    max_features='sqrt',\n    max_depth=8,\n    oob_score=True,\n    random_state=42,\n    n_jobs=-1\n)\n\nrf_model.fit(X_train_pp, y_train)\nrf_preds = rf_model.predict(X_test_pp)\nrf_confs = rf_model.predict_proba(X_test_pp).max(axis=1) * 100\nrf_acc = (rf_preds == y_test).mean() * 100\n\nprint(f\"✓ Model 3: Random Forest Ensemble Fitted (100 Estimators)\")\nprint(f\"  • Test Accuracy: {rf_acc:.2f}%\")\nprint(f\"  • OOB (Out-of-Bag) Score: {rf_model.oob_score_ * 100:.2f}%\")\nprint(f\"  • Mean Vote Confidence: {rf_confs.mean():.2f}%\")",
            ],
            'cell_5' => [
                'title'       => 'Cell 5: Model 4 — Support Vector Machine (RBF Kernel)',
                'short_title' => '4. SVM (RBF)',
                'badge'       => 'Kernel SVM + Platt',
                'badge_color' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon'        => 'zap',
                'description' => 'Radial Basis Function SVM with Platt probability scaling and C regularization.',
                'code'        => "# [Cell 5] Model 4: Support Vector Machine (RBF Kernel + Platt Probability)\nfrom sklearn.svm import SVC\nfrom sklearn.pipeline import Pipeline\n\n# Hyperparameters: C (regularization), gamma (kernel coefficient)\nsvm_model = SVC(\n    kernel='rbf',\n    C=1.0,\n    gamma='scale',\n    probability=True,\n    decision_function_shape='ovo',\n    random_state=42\n)\n\nsvm_model.fit(X_train_pp, y_train)\nsvm_preds = svm_model.predict(X_test_pp)\nsvm_confs = svm_model.predict_proba(X_test_pp).max(axis=1) * 100\nsvm_acc = (svm_preds == y_test).mean() * 100\n\nprint(f\"✓ Model 4: SVM (RBF) Fitted (Support Vectors: {sum(svm_model.n_support_)})\")\nprint(f\"  • Test Accuracy: {svm_acc:.2f}%\")\nprint(f\"  • Mean Platt Confidence: {svm_confs.mean():.2f}%\")",
            ],
            'cell_dev' => [
                'title'       => 'Cell 6 (Sandbox): DevMode Trial & Real-Time Verification Sandbox',
                'short_title' => '🧪 DevMode Trial',
                'badge'       => '🧪 Sandbox Trial',
                'badge_color' => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
                'icon'        => 'flask-conical',
                'description' => 'Freeform Python 3.13 scratchpad. Test live verification experiments or write custom trial code.',
                'code'        => "# [DevMode Trial Sandbox] Live Execution Verification & Custom Experimentation\n# ═════════════════════════════════════════════════════════════════════════════\n# This cell is your freeform testing scratchpad! You can wipe this code,\n# test custom algorithms, or run the 4 quick live experiments below.\n# All pipeline objects (df, X_train_pp, X_test_pp, y_train, y_test) are available.\n\nimport datetime\nimport time\nimport numpy as np\nimport pandas as pd\n\nprint(\"=\" * 70)\nprint(\"🧪 DEVMODE LIVE PYTHON 3.13 KERNEL SANDBOX\")\nprint(\"=\" * 70)\n\n# --- EXPERIMENT 1: Live System Clock (Proves real-time execution) ---\nlive_now = datetime.datetime.now()\nprint(f\"✓ [Exp 1] Live Engine Clock       : {live_now.strftime('%Y-%m-%d %H:%M:%S.%f')[:-3]}\")\n\n# --- EXPERIMENT 2: Variable & Telemetry Inspection from Ingestion ---\nif 'df' in globals():\n    print(f\"✓ [Exp 2] Active Fleet Records    : {len(df)} vehicles loaded in memory\")\nif 'X_train_pp' in globals():\n    print(f\"✓ [Exp 2] Preprocessed Feature Dim: X_train = {X_train_pp.shape}, X_test = {X_test_pp.shape}\")\n\n# --- EXPERIMENT 3: Artificial Sleep Delay (Uncomment to test execution delay) ---\n# print(\"  [Exp 3] Simulating 2.0 second Python sleep...\")\n# time.sleep(2.0)\n# print(\"✓ [Exp 3] 2-second sleep complete!\")\n\n# --- EXPERIMENT 4: Intentional Error Traceback (Uncomment to see live Python exception) ---\n# print(\"  [Exp 4] Triggering intentional ZeroDivisionError...\")\n# 1 / 0\n\nprint(\"=\" * 70)\nprint(\"💡 BLANK SLATE: You can edit or replace everything here with your own code!\")",
            ],
            'cell_6' => [
                'title'       => 'Cell 6: Evaluation & Comparative Leaderboard',
                'short_title' => 'Leaderboard',
                'badge'       => 'Leaderboard Benchmark',
                'badge_color' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon'        => 'bar-chart-2',
                'description' => 'Computes test metrics, macro F1, HIGH urgency recall, and multi-model consensus.',
                'code'        => "# [Cell 6] Multi-Model Evaluation & Leaderboard Benchmark\nfrom sklearn.metrics import precision_score, recall_score, f1_score, confusion_matrix\n\nmodels_dict = {\n    'Rule-Based Baseline': (rb_preds, rb_confs),\n    'Decision Tree (CART)': (dt_preds, dt_confs),\n    'Random Forest (100 Trees)': (rf_preds, rf_confs),\n    'SVM (RBF Kernel)': (svm_preds, svm_confs),\n}\n\n# Automatically integrate any dynamically added models registered in EXTRA_MODELS\nif 'EXTRA_MODELS' in globals() and isinstance(EXTRA_MODELS, dict):\n    models_dict.update(EXTRA_MODELS)\n\nprint(\"=\" * 74)\nprint(f\"{'ALGORITHM':<28} | {'ACCURACY':<10} | {'MACRO F1':<10} | {'HIGH RECALL':<12} | {'MEAN CONF'}\")\nprint(\"=\" * 74)\n\nfor name, (preds, confs) in models_dict.items():\n    acc = (preds == y_test).mean() * 100\n    f1 = f1_score(y_test, preds, average='macro', zero_division=0) * 100\n    rec = recall_score(y_test, preds, average=None, labels=['LOW', 'MEDIUM', 'HIGH'], zero_division=0)\n    high_rec = rec[2] * 100 if len(rec) > 2 else 0\n    print(f\"{name:<28} | {acc:>8.2f}% | {f1:>8.2f}% | {high_rec:>10.2f}% | {confs.mean():>8.2f}%\")\n\nprint(\"=\" * 74)\n\n# Compute Multi-Model Consensus on Test Set\nall_model_preds = [preds for preds, confs in models_dict.values()]\nnum_models = len(models_dict)\nagreements = []\nfor i in range(len(y_test)):\n    votes = [p[i] for p in all_model_preds]\n    agree_count = max(votes.count(v) for v in set(votes))\n    agreements.append(agree_count)\n\nprint(f\"\\n✓ Multi-Model Consensus Breakdown ({num_models} Models, N = {len(y_test)}):\")\nprint(f\"  • {num_models}/{num_models} Unanimous Agreement: {agreements.count(num_models)} ({agreements.count(num_models)/len(y_test)*100:.1f}%)\")\nif num_models >= 3:\n    maj_thresh = (num_models // 2) + 1\n    maj_count = sum(1 for a in agreements if a >= maj_thresh and a < num_models)\n    print(f\"  • Majority (>= {maj_thresh}/{num_models}):          {maj_count} ({maj_count/len(y_test)*100:.1f}%)\")\n    split_count = sum(1 for a in agreements if a < maj_thresh)\n    print(f\"  • Split / Contested (< {maj_thresh}):       {split_count} ({split_count/len(y_test)*100:.1f}%)\")",
            ],
        ];
    }
}

