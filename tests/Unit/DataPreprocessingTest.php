<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Prediction\DataPreprocessingService;
use App\Services\Prediction\DatasetImportService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DataPreprocessingTest extends TestCase
{
    public function test_data_preprocessing_imputes_missing_numeric_with_median_and_categorical_with_mode(): void
    {
        $service = new DataPreprocessingService();

        $rows = [
            ['days_since_service' => 10,  'km_since_service' => 500,  'fuel_type' => 'petrol', 'terrain_severity' => 1.0],
            ['days_since_service' => 20,  'km_since_service' => 1000, 'fuel_type' => 'petrol', 'terrain_severity' => 1.0],
            ['days_since_service' => 30,  'km_since_service' => 1500, 'fuel_type' => 'diesel', 'terrain_severity' => 1.2],
            // Row with missing values
            ['days_since_service' => null, 'km_since_service' => null, 'fuel_type' => '',       'terrain_severity' => null],
        ];

        $result = $service->imputeMissingValues($rows);
        $data = $result['data'];
        $report = $result['report'];

        // 4th row had null days_since_service; median of [10, 20, 30] is 20
        $this->assertEquals(20.0, $data[3]['days_since_service']);
        // 4th row had null km_since_service; median of [500, 1000, 1500] is 1000
        $this->assertEquals(1000.0, $data[3]['km_since_service']);
        // fuel_type mode is 'petrol' (2 petrol vs 1 diesel)
        $this->assertEquals('petrol', $data[3]['fuel_type']);

        // Check report contains imputation entries
        $this->assertArrayHasKey('days_since_service', $report);
        $this->assertEquals(1, $report['days_since_service']['missing_count']);
        $this->assertEquals(20.0, $report['days_since_service']['imputed_value']);
    }

    public function test_data_preprocessing_clips_outliers_using_iqr(): void
    {
        $service = new DataPreprocessingService();

        // 10 normal samples plus 1 extreme outlier spike
        $rows = [
            ['km_since_service' => 1000, 'days_since_service' => 30],
            ['km_since_service' => 1100, 'days_since_service' => 32],
            ['km_since_service' => 1050, 'days_since_service' => 31],
            ['km_since_service' => 1200, 'days_since_service' => 35],
            ['km_since_service' => 950,  'days_since_service' => 29],
            ['km_since_service' => 1020, 'days_since_service' => 30],
            ['km_since_service' => 1080, 'days_since_service' => 33],
            ['km_since_service' => 1150, 'days_since_service' => 34],
            // Extreme outlier spike: 999,999 km
            ['km_since_service' => 999999, 'days_since_service' => 30],
        ];

        $result = $service->clipOutliers($rows);
        $data = $result['data'];
        $report = $result['report'];

        $lastRowKm = $data[8]['km_since_service'];
        // Extreme outlier must be clipped far below 999,999
        $this->assertLessThan(5000.0, $lastRowKm);
        $this->assertArrayHasKey('km_since_service', $report);
        $this->assertGreaterThan(0, $report['km_since_service']['clipped_upper']);
    }

    public function test_dataset_import_service_auto_suggests_mapping(): void
    {
        $importer = new DatasetImportService();

        $uploadedHeaders = [
            'Car_Plate',
            'Days_Elapsed',
            'Mileage_Since_Service',
            'Target_KM',
            'Rec_Months',
            'Daily_Usage',
            'Operating_Environment',
            'Car_Age',
            'Odometer',
            'Fuel',
            'Customer_Type',
            'Status',
        ];

        $suggested = $importer->autoSuggestMapping($uploadedHeaders);

        $this->assertEquals('Car_Plate', $suggested['plate_number']);
        $this->assertEquals('Days_Elapsed', $suggested['days_since_service']);
        $this->assertEquals('Mileage_Since_Service', $suggested['km_since_service']);
        $this->assertEquals('Target_KM', $suggested['interval_km']);
        $this->assertEquals('Rec_Months', $suggested['interval_months']);
        $this->assertEquals('Daily_Usage', $suggested['daily_km']);
        $this->assertEquals('Operating_Environment', $suggested['terrain_severity']);
        $this->assertEquals('Car_Age', $suggested['vehicle_age']);
        $this->assertEquals('Odometer', $suggested['total_mileage']);
        $this->assertEquals('Fuel', $suggested['fuel_type']);
        $this->assertEquals('Customer_Type', $suggested['owner_type']);
        $this->assertEquals('Status', $suggested['urgency_label']);
    }

    public function test_simulate_maintenance_artisan_command_with_flags_is_reproducible(): void
    {
        $tempPath1 = storage_path('app/ml_datasets/test_sim_1.csv');
        $tempPath2 = storage_path('app/ml_datasets/test_sim_2.csv');

        // Run twice with the exact same seed (42)
        Artisan::call('maintenance:simulate', [
            '--rows'    => 50,
            '--seed'    => 42,
            '--noise'   => 0.10,
            '--missing' => 0.05,
            '--bias'    => 'mountain',
            '--export'  => $tempPath1,
        ]);

        Artisan::call('maintenance:simulate', [
            '--rows'    => 50,
            '--seed'    => 42,
            '--noise'   => 0.10,
            '--missing' => 0.05,
            '--bias'    => 'mountain',
            '--export'  => $tempPath2,
        ]);

        $this->assertFileExists($tempPath1);
        $this->assertFileExists($tempPath2);

        // Files must be identical due to deterministic seed
        $this->assertEquals(md5_file($tempPath1), md5_file($tempPath2));

        // Cleanup
        @unlink($tempPath1);
        @unlink($tempPath2);
    }
}
