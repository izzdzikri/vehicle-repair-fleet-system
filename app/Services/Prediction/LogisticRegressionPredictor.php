<?php

declare(strict_types=1);

namespace App\Services\Prediction;

/**
 * Class LogisticRegressionPredictor
 * 
 * Standard Multinomial (Softmax) Logistic Regression with L2 Regularization.
 * Implemented in pure PHP with gradient descent optimization.
 * 
 * Mathematical Formulation:
 * -------------------------
 * Linear Logits: z_c = W_c^T x + b_c  for class c \in {LOW, MEDIUM, HIGH}
 * Softmax Posterior: P(y = c | x) = exp(z_c) / \sum_{k} exp(z_k)
 * Objective: Minimize cross-entropy loss + (lambda / 2) * ||W||_2^2
 */
class LogisticRegressionPredictor implements PredictorInterface
{
    protected float $learningRate;
    protected int $maxIterations;
    protected float $l2Penalty;
    protected float $tolerance;

    /** @var array<string, array<int, float>> Weights W for each class */
    protected array $weights = [];

    /** @var array<string, float> Bias b for each class */
    protected array $biases = [];

    protected array $means = [];
    protected array $stds = [];
    protected array $classes = ['LOW', 'MEDIUM', 'HIGH'];

    public function __construct(?float $learningRate = null, ?int $maxIterations = null, ?float $l2Penalty = null)
    {
        $cfg = config('prediction.logistic_regression', []);
        $this->learningRate = $learningRate ?? (float) ($cfg['learning_rate'] ?? 0.05);
        $this->maxIterations = $maxIterations ?? (int) ($cfg['max_iterations'] ?? 200);
        $this->l2Penalty = $l2Penalty ?? (float) ($cfg['l2_penalty'] ?? 0.01);
        $this->tolerance = (float) ($cfg['tolerance'] ?? 1e-4);
    }

    public function getKey(): string
    {
        return 'logistic_regression';
    }

    public function getName(): string
    {
        return 'Logistic Regression';
    }

    public function fit(array $samples, array $labels): void
    {
        $n = count($samples);
        if ($n === 0 || $n !== count($labels)) {
            $this->weights = [];
            return;
        }

        // 1. Vectorize inputs
        $rawX = [];
        $y = [];
        foreach ($samples as $i => $s) {
            $rawX[] = FeatureExtractor::extractVector($s);
            $y[] = strtoupper((string) ($labels[$i] ?? 'LOW'));
        }

        // 2. Standardize features
        $scaling = FeatureExtractor::computeScalingParameters($rawX);
        $this->means = $scaling['means'];
        $this->stds = $scaling['stds'];

        $X = [];
        foreach ($rawX as $v) {
            $X[] = FeatureExtractor::scaleVector($v, $this->means, $this->stds);
        }

        $numFeatures = count($X[0]);

        // 3. Initialize weights and biases to zeros
        $this->weights = [];
        $this->biases = [];
        foreach ($this->classes as $cls) {
            $this->weights[$cls] = array_fill(0, $numFeatures, 0.0);
            $this->biases[$cls] = 0.0;
        }

        // One-hot encode targets
        $oneHotY = [];
        for ($i = 0; $i < $n; $i++) {
            $oneHotY[$i] = [];
            foreach ($this->classes as $cls) {
                $oneHotY[$i][$cls] = ($y[$i] === $cls) ? 1.0 : 0.0;
            }
        }

        // 4. Gradient Descent Optimization
        for ($iter = 0; $iter < $this->maxIterations; $iter++) {
            // Gradient accumulators
            $gradW = [];
            $gradB = [];
            foreach ($this->classes as $cls) {
                $gradW[$cls] = array_fill(0, $numFeatures, 0.0);
                $gradB[$cls] = 0.0;
            }

            // Compute forward probabilities and accumulate gradients
            for ($i = 0; $i < $n; $i++) {
                $probs = $this->computeSoftmax($X[$i]);

                foreach ($this->classes as $cls) {
                    $diff = $probs[$cls] - $oneHotY[$i][$cls];
                    $gradB[$cls] += $diff;

                    for ($j = 0; $j < $numFeatures; $j++) {
                        $gradW[$cls][$j] += $diff * $X[$i][$j];
                    }
                }
            }

            // Apply parameter updates with L2 weight decay
            $maxGrad = 0.0;
            $scale = 1.0 / $n;

            foreach ($this->classes as $cls) {
                // Bias update
                $stepB = $this->learningRate * ($gradB[$cls] * $scale);
                $this->biases[$cls] -= $stepB;
                $maxGrad = max($maxGrad, abs($stepB));

                // Weight update with Ridge penalty
                for ($j = 0; $j < $numFeatures; $j++) {
                    $l2Reg = $this->l2Penalty * $this->weights[$cls][$j];
                    $stepW = $this->learningRate * (($gradW[$cls][$j] * $scale) + $l2Reg);
                    $this->weights[$cls][$j] -= $stepW;
                    $maxGrad = max($maxGrad, abs($stepW));
                }
            }

            // Early stopping if gradient steps vanish
            if ($maxGrad < $this->tolerance) {
                break;
            }
        }
    }

    public function predict(array $sample): string
    {
        return $this->predictProba($sample)['prediction'];
    }

    public function predictProba(array $sample): array
    {
        if (empty($this->weights)) {
            return [
                'prediction'    => 'LOW',
                'confidence'    => 50.0,
                'probabilities' => ['LOW' => 50.0, 'MEDIUM' => 25.0, 'HIGH' => 25.0],
            ];
        }

        $rawVector = FeatureExtractor::extractVector($sample);
        $vector = FeatureExtractor::scaleVector($rawVector, $this->means, $this->stds);

        $probs = $this->computeSoftmax($vector);

        $probabilities = [];
        $bestClass = 'LOW';
        $maxProb = -1.0;

        foreach ($this->classes as $cls) {
            $p = round($probs[$cls] * 100.0, 1);
            $probabilities[$cls] = $p;
            if ($p > $maxProb) {
                $maxProb = $p;
                $bestClass = $cls;
            }
        }

        $confidence = min(99.0, max(50.0, $probabilities[$bestClass]));

        return [
            'prediction'    => $bestClass,
            'confidence'    => $confidence,
            'probabilities' => $probabilities,
        ];
    }

    /**
     * Compute softmax probabilities over linear class logits.
     *
     * @param array<int, float> $vector
     * @return array<string, float>
     */
    protected function computeSoftmax(array $vector): array
    {
        $logits = [];
        $numFeatures = count($vector);

        foreach ($this->classes as $cls) {
            $z = $this->biases[$cls] ?? 0.0;
            $w = $this->weights[$cls] ?? [];
            for ($j = 0; $j < $numFeatures; $j++) {
                $z += ($w[$j] ?? 0.0) * $vector[$j];
            }
            $logits[$cls] = $z;
        }

        // Numerically stable softmax via max subtraction
        $maxLogit = max($logits);
        $expSum = 0.0;
        $exps = [];

        foreach ($logits as $cls => $z) {
            $e = exp(min(20.0, max(-20.0, $z - $maxLogit)));
            $exps[$cls] = $e;
            $expSum += $e;
        }

        $probs = [];
        foreach ($exps as $cls => $e) {
            $probs[$cls] = $e / max(1e-7, $expSum);
        }

        return $probs;
    }
}
