<?php

namespace Tests\Feature;

use App\Models\MaintenanceAlert;
use App\Models\MlDataset;
use App\Models\MlExperiment;
use App\Models\MlPredictionComparison;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PredictiveAnalyticsSubmoduleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $staff;
    protected User $individual;
    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->individual = User::factory()->create([
            'role' => 'individual',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $f) {
            if (file_exists($f)) {
                @unlink($f);
            }
        }

        parent::tearDown();
    }

    public function test_non_admin_cannot_access_predictive_analytics_submodule(): void
    {
        $this->actingAs($this->individual)
            ->get(route('admin.predictive-analytics.index'))
            ->assertStatus(403);

        $this->actingAs($this->staff)
            ->get(route('admin.predictive-analytics.index'))
            ->assertStatus(403);
    }

    public function test_admin_can_preview_dataset_and_receive_suggested_mappings(): void
    {
        $csvContent = "VehicleReg,JobType,RecordYear,DaysElapsed,KmElapsed,IntervalKm,IntervalMonths,DailyMileage,Severity,Age,TotalKm,Fuel,TargetUrgency\n"
                    . "ABC1234,Oil Change,2026,190,11000,10000,6,50,1.2,3,45000,Petrol,HIGH\n"
                    . "XYZ5678,Brake Check,2026,45,2000,10000,6,30,1.0,2,22000,Diesel,LOW\n";

        $file = UploadedFile::fake()->createWithContent('test_fleet.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.predictive-analytics.preview-dataset'), [
                'dataset_file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'     => 'success',
                'total_rows' => 2,
            ])
            ->assertJsonStructure([
                'status',
                'headers',
                'preview_rows',
                'total_rows',
                'suggested_mapping',
                'target_fields',
            ]);
    }

    public function test_admin_can_import_and_preprocess_dataset_with_column_mapping(): void
    {
        $csvContent = "reg_no,job,days,km,rec_km,rec_months,daily,urgency\n"
                    . "WA1234B,Oil Service,200,12000,10000,6,50,HIGH\n"
                    . "WB5678C,Tire Rotation,30,1500,10000,6,35,LOW\n"
                    . "WC9012D,Brake Service,120,7000,10000,6,40,MEDIUM\n";

        $file = UploadedFile::fake()->createWithContent('custom_fleet.csv', $csvContent);

        $mapping = [
            'plate_number'       => 'reg_no',
            'service_type'       => 'job',
            'days_since_service' => 'days',
            'km_since_service'   => 'km',
            'interval_km'        => 'rec_km',
            'interval_months'    => 'rec_months',
            'daily_km'           => 'daily',
            'urgency_label'      => 'urgency',
        ];

        $response = $this->actingAs($this->admin)
            ->from(route('admin.predictive-analytics.index', ['tab' => 'data']))
            ->post(route('admin.predictive-analytics.import-mapped'), [
                'dataset_file'   => $file,
                'dataset_name'   => 'Custom Mapped Test Fleet',
                'source_type'    => 'synthetic',
                'column_mapping' => $mapping,
            ]);

        $response->assertRedirect(route('admin.predictive-analytics.index', ['tab' => 'data']));

        $dataset = MlDataset::where('name', 'Custom Mapped Test Fleet')->first();
        $this->assertNotNull($dataset);
        $this->assertEquals(3, $dataset->row_count);
        $this->assertFileExists($dataset->absolute_path);
        $this->createdFiles[] = $dataset->absolute_path;
    }

    public function test_admin_can_run_in_system_experiment_and_generate_metrics_including_ensemble(): void
    {
        // Create sample dataset file on disk
        $csvContent = "plate_number,service_type,scan_year,days_since_service,km_since_service,interval_km,interval_months,daily_km,terrain_severity,vehicle_age,total_mileage,fuel_type,urgency_label\n";
        for ($i = 1; $i <= 20; $i++) {
            $urgency = ($i % 3 === 0) ? 'HIGH' : (($i % 3 === 1) ? 'LOW' : 'MEDIUM');
            $days = $urgency === 'HIGH' ? 220 : ($urgency === 'MEDIUM' ? 120 : 30);
            $km = $urgency === 'HIGH' ? 14000 : ($urgency === 'MEDIUM' ? 8000 : 2000);
            $csvContent .= sprintf("V-%04d,Scheduled Maintenance,2026,%d,%d,10000,6,45,1.0,4,50000,Petrol,%s\n", $i, $days, $km, $urgency);
        }

        $relPath = 'ml_datasets/pipeline_test_' . uniqid() . '.csv';
        $fullPath = storage_path('app/' . $relPath);
        @mkdir(dirname($fullPath), 0755, true);
        file_put_contents($fullPath, $csvContent);
        $this->createdFiles[] = $fullPath;

        $dataset = MlDataset::create([
            'name'                 => 'Pipeline Experiment Benchmark',
            'source_type'          => 'synthetic',
            'file_path'            => $relPath,
            'row_count'            => 20,
            'feature_mapping_json' => [
                'features' => ['days_since_service', 'km_since_service', 'interval_km', 'interval_months', 'daily_km', 'terrain_severity', 'total_mileage', 'vehicle_age', 'fuel_type'],
                'target'   => 'urgency_label',
                'noise'    => 0.08,
                'missing'  => 0.0,
            ],
            'uploaded_by'          => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.predictive-analytics.index', ['tab' => 'train']))
            ->post(route('admin.predictive-analytics.run'), [
                'dataset_id'  => $dataset->id,
                'algorithms'  => ['rule_based', 'decision_tree', 'random_forest', 'svm', 'logistic_regression'],
                'split_ratio' => 0.80,
                'seed'        => 42,
            ]);

        $response->assertRedirect(route('admin.predictive-analytics.index', ['tab' => 'train']));

        // Verify experiment record was created
        $experiment = MlExperiment::where('dataset_id', $dataset->id)->latest()->first();
        $this->assertNotNull($experiment);
        $this->assertEquals('completed', $experiment->status);

        // Verify metrics were generated for all 5 models + ensemble
        $this->assertDatabaseHas('ml_model_metrics', [
            'experiment_id' => $experiment->id,
            'algorithm'     => 'ensemble',
        ]);
        $this->assertDatabaseHas('ml_model_metrics', [
            'experiment_id' => $experiment->id,
            'algorithm'     => 'rule_based',
        ]);
        $this->assertDatabaseHas('ml_model_metrics', [
            'experiment_id' => $experiment->id,
            'algorithm'     => 'decision_tree',
        ]);
        $this->assertDatabaseHas('ml_model_metrics', [
            'experiment_id' => $experiment->id,
            'algorithm'     => 'random_forest',
        ]);
        $this->assertDatabaseHas('ml_model_metrics', [
            'experiment_id' => $experiment->id,
            'algorithm'     => 'svm',
        ]);
        $this->assertDatabaseHas('ml_model_metrics', [
            'experiment_id' => $experiment->id,
            'algorithm'     => 'logistic_regression',
        ]);

        // Verify row-by-row comparisons exist
        $this->assertGreaterThan(0, $experiment->predictions()->count());
    }

    public function test_admin_can_export_experiment_predictions_to_csv(): void
    {
        $dataset = MlDataset::create([
            'name'        => 'Export Dataset',
            'source_type' => 'synthetic',
            'file_path'   => 'ml_datasets/dummy.csv',
            'row_count'   => 1,
            'uploaded_by' => $this->admin->id,
        ]);

        $experiment = MlExperiment::create([
            'dataset_id'  => $dataset->id,
            'run_name'    => 'Test Export Run',
            'split_ratio' => 0.80,
            'status'      => 'completed',
        ]);

        MlPredictionComparison::create([
            'experiment_id'             => $experiment->id,
            'vehicle_identifier'        => 'WXY1234',
            'service_type'              => 'Brake Inspection',
            'scan_year'                 => 2026,
            'ground_truth_label'        => 'HIGH',
            'rb_prediction'             => 'HIGH',
            'rb_confidence'             => 95.0,
            'dt_prediction'             => 'HIGH',
            'dt_confidence'             => 90.0,
            'rf_prediction'             => 'HIGH',
            'rf_confidence'             => 92.0,
            'svm_prediction'            => 'HIGH',
            'svm_confidence'            => 88.0,
            'lr_prediction'             => 'HIGH',
            'lr_confidence'             => 85.0,
            'consensus_label'           => 'HIGH',
            'agreement_rate'            => '5/5',
            'features_json'             => json_encode(['days_since_service' => 200]),
            'reasoning_json'            => json_encode([]),
            'staff_verification_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.researcher-panel.export-csv', $experiment));

        $response->assertStatus(200);
        $this->assertTrue(str_contains((string) $response->headers->get('content-disposition'), 'predictive_report_exp_'));
    }

    public function test_admin_can_push_ensemble_consensus_prediction_to_maintenance_alerts(): void
    {
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'TEST-ALERT-01',
        ]);

        $dataset = MlDataset::create([
            'name'        => 'Push Dataset',
            'source_type' => 'synthetic',
            'file_path'   => 'ml_datasets/dummy.csv',
            'row_count'   => 1,
            'uploaded_by' => $this->admin->id,
        ]);

        $experiment = MlExperiment::create([
            'dataset_id'  => $dataset->id,
            'run_name'    => 'Push Test Experiment',
            'split_ratio' => 0.80,
            'status'      => 'completed',
        ]);

        $prediction = MlPredictionComparison::create([
            'experiment_id'             => $experiment->id,
            'vehicle_identifier'        => $vehicle->plate_number,
            'service_type'              => 'Major Engine Overhaul',
            'scan_year'                 => 2026,
            'ground_truth_label'        => 'HIGH',
            'rb_prediction'             => 'HIGH',
            'rb_confidence'             => 90.0,
            'dt_prediction'             => 'HIGH',
            'dt_confidence'             => 88.0,
            'rf_prediction'             => 'HIGH',
            'rf_confidence'             => 94.0,
            'svm_prediction'            => 'HIGH',
            'svm_confidence'            => 86.0,
            'lr_prediction'             => 'HIGH',
            'lr_confidence'             => 84.0,
            'consensus_label'           => 'HIGH',
            'agreement_rate'            => '5/5',
            'features_json'             => json_encode(['days_since_service' => 210]),
            'pushed_to_alerts'          => false,
            'staff_verification_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.predictive-analytics.predictions.push', $prediction), [
                'algorithm' => 'ensemble',
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('maintenance_alerts', [
            'vehicle_id'        => $vehicle->id,
            'predicted_by_algo' => 'ensemble',
            'urgency'           => 'high',
            'agreement_rate'    => '5/5',
        ]);

        $prediction->refresh();
        $this->assertTrue($prediction->pushed_to_alerts);
        $this->assertEquals('ensemble', $prediction->pushed_algo);
    }

    public function test_staff_can_record_ground_truth_verification_feedback(): void
    {
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'VERIFY-001',
        ]);

        $alert = MaintenanceAlert::create([
            'vehicle_id'        => $vehicle->id,
            'source'            => 'predicted',
            'alert_type'        => 'Brake Service',
            'urgency'           => 'high',
            'recommendation'    => 'Predicted HIGH urgency',
            'predicted_by_algo' => 'ensemble',
            'confidence_score'  => 92.5,
            'is_read'           => false,
        ]);

        $dataset = MlDataset::create([
            'name'        => 'Verify Dataset',
            'source_type' => 'synthetic',
            'file_path'   => 'ml_datasets/dummy.csv',
            'row_count'   => 1,
            'uploaded_by' => $this->admin->id,
        ]);

        $experiment = MlExperiment::create([
            'dataset_id'  => $dataset->id,
            'run_name'    => 'Verification Audit Test',
            'split_ratio' => 0.80,
            'status'      => 'completed',
        ]);

        $prediction = MlPredictionComparison::create([
            'experiment_id'             => $experiment->id,
            'vehicle_identifier'        => $vehicle->plate_number,
            'service_type'              => 'Brake Service',
            'consensus_label'           => 'HIGH',
            'agreement_rate'            => '5/5',
            'pushed_to_alerts'          => true,
            'pushed_alert_id'           => $alert->id,
            'staff_verification_status' => 'pending',
        ]);

        // Staff records ground truth verification
        $response = $this->actingAs($this->staff)
            ->post(route('predictions.verify', $prediction), [
                'status' => 'verified_correct',
                'notes'  => 'Physical inspection confirms severe brake pad thinning.',
            ]);

        $response->assertSessionHas('success');

        $prediction->refresh();
        $this->assertEquals('verified_correct', $prediction->staff_verification_status);
        $this->assertEquals($this->staff->id, $prediction->verified_by);
        $this->assertEquals('Physical inspection confirms severe brake pad thinning.', $prediction->staff_notes);

        // Verify pushed live alert was kept in sync
        $alert->refresh();
        $this->assertEquals('verified', $alert->ml_staff_verified);
        $this->assertTrue($alert->is_verified);
    }

    public function test_customer_cannot_record_ground_truth_verification(): void
    {
        $dataset = MlDataset::create([
            'name'        => 'Customer 403 Dataset',
            'source_type' => 'synthetic',
            'file_path'   => 'ml_datasets/dummy.csv',
            'row_count'   => 1,
            'uploaded_by' => $this->admin->id,
        ]);

        $experiment = MlExperiment::create([
            'dataset_id'  => $dataset->id,
            'run_name'    => 'Customer 403 Test',
            'split_ratio' => 0.80,
            'status'      => 'completed',
        ]);

        $prediction = MlPredictionComparison::create([
            'experiment_id'             => $experiment->id,
            'vehicle_identifier'        => 'TEST-403',
            'service_type'              => 'Oil Service',
            'consensus_label'           => 'LOW',
            'agreement_rate'            => '5/5',
            'staff_verification_status' => 'pending',
        ]);

        // Individual customer tries to verify -> 403 Forbidden
        $response = $this->actingAs($this->individual)
            ->post(route('predictions.verify', $prediction), [
                'status' => 'verified_correct',
                'notes'  => 'I am a customer trying to verify.',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_generate_synthetic_datasets_with_custom_noise_selector(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.predictive-analytics.generate'), [
                'rows'    => 120,
                'noise'   => 0.15,
                'missing' => 0.05,
                'bias'    => 'mountain',
                'seed'    => 101,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $latestDataset = MlDataset::where('name', 'like', '%(15% noise)%')->latest()->first();
        $this->assertNotNull($latestDataset);
        $this->assertEquals(120, $latestDataset->row_count);
        $this->assertEquals(0.15, $latestDataset->feature_mapping_json['noise']);
        $this->assertEquals('mountain', $latestDataset->feature_mapping_json['bias']);
    }
}
