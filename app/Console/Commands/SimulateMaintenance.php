<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MlDataset;
use App\Models\User;
use App\Services\Prediction\DataPreprocessingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SimulateMaintenance extends Command
{
    protected $signature = 'maintenance:simulate
                            {--rows=600 : Number of fleet degradation records to generate}
                            {--noise=0.08 : Gaussian jitter standard deviation on numeric telemetry}
                            {--missing=0.0 : Fraction of random null values to inject (0.0 to 0.5)}
                            {--imbalance=0.0 : Urgency class imbalance skew (-1.0 to 1.0, 0 for balanced)}
                            {--bias=none : Bias fleet toward specific operating condition (mountain, urban, rural, highway, corporate, heavy_wear)}
                            {--seed=42 : Deterministic random generator seed}
                            {--ablation=none : Feature ablation mode (no_terrain, no_daily_km, no_odometer, none)}
                            {--export= : Destination CSV file path (defaults to storage/app/ml_datasets/synthetic_fleet_data.csv)}
                            {--dataset-name= : Register as MlDataset in database with this title}
                            {--preprocess : Run generated records through DataPreprocessingService immediately}';

    protected $description = 'Generate synthetic fleet wear datasets with configurable Gaussian noise, missing nulls, class imbalance, and sensor ablation.';

    protected array $terrainSeverity = [
        'urban'    => 1.15,
        'highway'  => 0.90,
        'rural'    => 1.20,
        'mountain' => 1.30,
        'mixed'    => 1.00,
    ];

    protected array $serviceTypes = [
        'Oil & Filter Change'       => ['km' => 5000,  'months' => 6],
        'Full Engine Service'       => ['km' => 10000, 'months' => 12],
        'Tyre Rotation & Balancing' => ['km' => 10000, 'months' => 6],
        'Air Conditioning Service'  => ['km' => 20000, 'months' => 24],
        'Suspension Inspection'     => ['km' => 25000, 'months' => 24],
        'Transmission Fluid Change' => ['km' => 40000, 'months' => 24],
        'Full Vehicle Inspection'   => ['km' => 30000, 'months' => 12],
    ];

    protected array $fuelTypes = ['petrol', 'diesel', 'hybrid', 'electric'];
    protected array $ownerTypes = ['individual', 'corporate'];
    protected array $brands = ['Proton', 'Perodua', 'Toyota', 'Honda', 'Hyundai', 'Nissan', 'Mazda', 'Ford'];

    public function handle(DataPreprocessingService $preprocessor): int
    {
        $rows = (int) $this->option('rows');
        $noise = (float) $this->option('noise');
        $missing = (float) $this->option('missing');
        $imbalance = (float) $this->option('imbalance');
        $bias = strtolower((string) $this->option('bias'));
        $seed = (int) $this->option('seed');
        $ablation = strtolower((string) $this->option('ablation'));
        $exportPath = $this->option('export');
        $datasetName = $this->option('dataset-name');
        $doPreprocess = (bool) $this->option('preprocess');

        mt_srand($seed);

        $this->info("Generating {$rows} synthetic fleet records (Seed: {$seed}, Noise: {$noise}, Missing: {$missing}, Imbalance: {$imbalance}, Bias: {$bias}, Ablation: {$ablation})...");

        $records = [];
        $baseDate = Carbon::create(2024, 1, 1);
        $endDate = Carbon::create(2026, 10, 31);
        $totalDaysSpan = (int) $baseDate->diffInDays($endDate);

        $serviceKeys = array_keys($this->serviceTypes);
        $terrainKeys = array_keys($this->terrainSeverity);

        for ($i = 1; $i <= $rows; $i++) {
            $brand = $this->brands[array_rand($this->brands)];
            $plate = sprintf('%s %04d', substr($brand, 0, 3), mt_rand(1000, 9999));
            $fuelType = $this->fuelTypes[array_rand($this->fuelTypes)];
            $ownerType = $this->ownerTypes[array_rand($this->ownerTypes)];
            $vehicleYear = mt_rand(2012, 2024);

            // Apply bias
            $terrain = $terrainKeys[array_rand($terrainKeys)];
            if ($bias === 'mountain') {
                $terrain = 'mountain';
            } elseif ($bias === 'urban') {
                $terrain = 'urban';
            } elseif ($bias === 'rural') {
                $terrain = 'rural';
            } elseif ($bias === 'highway') {
                $terrain = 'highway';
            } elseif ($bias === 'corporate') {
                $ownerType = 'corporate';
            }

            $terrainSev = $this->terrainSeverity[$terrain];

            $scanDate = $baseDate->copy()->addDays(mt_rand(0, $totalDaysSpan));
            $year = (int) $scanDate->year;
            $vehicleAge = max(1, $year - $vehicleYear);

            // Base daily usage
            $baseDailyKm = ($ownerType === 'corporate') ? 60.0 : 35.0;
            if ($bias === 'heavy_wear') {
                $baseDailyKm = 85.0;
            }

            // Apply Gaussian jitter to daily km
            $dailyKm = $baseDailyKm * (1.0 + $this->gaussianRandom() * $noise);
            $dailyKm = round(max(10.0, min(180.0, $dailyKm)), 1);

            $serviceType = $serviceKeys[array_rand($serviceKeys)];
            $interval = $this->serviceTypes[$serviceType];
            $intervalKm = $interval['km'];
            $intervalMonths = $interval['months'];

            $totalMileage = (int) round($dailyKm * 365.0 * $vehicleAge * (1.0 + $this->gaussianRandom() * $noise));
            $totalMileage = max(1000, $totalMileage);

            // Elapsed wear percentage
            // Normal range: 0.10 to 1.30. Imbalance skews this distribution
            $pctElapsed = $this->randomUniform(0.10, 1.30);
            if ($imbalance > 0.0) {
                // Skew toward healthy / lower elapsed (more LOW)
                $pctElapsed *= (1.0 - (0.5 * min(1.0, $imbalance)));
            } elseif ($imbalance < 0.0) {
                // Skew toward overdue / higher elapsed (more HIGH)
                $pctElapsed *= (1.0 + (0.8 * min(1.0, abs($imbalance))));
            }

            $kmSince = round($intervalKm * $pctElapsed * (1.0 + $this->gaussianRandom() * $noise));
            $daysSince = (int) round($intervalMonths * 30.44 * $pctElapsed * (1.0 + $this->gaussianRandom() * $noise));

            $kmSince = max(0.0, $kmSince);
            $daysSince = max(0, $daysSince);

            // Calculate ground-truth urgency label
            $effKm = max(1.0, $intervalKm / max(0.1, $terrainSev));
            $remKm = $effKm - $kmSince;
            $daysViaKm = $remKm / max(1.0, $dailyKm);
            $daysViaTime = ($intervalMonths * 30.44) - $daysSince;
            $d = (int) round(min($daysViaKm, $daysViaTime));

            // Boundary noise for edge cases in medium zone (0 < d <= 21)
            if ($d > 0 && $d <= 21 && $noise > 0.0) {
                $d += (int) round($this->gaussianRandom() * ($noise * 30.0));
            }

            $urgency = match (true) {
                $d <= 0  => 'HIGH',
                $d <= 14 => 'MEDIUM',
                default  => 'LOW',
            };

            // Apply sensor ablation
            if ($ablation === 'no_terrain') {
                $terrainSev = 1.0;
            } elseif ($ablation === 'no_daily_km') {
                $dailyKm = 40.0;
            } elseif ($ablation === 'no_odometer') {
                $kmSince = 0.0;
            }

            // Inject random missing values if requested
            $rowRecord = [
                'vehicle_id'         => sprintf('VH%04d', $i),
                'plate_number'       => $plate,
                'service_type'       => $serviceType,
                'scan_year'          => $year,
                'days_since_service' => $this->maybeNullify((float) $daysSince, $missing),
                'km_since_service'   => $this->maybeNullify((float) $kmSince, $missing),
                'interval_km'        => $intervalKm,
                'interval_months'    => $intervalMonths,
                'daily_km'           => $this->maybeNullify((float) $dailyKm, $missing),
                'terrain_severity'   => $this->maybeNullify((float) $terrainSev, $missing),
                'vehicle_age'        => $this->maybeNullify((float) $vehicleAge, $missing),
                'total_mileage'      => $this->maybeNullify((float) $totalMileage, $missing),
                'fuel_type'          => $this->maybeNullify($fuelType, $missing),
                'owner_type'         => $this->maybeNullify($ownerType, $missing),
                'urgency_label'      => $urgency,
            ];

            $records[] = $rowRecord;
        }

        // Optional Immediate Preprocessing
        $preprocessingReport = null;
        if ($doPreprocess) {
            $this->info('Applying DataPreprocessingService (Median Imputation & IQR Outlier Clipping)...');
            $prepResult = $preprocessor->preprocess($records, [
                'impute'        => true,
                'clip_outliers' => true,
                'scale'         => false,
            ]);
            $records = $prepResult['clean_data'];
            $preprocessingReport = $prepResult['report'];
        }

        // Determine destination CSV path
        if (empty($exportPath)) {
            $exportPath = storage_path('app/ml_datasets/synthetic_fleet_data.csv');
        }
        @mkdir(dirname($exportPath), 0755, true);

        // Write CSV
        $fp = fopen($exportPath, 'w');
        if ($fp && ! empty($records)) {
            fputcsv($fp, array_keys($records[0]));
            foreach ($records as $r) {
                fputcsv($fp, $r);
            }
            fclose($fp);
            $this->info("Successfully wrote dataset to [{$exportPath}].");
        }

        // Optionally persist to MlDataset table
        if (! empty($datasetName)) {
            $admin = User::where('role', 'admin')->first();
            $relPath = str_replace(storage_path('app/'), '', $exportPath);

            $ds = MlDataset::updateOrCreate(
                ['name' => $datasetName],
                [
                    'source_type'          => 'synthetic',
                    'file_path'            => $relPath,
                    'row_count'            => count($records),
                    'feature_mapping_json' => ['seed' => $seed, 'noise' => $noise, 'missing' => $missing, 'bias' => $bias, 'ablation' => $ablation],
                    'uploaded_by'          => $admin?->id ?? 1,
                ]
            );
            $this->info("Dataset registered in database as ID #{$ds->id} ('{$ds->name}').");
        }

        // Class breakdown summary
        $classCounts = ['LOW' => 0, 'MEDIUM' => 0, 'HIGH' => 0];
        $missingCount = 0;
        foreach ($records as $r) {
            $lbl = $r['urgency_label'] ?? 'LOW';
            if (isset($classCounts[$lbl])) {
                $classCounts[$lbl]++;
            }
            foreach ($r as $val) {
                if ($val === null) {
                    $missingCount++;
                }
            }
        }

        $this->table(
            ['Class Label', 'Count', 'Proportion'],
            [
                ['LOW (Healthy)', $classCounts['LOW'], round(($classCounts['LOW'] / $rows) * 100, 1) . '%'],
                ['MEDIUM (Due Soon)', $classCounts['MEDIUM'], round(($classCounts['MEDIUM'] / $rows) * 100, 1) . '%'],
                ['HIGH (Overdue)', $classCounts['HIGH'], round(($classCounts['HIGH'] / $rows) * 100, 1) . '%'],
            ]
        );

        if ($missingCount > 0) {
            $this->warn("Total Missing / Null Values: {$missingCount}");
        }

        if ($preprocessingReport && ! empty($preprocessingReport['imputation'])) {
            $this->info("Imputation applied to " . count($preprocessingReport['imputation']) . " columns.");
        }

        return self::SUCCESS;
    }

    /**
     * Standard Normal Gaussian Random Variable via Box-Muller transform: N(0, 1).
     */
    protected function gaussianRandom(): float
    {
        $u1 = max(1e-7, (float) mt_rand() / (float) mt_getrandmax());
        $u2 = (float) mt_rand() / (float) mt_getrandmax();

        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }

    protected function randomUniform(float $min, float $max): float
    {
        return $min + ((float) mt_rand() / (float) mt_getrandmax()) * ($max - $min);
    }

    protected function maybeNullify(mixed $val, float $missingRate): mixed
    {
        if ($missingRate <= 0.0) {
            return $val;
        }

        $rand = (float) mt_rand() / (float) mt_getrandmax();
        if ($rand < $missingRate) {
            return null;
        }

        return $val;
    }
}
