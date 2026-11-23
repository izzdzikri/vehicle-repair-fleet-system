<?php

declare(strict_types=1);

namespace App\Services\Prediction;

/**
 * Common Predictor Interface for All Predictive Maintenance Algorithms
 * 
 * Standard contract implemented by:
 * 1. RuleBasedPredictor (Deterministic physics baseline)
 * 2. DecisionTreePredictor (CART algorithm)
 * 3. RandomForestPredictor (Bootstrap aggregation of CART trees)
 * 4. SvmPredictor (Support Vector Machine with RBF kernel)
 * 5. LogisticRegressionPredictor (Multinomial softmax regression)
 * 
 * Output labels are standard 3-tier: 'LOW', 'MEDIUM', 'HIGH'
 */
interface PredictorInterface
{
    /**
     * Train / fit the model with training sample feature vectors and ground-truth labels.
     *
     * @param array<int, array<string, mixed>> $samples Array of feature associative arrays
     * @param array<int, string> $labels Array of class labels ('LOW', 'MEDIUM', 'HIGH')
     */
    public function fit(array $samples, array $labels): void;

    /**
     * Predict the class label for a single feature vector.
     *
     * @param array<string, mixed> $sample Feature associative array
     * @return string 'LOW' | 'MEDIUM' | 'HIGH'
     */
    public function predict(array $sample): string;

    /**
     * Predict class probabilities and confidence score.
     *
     * @param array<string, mixed> $sample Feature associative array
     * @return array{
     *     prediction: string,
     *     confidence: float,
     *     probabilities: array<string, float>
     * }
     */
    public function predictProba(array $sample): array;

    /**
     * Unique identifier key of the algorithm (e.g. 'rule_based', 'decision_tree').
     */
    public function getKey(): string;

    /**
     * Human-readable presentation name of the algorithm.
     */
    public function getName(): string;
}
