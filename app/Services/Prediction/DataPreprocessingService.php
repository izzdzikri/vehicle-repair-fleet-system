<?php

declare(strict_types=1);

namespace App\Services\Prediction;

/**
 * Class DataPreprocessingService
 * 
 * Research-Grade Data Preprocessing Pipeline for Fleet Maintenance Datasets.
 * 
 * Key Capabilities:
 * 1. Missing-Value Imputation:
 *    - Numeric features: Median imputation (robust against extreme outliers).
 *    - Categorical features: Mode imputation (most frequent observed class).
 * 2. Outlier Detection & Clipping:
 *    - Interquartile Range (IQR) fencing: [Q1 - 1.5*IQR, Q3 + 1.5*IQR].
 *    - Extreme telemetry spikes (e.g. sensor glitched 500,000 km) are clipped to boundary thresholds.
 * 3. Feature Scaling:
 *    - Z-Score Standardization (zero mean, unit variance): z = (x - mu) / sigma.
 *    - Min-Max Scaling (bounded to [0, 1]): x' = (x - min) / (max - min).
 * 4. Stored Transformation Audit Report:
 *    - Emits a structured summary detailing every imputation, outlier clamp, and distribution shifts.
 */
class DataPreprocessingService
{
    public const NUMERIC_COLUMNS = [
        'days_since_service',
        'km_since_service',
        'interval_km',
        'interval_months',
        'daily_km',
        'terrain_severity',
        'vehicle_age',
        'total_mileage',
    ];

    public const CATEGORICAL_COLUMNS = [
        'fuel_type',
        'owner_type',
        'service_type',
    ];

    /**
     * Run full preprocessing pipeline: Imputation -> Outlier Clipping -> Optional Scaling.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param array{
     *     impute?: bool,
     *     clip_outliers?: bool,
     *     scale?: bool,
     *     scaling_method?: 'standard'|'minmax',
     *     iqr_multiplier?: float
     * } $options
     * @return array{
     *     clean_data: array<int, array<string, mixed>>,
     *     report: array<string, mixed>
     * }
     */
    public function preprocess(array $rows, array $options = []): array
    {
        if (empty($rows)) {
            return [
                'clean_data' => [],
                'report' => [
                    'total_rows' => 0,
                    'status' => 'empty',
                ],
            ];
        }

        $doImpute = $options['impute'] ?? true;
        $doClip = $options['clip_outliers'] ?? true;
        $doScale = $options['scale'] ?? false;
        $scaleMethod = $options['scaling_method'] ?? 'standard';
        $iqrMult = (float) ($options['iqr_multiplier'] ?? 1.5);

        $initialSummary = $this->computeFeatureSummary($rows);
        $cleanData = $rows;

        // 1. Missing Value Imputation
        $imputationReport = [];
        if ($doImpute) {
            $imputeResult = $this->imputeMissingValues($cleanData);
            $cleanData = $imputeResult['data'];
            $imputationReport = $imputeResult['report'];
        }

        // 2. Outlier Clipping
        $outlierReport = [];
        if ($doClip) {
            $clipResult = $this->clipOutliers($cleanData, $iqrMult);
            $cleanData = $clipResult['data'];
            $outlierReport = $clipResult['report'];
        }

        // 3. Feature Scaling (optional, non-destructive: stores in scaled_features key if requested)
        $scalingReport = [];
        if ($doScale) {
            $scaleResult = $this->scaleFeatures($cleanData, $scaleMethod);
            $cleanData = $scaleResult['data'];
            $scalingReport = $scaleResult['report'];
        }

        $finalSummary = $this->computeFeatureSummary($cleanData);

        $report = [
            'total_rows'        => count($cleanData),
            'timestamp'         => now()->toIso8601String(),
            'options'           => [
                'impute'         => $doImpute,
                'clip_outliers'  => $doClip,
                'scale'          => $doScale,
                'scaling_method' => $scaleMethod,
                'iqr_multiplier' => $iqrMult,
            ],
            'imputation'        => $imputationReport,
            'outliers'          => $outlierReport,
            'scaling'           => $scalingReport,
            'summary_before'    => $initialSummary,
            'summary_after'     => $finalSummary,
        ];

        return [
            'clean_data' => $cleanData,
            'report'     => $report,
        ];
    }

    /**
     * Impute missing values (median for numeric, mode for categorical).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array{data: array, report: array}
     */
    public function imputeMissingValues(array $rows): array
    {
        $report = [];
        $medians = [];
        $modes = [];

        // 1. Calculate medians for numeric columns from present values
        foreach (self::NUMERIC_COLUMNS as $col) {
            $vals = [];
            $missingCount = 0;

            foreach ($rows as $row) {
                $val = $row[$col] ?? null;
                if ($val !== null && $val !== '' && is_numeric($val)) {
                    $vals[] = (float) $val;
                } else {
                    $missingCount++;
                }
            }

            if (! empty($vals)) {
                sort($vals);
                $cnt = count($vals);
                $mid = (int) floor($cnt / 2);
                $med = ($cnt % 2 === 0) ? ($vals[$mid - 1] + $vals[$mid]) / 2.0 : (float) $vals[$mid];
                $medians[$col] = $med;
            } else {
                $medians[$col] = 0.0;
            }

            if ($missingCount > 0) {
                $report[$col] = [
                    'strategy'      => 'median',
                    'missing_count' => $missingCount,
                    'missing_pct'   => round(($missingCount / count($rows)) * 100, 2),
                    'imputed_value' => round($medians[$col], 4),
                ];
            }
        }

        // 2. Calculate modes for categorical columns
        foreach (self::CATEGORICAL_COLUMNS as $col) {
            $frequencies = [];
            $missingCount = 0;

            foreach ($rows as $row) {
                $val = $row[$col] ?? null;
                if ($val !== null && trim((string) $val) !== '') {
                    $vStr = strtolower(trim((string) $val));
                    $frequencies[$vStr] = ($frequencies[$vStr] ?? 0) + 1;
                } else {
                    $missingCount++;
                }
            }

            if (! empty($frequencies)) {
                arsort($frequencies);
                $mode = array_key_first($frequencies);
                $modes[$col] = $mode;
            } else {
                $modes[$col] = 'unknown';
            }

            if ($missingCount > 0) {
                $report[$col] = [
                    'strategy'      => 'mode',
                    'missing_count' => $missingCount,
                    'missing_pct'   => round(($missingCount / count($rows)) * 100, 2),
                    'imputed_value' => $modes[$col],
                ];
            }
        }

        // 3. Fill missing values
        $imputedData = [];
        foreach ($rows as $row) {
            $r = $row;
            foreach (self::NUMERIC_COLUMNS as $col) {
                $v = $r[$col] ?? null;
                if ($v === null || $v === '' || ! is_numeric($v)) {
                    $r[$col] = $medians[$col];
                } else {
                    $r[$col] = (float) $v;
                }
            }
            foreach (self::CATEGORICAL_COLUMNS as $col) {
                $v = $r[$col] ?? null;
                if ($v === null || trim((string) $v) === '') {
                    $r[$col] = $modes[$col];
                }
            }
            $imputedData[] = $r;
        }

        return ['data' => $imputedData, 'report' => $report];
    }

    /**
     * Clip outliers using the Interquartile Range (IQR) rule: [Q1 - k*IQR, Q3 + k*IQR].
     *
     * @param array<int, array<string, mixed>> $rows
     * @param float $k Multiplier (default 1.5)
     * @return array{data: array, report: array}
     */
    public function clipOutliers(array $rows, float $k = 1.5): array
    {
        $report = [];
        $bounds = [];

        foreach (self::NUMERIC_COLUMNS as $col) {
            $vals = [];
            foreach ($rows as $r) {
                if (isset($r[$col]) && is_numeric($r[$col])) {
                    $vals[] = (float) $r[$col];
                }
            }

            if (count($vals) < 4) {
                continue;
            }

            sort($vals);
            $q1 = $this->percentile($vals, 0.25);
            $q3 = $this->percentile($vals, 0.75);
            $iqr = max(0.0, $q3 - $q1);

            // Avoid zero-IQR collapsing bounds for near-constant columns
            if ($iqr < 1e-5) {
                continue;
            }

            // Lower bound clamped to 0 for non-negative vehicle physical attributes
            $lower = max(0.0, $q1 - ($k * $iqr));
            $upper = $q3 + ($k * $iqr);

            // Exception: terrain severity minimum is 0.5
            if ($col === 'terrain_severity') {
                $lower = max(0.5, $lower);
            }

            $bounds[$col] = [
                'q1'    => round($q1, 4),
                'q3'    => round($q3, 4),
                'iqr'   => round($iqr, 4),
                'lower' => round($lower, 4),
                'upper' => round($upper, 4),
            ];
        }

        // Apply clipping
        $clippedData = [];
        $clipCounts = array_fill_keys(array_keys($bounds), ['lower' => 0, 'upper' => 0, 'total' => 0]);

        foreach ($rows as $row) {
            $r = $row;
            foreach ($bounds as $col => $b) {
                if (isset($r[$col]) && is_numeric($r[$col])) {
                    $val = (float) $r[$col];
                    if ($val < $b['lower']) {
                        $r[$col] = $b['lower'];
                        $clipCounts[$col]['lower']++;
                        $clipCounts[$col]['total']++;
                    } elseif ($val > $b['upper']) {
                        $r[$col] = $b['upper'];
                        $clipCounts[$col]['upper']++;
                        $clipCounts[$col]['total']++;
                    }
                }
            }
            $clippedData[] = $r;
        }

        foreach ($bounds as $col => $b) {
            if ($clipCounts[$col]['total'] > 0) {
                $report[$col] = array_merge($b, [
                    'clipped_lower' => $clipCounts[$col]['lower'],
                    'clipped_upper' => $clipCounts[$col]['upper'],
                    'clipped_total' => $clipCounts[$col]['total'],
                    'clipped_pct'   => round(($clipCounts[$col]['total'] / count($rows)) * 100, 2),
                ]);
            }
        }

        return ['data' => $clippedData, 'report' => $report];
    }

    /**
     * Scale numeric features using Z-Score (standard) or Min-Max normalization.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param string $method
     * @return array{data: array, report: array}
     */
    public function scaleFeatures(array $rows, string $method = 'standard'): array
    {
        $report = [];
        $params = [];

        foreach (self::NUMERIC_COLUMNS as $col) {
            $vals = [];
            foreach ($rows as $r) {
                if (isset($r[$col]) && is_numeric($r[$col])) {
                    $vals[] = (float) $r[$col];
                }
            }
            if (empty($vals)) {
                continue;
            }

            $count = count($vals);
            $min = min($vals);
            $max = max($vals);
            $mean = array_sum($vals) / $count;

            $variance = 0.0;
            foreach ($vals as $v) {
                $variance += pow($v - $mean, 2);
            }
            $std = sqrt($variance / max(1, $count));

            $params[$col] = [
                'min'  => $min,
                'max'  => $max,
                'mean' => $mean,
                'std'  => $std > 1e-7 ? $std : 1.0,
            ];
            $report[$col] = $params[$col];
        }

        $scaledData = [];
        foreach ($rows as $row) {
            $r = $row;
            foreach ($params as $col => $p) {
                if (isset($r[$col]) && is_numeric($r[$col])) {
                    $val = (float) $r[$col];
                    if ($method === 'minmax') {
                        $range = max(1e-7, $p['max'] - $p['min']);
                        $r[$col . '_scaled'] = round(($val - $p['min']) / $range, 4);
                    } else {
                        // Standard Z-Score
                        $r[$col . '_scaled'] = round(($val - $p['mean']) / $p['std'], 4);
                    }
                }
            }
            $scaledData[] = $r;
        }

        return ['data' => $scaledData, 'report' => $report];
    }

    /**
     * Calculate statistical summary for numeric columns.
     */
    protected function computeFeatureSummary(array $rows): array
    {
        $summary = [];
        foreach (self::NUMERIC_COLUMNS as $col) {
            $vals = [];
            $nullCount = 0;

            foreach ($rows as $r) {
                $v = $r[$col] ?? null;
                if ($v !== null && $v !== '' && is_numeric($v)) {
                    $vals[] = (float) $v;
                } else {
                    $nullCount++;
                }
            }

            if (! empty($vals)) {
                $cnt = count($vals);
                $mean = array_sum($vals) / $cnt;
                $summary[$col] = [
                    'count'      => $cnt,
                    'null_count' => $nullCount,
                    'min'        => round(min($vals), 2),
                    'max'        => round(max($vals), 2),
                    'mean'       => round($mean, 2),
                ];
            }
        }

        return $summary;
    }

    /**
     * Compute percentile using linear interpolation.
     */
    protected function percentile(array $sortedVals, float $p): float
    {
        $n = count($sortedVals);
        if ($n === 0) {
            return 0.0;
        }
        if ($n === 1) {
            return (float) $sortedVals[0];
        }

        $idx = $p * ($n - 1);
        $low = (int) floor($idx);
        $high = (int) ceil($idx);
        $weight = $idx - $low;

        return (float) ($sortedVals[$low] * (1.0 - $weight) + $sortedVals[$high] * $weight);
    }
}
