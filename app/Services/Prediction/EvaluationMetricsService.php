<?php

declare(strict_types=1);

namespace App\Services\Prediction;

/**
 * Class EvaluationMetricsService
 * 
 * Computes rigorous statistical performance metrics for predictive maintenance classifiers:
 * - Multiclass Accuracy
 * - Macro-Averaged Precision, Recall, and F1-Score
 * - High-Urgency Recall (Safety critical metric)
 * - 3x3 Confusion Matrix
 * - Temporal Accuracy Breakdown per Scan Year
 */
class EvaluationMetricsService
{
    public const CLASSES = ['LOW', 'MEDIUM', 'HIGH'];

    /**
     * Compute comprehensive metrics for predictions against true labels.
     *
     * @param array<int, string> $trueLabels
     * @param array<int, string> $predLabels
     * @param array<int, array<string, mixed>>|null $samples
     * @return array{
     *     accuracy: float,
     *     precision_macro: float,
     *     recall_macro: float,
     *     f1_macro: float,
     *     high_urgency_recall: float,
     *     confusion_matrix: array<string, array<string, int>>,
     *     class_metrics: array<string, array{precision: float, recall: float, f1: float, support: int}>,
     *     yearly_accuracy: array<string, float>,
     *     sample_count: int
     * }
     */
    public function calculate(array $trueLabels, array $predLabels, ?array $samples = null): array
    {
        $n = count($trueLabels);
        if ($n === 0 || $n !== count($predLabels)) {
            return [
                'accuracy'            => 0.0,
                'precision_macro'     => 0.0,
                'recall_macro'        => 0.0,
                'f1_macro'            => 0.0,
                'high_urgency_recall' => 0.0,
                'confusion_matrix'    => $this->emptyConfusionMatrix(),
                'class_metrics'       => [],
                'yearly_accuracy'     => [],
                'sample_count'        => 0,
            ];
        }

        // Initialize 3x3 confusion matrix [Actual][Predicted]
        $cm = $this->emptyConfusionMatrix();
        $correct = 0;

        for ($i = 0; $i < $n; $i++) {
            $act = strtoupper((string) ($trueLabels[$i] ?? 'LOW'));
            $prd = strtoupper((string) ($predLabels[$i] ?? 'LOW'));

            if (! in_array($act, self::CLASSES, true)) {
                $act = 'LOW';
            }
            if (! in_array($prd, self::CLASSES, true)) {
                $prd = 'LOW';
            }

            $cm[$act][$prd]++;
            if ($act === $prd) {
                $correct++;
            }
        }

        $overallAccuracy = round($correct / $n, 4);

        // Per-class metrics
        $classMetrics = [];
        $precisions = [];
        $recalls = [];
        $f1s = [];

        foreach (self::CLASSES as $c) {
            $tp = $cm[$c][$c];
            $actualTotal = array_sum($cm[$c]); // TP + FN
            $predTotal = 0; // TP + FP
            foreach (self::CLASSES as $rowClass) {
                $predTotal += $cm[$rowClass][$c];
            }

            $precision = $predTotal > 0 ? ($tp / $predTotal) : 0.0;
            $recall = $actualTotal > 0 ? ($tp / $actualTotal) : 0.0;
            $f1 = ($precision + $recall) > 0 ? (2 * $precision * $recall) / ($precision + $recall) : 0.0;

            $classMetrics[$c] = [
                'precision' => round($precision, 4),
                'recall'    => round($recall, 4),
                'f1'        => round($f1, 4),
                'support'   => $actualTotal,
            ];

            $precisions[] = $precision;
            $recalls[] = $recall;
            $f1s[] = $f1;
        }

        $macroPrecision = round(array_sum($precisions) / count(self::CLASSES), 4);
        $macroRecall = round(array_sum($recalls) / count(self::CLASSES), 4);
        $macroF1 = round(array_sum($f1s) / count(self::CLASSES), 4);
        $highRecall = $classMetrics['HIGH']['recall'];

        // Temporal Accuracy Breakdown by Year
        $yearlyAccuracy = [];
        if ($samples !== null) {
            $yearBuckets = [];
            foreach ($samples as $i => $s) {
                $year = (string) ($s['year'] ?? ($s['scan_year'] ?? 'overall'));
                if (! isset($yearBuckets[$year])) {
                    $yearBuckets[$year] = ['correct' => 0, 'total' => 0];
                }
                $yearBuckets[$year]['total']++;

                $act = strtoupper((string) ($trueLabels[$i] ?? 'LOW'));
                $prd = strtoupper((string) ($predLabels[$i] ?? 'LOW'));
                if ($act === $prd) {
                    $yearBuckets[$year]['correct']++;
                }
            }

            ksort($yearBuckets);
            foreach ($yearBuckets as $yr => $data) {
                $yearlyAccuracy[$yr] = $data['total'] > 0
                    ? round(($data['correct'] / $data['total']) * 100.0, 1)
                    : 0.0;
            }
        }

        $yearlyAccuracy['overall'] = round($overallAccuracy * 100.0, 1);

        $matrix3x3 = [
            [$cm['LOW']['LOW'], $cm['LOW']['MEDIUM'], $cm['LOW']['HIGH']],
            [$cm['MEDIUM']['LOW'], $cm['MEDIUM']['MEDIUM'], $cm['MEDIUM']['HIGH']],
            [$cm['HIGH']['LOW'], $cm['HIGH']['MEDIUM'], $cm['HIGH']['HIGH']],
        ];

        return [
            'accuracy'               => $overallAccuracy,
            'precision_macro'        => $macroPrecision,
            'recall_macro'           => $macroRecall,
            'f1_macro'               => $macroF1,
            'high_urgency_recall'    => $highRecall,
            'confusion_matrix'       => $matrix3x3,
            'confusion_matrix_named' => $cm,
            'class_metrics'          => $classMetrics,
            'yearly_accuracy'        => $yearlyAccuracy,
            'sample_count'           => $n,
        ];
    }

    /**
     * Helper to instantiate an empty 3x3 confusion matrix.
     *
     * @return array<string, array<string, int>>
     */
    protected function emptyConfusionMatrix(): array
    {
        $cm = [];
        foreach (self::CLASSES as $act) {
            $cm[$act] = [];
            foreach (self::CLASSES as $prd) {
                $cm[$act][$prd] = 0;
            }
        }

        return $cm;
    }
}
