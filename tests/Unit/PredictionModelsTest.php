<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Prediction\DecisionTreePredictor;
use App\Services\Prediction\EnsemblePredictor;
use App\Services\Prediction\EvaluationMetricsService;
use App\Services\Prediction\LogisticRegressionPredictor;
use App\Services\Prediction\PredictorInterface;
use App\Services\Prediction\RandomForestPredictor;
use App\Services\Prediction\RuleBasedPredictor;
use App\Services\Prediction\SvmPredictor;
use Tests\TestCase;

class PredictionModelsTest extends TestCase
{
    /**
     * Generate synthetic fleet test records across the 3 urgency categories.
     *
     * @return array{samples: array<int, array>, labels: array<int, string>}
     */
    protected function getSampleDataset(): array
    {
        $samples = [
            // Overdue vehicles -> HIGH
            [
                'days_since_service' => 220,
                'km_since_service'   => 12500,
                'interval_km'        => 10000,
                'interval_months'    => 6,
                'daily_km'           => 60,
                'terrain_severity'   => 1.15,
                'vehicle_age'        => 7,
                'total_mileage'      => 120000,
                'fuel_type'          => 'diesel',
                'owner_type'         => 'corporate',
                'scan_year'          => 2025,
            ],
            [
                'days_since_service' => 200,
                'km_since_service'   => 11000,
                'interval_km'        => 10000,
                'interval_months'    => 6,
                'daily_km'           => 55,
                'terrain_severity'   => 1.20,
                'vehicle_age'        => 8,
                'total_mileage'      => 140000,
                'fuel_type'          => 'diesel',
                'owner_type'         => 'corporate',
                'scan_year'          => 2025,
            ],
            // Due soon (within 14 days) -> MEDIUM
            [
                'days_since_service' => 170,
                'km_since_service'   => 9400,
                'interval_km'        => 10000,
                'interval_months'    => 6,
                'daily_km'           => 50,
                'terrain_severity'   => 1.0,
                'vehicle_age'        => 4,
                'total_mileage'      => 65000,
                'fuel_type'          => 'petrol',
                'owner_type'         => 'individual',
                'scan_year'          => 2026,
            ],
            [
                'days_since_service' => 172,
                'km_since_service'   => 9600,
                'interval_km'        => 10000,
                'interval_months'    => 6,
                'daily_km'           => 50,
                'terrain_severity'   => 1.0,
                'vehicle_age'        => 5,
                'total_mileage'      => 75000,
                'fuel_type'          => 'petrol',
                'owner_type'         => 'individual',
                'scan_year'          => 2026,
            ],
            // Healthy vehicles with ample headroom -> LOW
            [
                'days_since_service' => 30,
                'km_since_service'   => 1200,
                'interval_km'        => 10000,
                'interval_months'    => 6,
                'daily_km'           => 40,
                'terrain_severity'   => 0.90,
                'vehicle_age'        => 2,
                'total_mileage'      => 25000,
                'fuel_type'          => 'hybrid',
                'owner_type'         => 'individual',
                'scan_year'          => 2024,
            ],
            [
                'days_since_service' => 45,
                'km_since_service'   => 2000,
                'interval_km'        => 10000,
                'interval_months'    => 6,
                'daily_km'           => 45,
                'terrain_severity'   => 1.0,
                'vehicle_age'        => 3,
                'total_mileage'      => 35000,
                'fuel_type'          => 'petrol',
                'owner_type'         => 'corporate',
                'scan_year'          => 2024,
            ],
        ];

        $labels = ['HIGH', 'HIGH', 'MEDIUM', 'MEDIUM', 'LOW', 'LOW'];

        return ['samples' => $samples, 'labels' => $labels];
    }

    public function test_rule_based_predictor_classifies_correctly_and_calibrates_confidence(): void
    {
        $predictor = new RuleBasedPredictor();

        // 1. Overdue vehicle
        $overdueSample = [
            'days_since_service' => 200,
            'km_since_service'   => 12000,
            'interval_km'        => 10000,
            'interval_months'    => 6,
            'daily_km'           => 50,
            'terrain_severity'   => 1.0,
        ];
        $resOverdue = $predictor->predictProba($overdueSample);
        $this->assertEquals('HIGH', $resOverdue['prediction']);
        $this->assertGreaterThanOrEqual(50.0, $resOverdue['confidence']);
        $this->assertLessThanOrEqual(99.0, $resOverdue['confidence']);
        $this->assertEquals('HIGH', $predictor->predict($overdueSample));

        // 2. Due soon (e.g. 5 days remaining)
        $dueSoonSample = [
            'days_since_service' => 175,
            'km_since_service'   => 9700,
            'interval_km'        => 10000,
            'interval_months'    => 6,
            'daily_km'           => 50,
            'terrain_severity'   => 1.0,
        ];
        $resDueSoon = $predictor->predictProba($dueSoonSample);
        $this->assertEquals('MEDIUM', $resDueSoon['prediction']);

        // 3. Healthy vehicle (140 days remaining)
        $healthySample = [
            'days_since_service' => 30,
            'km_since_service'   => 1500,
            'interval_km'        => 10000,
            'interval_months'    => 6,
            'daily_km'           => 40,
            'terrain_severity'   => 1.0,
        ];
        $resHealthy = $predictor->predictProba($healthySample);
        $this->assertEquals('LOW', $resHealthy['prediction']);

        // 4. Probabilities sum to approximately 100%
        $sum = array_sum($resHealthy['probabilities']);
        $this->assertEqualsWithDelta(100.0, $sum, 1.0);
    }

    public function test_decision_tree_predictor_fits_and_predicts(): void
    {
        $dataset = $this->getSampleDataset();
        $dt = new DecisionTreePredictor(maxDepth: 4, minSamplesSplit: 2);
        $dt->fit($dataset['samples'], $dataset['labels']);

        $res = $dt->predictProba($dataset['samples'][0]);
        $this->assertContains($res['prediction'], ['LOW', 'MEDIUM', 'HIGH']);
        $this->assertGreaterThanOrEqual(50.0, $res['confidence']);
        $this->assertArrayHasKey('LOW', $res['probabilities']);
        $this->assertArrayHasKey('MEDIUM', $res['probabilities']);
        $this->assertArrayHasKey('HIGH', $res['probabilities']);
    }

    public function test_random_forest_predictor_fits_and_aggregates_votes(): void
    {
        $dataset = $this->getSampleDataset();
        $rf = new RandomForestPredictor(nEstimators: 10, maxDepth: 4);
        $rf->fit($dataset['samples'], $dataset['labels']);

        $res = $rf->predictProba($dataset['samples'][0]);
        $this->assertContains($res['prediction'], ['LOW', 'MEDIUM', 'HIGH']);
        $this->assertEquals(10, $res['total_trees']);
        $this->assertEquals(10, array_sum($res['vote_counts']));
    }

    public function test_svm_predictor_fits_with_rbf_kernel_and_predicts(): void
    {
        $dataset = $this->getSampleDataset();
        $svm = new SvmPredictor(c: 1.0, gamma: 0.1);
        $svm->fit($dataset['samples'], $dataset['labels']);

        $res = $svm->predictProba($dataset['samples'][0]);
        $this->assertContains($res['prediction'], ['LOW', 'MEDIUM', 'HIGH']);
        $this->assertGreaterThanOrEqual(50.0, $res['confidence']);
        $this->assertArrayHasKey('HIGH', $res['probabilities']);
    }

    public function test_logistic_regression_predictor_fits_and_predicts_softmax(): void
    {
        $dataset = $this->getSampleDataset();
        $lr = new LogisticRegressionPredictor(learningRate: 0.1, maxIterations: 50);
        $lr->fit($dataset['samples'], $dataset['labels']);

        $res = $lr->predictProba($dataset['samples'][0]);
        $this->assertContains($res['prediction'], ['LOW', 'MEDIUM', 'HIGH']);
        $this->assertGreaterThanOrEqual(50.0, $res['confidence']);
        $this->assertEqualsWithDelta(100.0, array_sum($res['probabilities']), 1.0);
    }

    public function test_ensemble_predictor_executes_majority_voting_and_tie_breaking(): void
    {
        $dataset = $this->getSampleDataset();
        $ensemble = new EnsemblePredictor();
        $ensemble->fit($dataset['samples'], $dataset['labels']);

        $res = $ensemble->predictDetailed($dataset['samples'][0]);

        $this->assertContains($res['final_urgency'], ['LOW', 'MEDIUM', 'HIGH']);
        $this->assertCount(5, $res['model_predictions']);
        $this->assertArrayHasKey('rule_based', $res['model_predictions']);
        $this->assertArrayHasKey('decision_tree', $res['model_predictions']);
        $this->assertArrayHasKey('random_forest', $res['model_predictions']);
        $this->assertArrayHasKey('svm', $res['model_predictions']);
        $this->assertArrayHasKey('logistic_regression', $res['model_predictions']);
        $this->assertEquals(5, array_sum($res['votes']));

        // Test explicit tie-breaking logic using mock predictors
        $mockHigh = new class implements PredictorInterface {
            public function fit(array $s, array $l): void {}
            public function predict(array $s): string { return 'HIGH'; }
            public function predictProba(array $s): array {
                return ['prediction' => 'HIGH', 'confidence' => 95.0, 'probabilities' => ['HIGH' => 95.0]];
            }
            public function getKey(): string { return 'mock_high'; }
            public function getName(): string { return 'Mock High Model'; }
        };

        $mockMedium = new class implements PredictorInterface {
            public function fit(array $s, array $l): void {}
            public function predict(array $s): string { return 'MEDIUM'; }
            public function predictProba(array $s): array {
                return ['prediction' => 'MEDIUM', 'confidence' => 70.0, 'probabilities' => ['MEDIUM' => 70.0]];
            }
            public function getKey(): string { return 'mock_med'; }
            public function getName(): string { return 'Mock Medium Model'; }
        };

        // Construct 2 HIGH vs 2 MEDIUM tie
        $tiedEnsemble = new EnsemblePredictor([
            'm1' => $mockHigh,
            'm2' => $mockMedium,
        ]);

        $tiedResult = $tiedEnsemble->predictDetailed([]);
        $this->assertTrue($tiedResult['tie_broken']);
        // Mock High had 95% vs Mock Med 70%, so HIGH must win tie-breaker
        $this->assertEquals('HIGH', $tiedResult['final_urgency']);
        $this->assertEquals('Mock High Model', $tiedResult['tie_breaking_model']);
        $this->assertEquals(95.0, $tiedResult['confidence']);
    }

    public function test_evaluation_metrics_service_computes_accuracy_f1_confusion_and_yearly(): void
    {
        $evaluator = new EvaluationMetricsService();

        $true = ['HIGH', 'HIGH', 'MEDIUM', 'MEDIUM', 'LOW', 'LOW'];
        $pred = ['HIGH', 'MEDIUM', 'MEDIUM', 'LOW', 'LOW', 'LOW'];

        $samples = [
            ['scan_year' => 2024],
            ['scan_year' => 2024],
            ['scan_year' => 2025],
            ['scan_year' => 2025],
            ['scan_year' => 2026],
            ['scan_year' => 2026],
        ];

        $metrics = $evaluator->calculate($true, $pred, $samples);

        // 4 out of 6 correct = 66.67%
        $this->assertEquals(0.6667, $metrics['accuracy']);
        $this->assertArrayHasKey('confusion_matrix', $metrics);
        $this->assertEquals(1, $metrics['confusion_matrix_named']['HIGH']['HIGH']);
        $this->assertEquals(1, $metrics['confusion_matrix_named']['HIGH']['MEDIUM']);
        $this->assertEquals(0, $metrics['confusion_matrix_named']['HIGH']['LOW']);
        $this->assertEquals(1, $metrics['confusion_matrix'][2][2]); // HIGH/HIGH in 3x3

        $this->assertArrayHasKey('yearly_accuracy', $metrics);
        $this->assertArrayHasKey('2024', $metrics['yearly_accuracy']);
        $this->assertArrayHasKey('2025', $metrics['yearly_accuracy']);
        $this->assertArrayHasKey('2026', $metrics['yearly_accuracy']);
        $this->assertEquals(100.0, $metrics['yearly_accuracy']['2026']); // 2/2 correct in 2026

        $this->assertGreaterThan(0.0, $metrics['precision_macro']);
        $this->assertGreaterThan(0.0, $metrics['recall_macro']);
        $this->assertGreaterThan(0.0, $metrics['f1_macro']);
        $this->assertEquals(0.5, $metrics['high_urgency_recall']); // 1 of 2 HIGH detected
    }
}
