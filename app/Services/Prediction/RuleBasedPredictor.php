<?php

declare(strict_types=1);

namespace App\Services\Prediction;

/**
 * Class RuleBasedPredictor
 * 
 * Deterministic baseline predictive maintenance model based on dual physical constraints:
 * cumulative mileage degradation and calendar aging, modulated by operating terrain severity.
 * 
 * =========================================================================================
 * ACADEMIC PSEUDOCODE FOR THESIS & PUBLICATION CITATION:
 * =========================================================================================
 * 
 * Algorithm: Dual-Constraint Physical Horizon Classification (DCPH)
 * 
 * Input:
 *   - d_since       : Elapsed days since last scheduled maintenance service
 *   - km_since      : Odometer kilometers traversed since last service
 *   - int_km        : Recommended manufacturer service interval in kilometers
 *   - int_mo        : Recommended manufacturer service interval in months
 *   - daily_km      : Estimated daily driving utilization rate (km/day)
 *   - terrain_sev   : Operating environment penalty factor (e.g. 1.15 for urban stop-and-go)
 * 
 * Output:
 *   - Urgency       : Categorical state in {'LOW', 'MEDIUM', 'HIGH'}
 *   - Confidence    : Normalized certainty in [50.0%, 99.0%]
 *   - Probabilities : Soft posterior distribution across all three states
 * 
 * Procedure:
 *   1: eff_km  <- int_km / max(terrain_sev, 0.1)
 *   2: rem_km  <- eff_km - km_since
 *   3: d_km    <- rem_km / max(daily_km, 1.0)
 *   4: d_time  <- (int_mo * 30.44) - d_since
 *   5: d_eff   <- min(d_km, d_time)                // Governing bottleneck constraint
 * 
 *   6: if d_eff <= 0 then
 *   7:    Urgency <- 'HIGH'                         // Threshold exceeded; immediate service required
 *   8:    delta   <- min(abs(d_eff), 60.0)
 *   9:    Conf    <- 50.0 + 50.0 * (delta / 60.0)
 *  10: else if d_eff <= 14 then
 *  11:   Urgency <- 'MEDIUM'                       // Due within 14-day preventive booking queue
 *  12:   delta   <- min(d_eff, 14.0 - d_eff)
 *  13:   Conf    <- 50.0 + 50.0 * (delta / 7.0)
 *  14: else
 *  15:   Urgency <- 'LOW'                          // Healthy operational state; ample headroom
 *  16:   delta   <- min(d_eff - 14.0, 60.0)
 *  17:   Conf    <- 50.0 + 50.0 * (delta / 60.0)
 *  18: end if
 * 
 *  19: Conf <- clamp(Conf, 50.0, 99.0)
 *  20: Prob[Urgency] <- Conf
 *  21: Remainder <- (100.0 - Conf)
 *  22: Distribute Remainder across complementary states according to distance proximity.
 *  23: Return (Urgency, Conf, Prob)
 * =========================================================================================
 */
class RuleBasedPredictor implements PredictorInterface
{
    protected float $dueSoonDays;
    protected float $confidenceMarginDays;
    protected array $terrainWeights;

    public function __construct()
    {
        $cfg = config('prediction.rule_based', []);
        $this->dueSoonDays = (float) ($cfg['due_soon_days'] ?? 14.0);
        $this->confidenceMarginDays = (float) ($cfg['confidence_margin_days'] ?? 60.0);
        $this->terrainWeights = (array) ($cfg['terrain_severity'] ?? [
            'highway'  => 0.90,
            'mixed'    => 1.00,
            'urban'    => 1.15,
            'rural'    => 1.20,
            'mountain' => 1.30,
        ]);
    }

    public function getKey(): string
    {
        return 'rule_based';
    }

    public function getName(): string
    {
        return 'Rule-Based Baseline';
    }

    /**
     * Rule-based algorithm is deterministic and non-parametric.
     * fit() satisfies the PredictorInterface contract.
     */
    public function fit(array $samples, array $labels): void
    {
        // Deterministic expert system; parameters are grounded in automotive maintenance physics
    }

    public function predict(array $sample): string
    {
        return $this->predictProba($sample)['prediction'];
    }

    public function predictProba(array $sample): array
    {
        $intKm = max(1000.0, (float) ($sample['interval_km'] ?? 10000.0));
        $intMonths = max(1.0, (float) ($sample['interval_months'] ?? 12.0));
        $kmSince = max(0.0, (float) ($sample['km_since_service'] ?? 0.0));
        $daysSince = max(0.0, (float) ($sample['days_since_service'] ?? 0.0));
        $dailyKm = max(1.0, (float) ($sample['daily_km'] ?? 40.0));

        // Resolve terrain penalty
        $rawTerrain = $sample['terrain_severity'] ?? 1.0;
        $terrainSeverity = is_numeric($rawTerrain)
            ? max(0.5, (float) $rawTerrain)
            : ($this->terrainWeights[strtolower((string) $rawTerrain)] ?? 1.0);

        // Physical calculations
        $effectiveKm = max(1.0, $intKm / $terrainSeverity);
        $remainingKm = $effectiveKm - $kmSince;
        $daysViaKm = $remainingKm / $dailyKm;
        $daysViaTime = ($intMonths * 30.44) - $daysSince;

        // Governing constraint (shortest remaining horizon)
        $d = (int) round(min($daysViaKm, $daysViaTime));

        if ($d <= 0) {
            $prediction = 'HIGH';
            $margin = min(abs($d), $this->confidenceMarginDays);
            $confidence = 50.0 + 50.0 * ($margin / $this->confidenceMarginDays);
        } elseif ($d <= $this->dueSoonDays) {
            $prediction = 'MEDIUM';
            $halfWindow = $this->dueSoonDays / 2.0;
            $distToEdge = min($d, $this->dueSoonDays - $d);
            $confidence = 50.0 + 50.0 * ($distToEdge / max(1.0, $halfWindow));
        } else {
            $prediction = 'LOW';
            $margin = min($d - $this->dueSoonDays, $this->confidenceMarginDays);
            $confidence = 50.0 + 50.0 * ($margin / $this->confidenceMarginDays);
        }

        $confidence = min(99.0, max(50.0, round($confidence, 1)));

        // Posterior probability distribution
        $remainingProb = round(100.0 - $confidence, 1);
        if ($prediction === 'HIGH') {
            $probabilities = [
                'HIGH'   => $confidence,
                'MEDIUM' => round($remainingProb * 0.75, 1),
                'LOW'    => round($remainingProb * 0.25, 1),
            ];
        } elseif ($prediction === 'MEDIUM') {
            $probabilities = [
                'HIGH'   => round($remainingProb * 0.50, 1),
                'MEDIUM' => $confidence,
                'LOW'    => round($remainingProb * 0.50, 1),
            ];
        } else {
            $probabilities = [
                'HIGH'   => round($remainingProb * 0.20, 1),
                'MEDIUM' => round($remainingProb * 0.80, 1),
                'LOW'    => $confidence,
            ];
        }

        // Normalize sum to 100.0
        $sum = array_sum($probabilities);
        if ($sum > 0) {
            foreach ($probabilities as $k => $v) {
                $probabilities[$k] = round(($v / $sum) * 100.0, 1);
            }
        }

        return [
            'prediction'    => $prediction,
            'confidence'    => $confidence,
            'probabilities' => $probabilities,
            'diagnostics'   => [
                'days_remaining'        => $d,
                'effective_interval_km' => round($effectiveKm, 1),
                'days_via_km'           => round($daysViaKm, 1),
                'days_via_time'         => round($daysViaTime, 1),
                'governing_factor'      => $daysViaKm <= $daysViaTime ? 'mileage' : 'calendar_time',
            ],
        ];
    }
}
