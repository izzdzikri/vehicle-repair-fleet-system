<?php

namespace Database\Seeders;

use App\Models\MlDataset;
use App\Models\MlExperiment;
use App\Models\MlModelMetric;
use App\Models\MlPredictionComparison;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MlFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : 1;

        // 1. Ensure Default ML Datasets Exist
        $kaggleDataset = MlDataset::firstOrCreate(
            ['name' => 'Kaggle Fleet Maintenance Benchmark'],
            [
                'source_type'          => 'kaggle_benchmark',
                'file_path'            => 'ml_datasets/kaggle_benchmark_data.csv',
                'row_count'            => 600,
                'feature_mapping_json' => ['features' => ['days_since_service', 'km_since_service', 'interval_km', 'interval_months', 'daily_km', 'terrain_severity', 'total_mileage', 'vehicle_age', 'fuel_type', 'owner_type'], 'target' => 'urgency_label'],
                'uploaded_by'          => $adminId,
            ]
        );

        MlDataset::firstOrCreate(
            ['name' => 'Synthetic Fleet Wear Simulation'],
            [
                'source_type'          => 'synthetic',
                'file_path'            => 'ml_datasets/synthetic_fleet_data.csv',
                'row_count'            => 600,
                'feature_mapping_json' => ['features' => ['days_since_service', 'km_since_service', 'interval_km', 'interval_months', 'daily_km', 'terrain_severity', 'total_mileage', 'vehicle_age', 'fuel_type', 'owner_type'], 'target' => 'urgency_label'],
                'uploaded_by'          => $adminId,
            ]
        );

        // 2. Load Seed Data JSON if Available
        $seedFile = database_path('seeders/data/ml_benchmark_seed.json');
        if (! File::exists($seedFile)) {
            return;
        }

        $data = json_decode(File::get($seedFile), true);
        if (! $data || empty($data['experiment'])) {
            return;
        }

        $expData = $data['experiment'];
        $expRunName = $expData['run_name'] ?? 'Benchmark Run #1 — Kaggle Benchmark 4-Model Evaluation';

        // 3. Create Benchmark Experiment if not already present
        $experiment = MlExperiment::where('run_name', $expRunName)->first();
        if (! $experiment) {
            $experiment = MlExperiment::create([
                'dataset_id'        => $kaggleDataset->id,
                'run_name'          => $expRunName,
                'split_ratio'       => $expData['split_ratio'] ?? 0.8,
                'cv_folds'          => $expData['cv_folds'] ?? 5,
                'status'            => 'completed',
                'summary_json'      => $expData['summary_json'] ?? [],
                'hyperparams_json'  => $expData['hyperparams_json'] ?? ['rf_n_estimators' => 100, 'svm_c' => 1],
                'run_by'            => $adminId,
            ]);
        }

        // 4. Seed Model Metrics
        if ($experiment && ! empty($expData['metrics'])) {
            foreach ($expData['metrics'] as $metric) {
                MlModelMetric::firstOrCreate(
                    [
                        'experiment_id' => $experiment->id,
                        'algorithm'     => $metric['algorithm'],
                    ],
                    [
                        'accuracy'              => $metric['accuracy'],
                        'precision_macro'       => $metric['precision_macro'] ?? null,
                        'recall_macro'          => $metric['recall_macro'] ?? null,
                        'f1_macro'              => $metric['f1_macro'] ?? null,
                        'high_urgency_recall'   => $metric['high_urgency_recall'] ?? null,
                        'confusion_matrix_json' => $metric['confusion_matrix_json'] ?? null,
                        'yearly_accuracy_json'  => $metric['yearly_accuracy_json'] ?? null,
                        'extra_meta_json'       => $metric['extra_meta_json'] ?? null,
                    ]
                );
            }
        }

        // 5. Seed Prediction Comparisons (120 test rows with XAI reasoning)
        if ($experiment && ! empty($data['predictions']) && MlPredictionComparison::where('experiment_id', $experiment->id)->count() === 0) {
            $now = now();
            $records = [];
            foreach ($data['predictions'] as $pred) {
                $records[] = [
                    'experiment_id'             => $experiment->id,
                    'vehicle_identifier'        => $pred['vehicle_identifier'],
                    'service_type'              => $pred['service_type'] ?? 'Scheduled Maintenance',
                    'scan_year'                 => $pred['scan_year'] ?? 2026,
                    'ground_truth_label'        => $pred['ground_truth_label'] ?? null,
                    'rb_prediction'             => $pred['rb_prediction'] ?? 'LOW',
                    'rb_confidence'             => $pred['rb_confidence'] ?? 80.0,
                    'rb_days_remaining'         => $pred['rb_days_remaining'] ?? 30,
                    'dt_prediction'             => $pred['dt_prediction'] ?? 'LOW',
                    'dt_confidence'             => $pred['dt_confidence'] ?? 85.0,
                    'rf_prediction'             => $pred['rf_prediction'] ?? 'LOW',
                    'rf_confidence'             => $pred['rf_confidence'] ?? 88.0,
                    'svm_prediction'            => $pred['svm_prediction'] ?? 'LOW',
                    'svm_confidence'            => $pred['svm_confidence'] ?? 82.0,
                    'consensus_label'           => $pred['consensus_label'] ?? 'LOW',
                    'agreement_rate'            => $pred['agreement_rate'] ?? '4/4',
                    'features_json'             => is_array($pred['features_json']) ? json_encode($pred['features_json']) : $pred['features_json'],
                    'reasoning_json'            => is_array($pred['reasoning_json'] ?? null) ? json_encode($pred['reasoning_json']) : ($pred['reasoning_json'] ?? null),
                    'pushed_to_alerts'          => false,
                    'pushed_alert_id'           => null,
                    'pushed_algo'               => null,
                    'staff_verification_status' => $pred['staff_verification_status'] ?? 'pending',
                    'verified_by'               => null,
                    'verified_at'               => null,
                    'staff_notes'               => null,
                    'created_at'                => $now,
                    'updated_at'                => $now,
                ];
            }

            foreach (array_chunk($records, 50) as $chunk) {
                MlPredictionComparison::insert($chunk);
            }
        }
    }
}
