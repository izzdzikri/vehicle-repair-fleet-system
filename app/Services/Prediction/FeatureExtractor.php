<?php

declare(strict_types=1);

namespace App\Services\Prediction;

/**
 * Feature Extractor and Preprocessing Encoder
 * 
 * Standardizes raw associative feature arrays into flat numeric vectors suitable
 * for statistical and machine learning classifiers (Decision Tree, Random Forest, SVM, Logistic Regression).
 */
class FeatureExtractor
{
    public const FUEL_TYPES = ['petrol', 'diesel', 'hybrid', 'electric'];
    public const OWNER_TYPES = ['individual', 'corporate'];

    public const NUMERIC_KEYS = [
        'days_since_service',
        'km_since_service',
        'interval_km',
        'interval_months',
        'daily_km',
        'terrain_severity',
        'vehicle_age',
        'total_mileage',
    ];

    /**
     * Convert an associative feature dictionary to a flat float array.
     * Categorical variables are one-hot encoded deterministically.
     *
     * @param array<string, mixed> $sample
     * @return array<int, float>
     */
    public static function extractVector(array $sample): array
    {
        $vector = [];

        // 1. Numeric features with fallback defaults
        $vector[] = (float) ($sample['days_since_service'] ?? 0.0);
        $vector[] = (float) ($sample['km_since_service'] ?? 0.0);
        $vector[] = (float) ($sample['interval_km'] ?? 10000.0);
        $vector[] = (float) ($sample['interval_months'] ?? 12.0);
        $vector[] = (float) ($sample['daily_km'] ?? 40.0);
        $vector[] = (float) ($sample['terrain_severity'] ?? 1.0);
        $vector[] = (float) ($sample['vehicle_age'] ?? 5.0);
        $vector[] = (float) ($sample['total_mileage'] ?? 50000.0);

        // Derived relative ratios (critical wear indicators)
        $effKm = max(1.0, ((float) ($sample['interval_km'] ?? 10000.0)) / max(0.1, (float) ($sample['terrain_severity'] ?? 1.0)));
        $kmSince = (float) ($sample['km_since_service'] ?? 0.0);
        $vector[] = (float) ($kmSince / $effKm); // km_wear_ratio

        $effDays = max(1.0, ((float) ($sample['interval_months'] ?? 12.0)) * 30.44);
        $daysSince = (float) ($sample['days_since_service'] ?? 0.0);
        $vector[] = (float) ($daysSince / $effDays); // time_wear_ratio

        // 2. One-hot encode fuel_type
        $fuel = strtolower((string) ($sample['fuel_type'] ?? 'petrol'));
        foreach (self::FUEL_TYPES as $type) {
            $vector[] = ($fuel === $type) ? 1.0 : 0.0;
        }

        // 3. One-hot encode owner_type
        $owner = strtolower((string) ($sample['owner_type'] ?? 'individual'));
        foreach (self::OWNER_TYPES as $type) {
            $vector[] = ($owner === $type) ? 1.0 : 0.0;
        }

        return $vector;
    }

    /**
     * Compute mean and standard deviation for each feature across a dataset.
     *
     * @param array<int, array<int, float>> $vectors
     * @return array{means: array<int, float>, stds: array<int, float>}
     */
    public static function computeScalingParameters(array $vectors): array
    {
        if (empty($vectors)) {
            return ['means' => [], 'stds' => []];
        }

        $numFeatures = count($vectors[0]);
        $n = count($vectors);
        $means = array_fill(0, $numFeatures, 0.0);
        $stds = array_fill(0, $numFeatures, 0.0);

        // Sum
        foreach ($vectors as $v) {
            for ($j = 0; $j < $numFeatures; $j++) {
                $means[$j] += $v[$j] ?? 0.0;
            }
        }
        for ($j = 0; $j < $numFeatures; $j++) {
            $means[$j] /= max(1, $n);
        }

        // Variance
        foreach ($vectors as $v) {
            for ($j = 0; $j < $numFeatures; $j++) {
                $diff = ($v[$j] ?? 0.0) - $means[$j];
                $stds[$j] += $diff * $diff;
            }
        }
        for ($j = 0; $j < $numFeatures; $j++) {
            $variance = $stds[$j] / max(1, $n);
            $stds[$j] = $variance > 1e-7 ? sqrt($variance) : 1.0;
        }

        return ['means' => $means, 'stds' => $stds];
    }

    /**
     * Apply Z-score standardization: z = (x - mean) / std.
     *
     * @param array<int, float> $vector
     * @param array<int, float> $means
     * @param array<int, float> $stds
     * @return array<int, float>
     */
    public static function scaleVector(array $vector, array $means, array $stds): array
    {
        $scaled = [];
        $count = count($vector);
        for ($j = 0; $j < $count; $j++) {
            $mean = $means[$j] ?? 0.0;
            $std = $stds[$j] ?? 1.0;
            $scaled[$j] = ($vector[$j] - $mean) / ($std > 1e-7 ? $std : 1.0);
        }

        return $scaled;
    }
}
