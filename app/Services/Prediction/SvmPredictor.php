<?php

declare(strict_types=1);

namespace App\Services\Prediction;

/**
 * Class SvmPredictor
 * 
 * Support Vector Machine (SVM) Classifier implemented in pure PHP.
 * Uses Radial Basis Function (RBF) kernel with One-vs-Rest (OvR) multiclass decomposition.
 * 
 * Mathematical Formulation:
 * -------------------------
 * Kernel: K(x, x') = exp(-gamma * ||x - x'||^2)
 * Dual Optimization: Sequential Minimal Optimization (SMO) with clipping to [0, C]
 * Margin Decision Value: f_c(x) = \sum_{i=1}^{N} \alpha_i y_i K(x_i, x) + b_c
 * Probability Calibration: Softmax across binary margin distances
 */
class SvmPredictor implements PredictorInterface
{
    protected float $c;
    protected float $gamma;
    protected float $tolerance;
    protected int $maxIterations;

    /** @var array<string, array{alphas: array<int, float>, b: float, support_vectors: array, y_binary: array}> */
    protected array $models = [];

    protected array $means = [];
    protected array $stds = [];
    protected array $classes = ['LOW', 'MEDIUM', 'HIGH'];

    public function __construct(?float $c = null, ?float $gamma = null)
    {
        $cfg = config('prediction.svm', []);
        $this->c = $c ?? (float) ($cfg['c'] ?? 1.0);
        $this->gamma = $gamma ?? (float) ($cfg['gamma'] ?? 0.1);
        $this->tolerance = (float) ($cfg['tolerance'] ?? 0.001);
        $this->maxIterations = (int) ($cfg['max_iterations'] ?? 100);
    }

    public function getKey(): string
    {
        return 'svm';
    }

    public function getName(): string
    {
        return 'SVM (RBF Kernel)';
    }

    public function fit(array $samples, array $labels): void
    {
        $n = count($samples);
        if ($n === 0 || $n !== count($labels)) {
            $this->models = [];
            return;
        }

        // 1. Vectorize samples
        $rawX = [];
        $y = [];
        foreach ($samples as $i => $s) {
            $rawX[] = FeatureExtractor::extractVector($s);
            $y[] = strtoupper((string) ($labels[$i] ?? 'LOW'));
        }

        // 2. Compute Z-score standardization parameters
        $scaling = FeatureExtractor::computeScalingParameters($rawX);
        $this->means = $scaling['means'];
        $this->stds = $scaling['stds'];

        // Scale vectors
        $X = [];
        foreach ($rawX as $v) {
            $X[] = FeatureExtractor::scaleVector($v, $this->means, $this->stds);
        }

        // Subsample for fast training convergence if dataset is large (e.g. max 300 support candidates)
        if ($n > 300) {
            $indices = array_rand($X, 300);
            $subX = [];
            $subY = [];
            foreach ($indices as $idx) {
                $subX[] = $X[$idx];
                $subY[] = $y[$idx];
            }
            $X = $subX;
            $y = $subY;
            $n = 300;
        }

        // Precompute RBF Kernel Gram Matrix
        $K = $this->computeKernelMatrix($X);

        // 3. Train One-vs-Rest Binary SVM for each class
        $this->models = [];
        foreach ($this->classes as $targetClass) {
            $binaryY = [];
            for ($i = 0; $i < $n; $i++) {
                $binaryY[$i] = ($y[$i] === $targetClass) ? 1.0 : -1.0;
            }

            $model = $this->trainBinarySvm($X, $binaryY, $K);
            $this->models[$targetClass] = $model;
        }
    }

    public function predict(array $sample): string
    {
        return $this->predictProba($sample)['prediction'];
    }

    public function predictProba(array $sample): array
    {
        if (empty($this->models)) {
            return [
                'prediction'    => 'LOW',
                'confidence'    => 50.0,
                'probabilities' => ['LOW' => 50.0, 'MEDIUM' => 25.0, 'HIGH' => 25.0],
            ];
        }

        // Extract and scale vector
        $rawVector = FeatureExtractor::extractVector($sample);
        $vector = FeatureExtractor::scaleVector($rawVector, $this->means, $this->stds);

        // Evaluate decision function f(x) for each class
        $scores = [];
        foreach ($this->classes as $cls) {
            $model = $this->models[$cls] ?? null;
            if ($model === null) {
                $scores[$cls] = 0.0;
                continue;
            }

            $f = $model['b'];
            $svs = $model['support_vectors'];
            $alphas = $model['alphas'];
            $yBinary = $model['y_binary'];

            foreach ($svs as $k => $sv) {
                $kernelVal = $this->rbfKernel($vector, $sv);
                $f += $alphas[$k] * $yBinary[$k] * $kernelVal;
            }
            $scores[$cls] = $f;
        }

        // Softmax conversion of margin scores into probabilities
        $maxScore = max($scores);
        $expSum = 0.0;
        $exps = [];
        foreach ($scores as $cls => $sc) {
            $e = exp(min(20.0, max(-20.0, $sc - $maxScore)));
            $exps[$cls] = $e;
            $expSum += $e;
        }

        $probabilities = [];
        $bestClass = 'LOW';
        $maxProb = -1.0;

        foreach ($this->classes as $cls) {
            $prob = round(($exps[$cls] / max(1e-7, $expSum)) * 100.0, 1);
            $probabilities[$cls] = $prob;
            if ($prob > $maxProb) {
                $maxProb = $prob;
                $bestClass = $cls;
            }
        }

        $confidence = min(99.0, max(50.0, $probabilities[$bestClass]));

        return [
            'prediction'    => $bestClass,
            'confidence'    => $confidence,
            'probabilities' => $probabilities,
            'margin_scores' => $scores,
        ];
    }

    /**
     * Sequential Minimal Optimization (SMO) algorithm to train a binary SVM.
     */
    protected function trainBinarySvm(array $X, array $y, array $K): array
    {
        $n = count($X);
        $alphas = array_fill(0, $n, 0.0);
        $b = 0.0;
        $passes = 0;
        $maxPasses = 10;
        $iter = 0;

        // E cache: prediction error for sample i
        $E = array_fill(0, $n, 0.0);
        for ($i = 0; $i < $n; $i++) {
            $E[$i] = - $y[$i];
        }

        while ($passes < $maxPasses && $iter < $this->maxIterations) {
            $iter++;
            $numChangedAlphas = 0;

            for ($i = 0; $i < $n; $i++) {
                $Ei = $E[$i];

                // Check KKT conditions violation
                $r = $y[$i] * $Ei;
                if (($r < - $this->tolerance && $alphas[$i] < $this->c) || ($r > $this->tolerance && $alphas[$i] > 0.0)) {
                    // Pick random second index j != i
                    $j = mt_rand(0, $n - 2);
                    if ($j >= $i) {
                        $j++;
                    }

                    $Ej = $E[$j];
                    $alphaIOld = $alphas[$i];
                    $alphaJOld = $alphas[$j];

                    // Compute L and H box bounds
                    if ($y[$i] !== $y[$j]) {
                        $L = max(0.0, $alphas[$j] - $alphas[$i]);
                        $H = min($this->c, $this->c + $alphas[$j] - $alphas[$i]);
                    } else {
                        $L = max(0.0, $alphas[$i] + $alphas[$j] - $this->c);
                        $H = min($this->c, $alphas[$i] + $alphas[$j]);
                    }

                    if ($L >= $H) {
                        continue;
                    }

                    // Compute eta = 2*K_ij - K_ii - K_jj
                    $eta = 2.0 * $K[$i][$j] - $K[$i][$i] - $K[$j][$j];
                    if ($eta >= 0.0) {
                        continue;
                    }

                    // Update alpha_j
                    $newAlphaJ = $alphas[$j] - ($y[$j] * ($Ei - $Ej)) / $eta;
                    $newAlphaJ = min($H, max($L, $newAlphaJ));

                    if (abs($newAlphaJ - $alphaJOld) < 1e-5) {
                        continue;
                    }

                    // Update alpha_i
                    $newAlphaI = $alphas[$i] + $y[$i] * $y[$j] * ($alphaJOld - $newAlphaJ);

                    // Compute threshold b
                    $b1 = $b - $Ei - $y[$i] * ($newAlphaI - $alphaIOld) * $K[$i][$i] - $y[$j] * ($newAlphaJ - $alphaJOld) * $K[$i][$j];
                    $b2 = $b - $Ej - $y[$i] * ($newAlphaI - $alphaIOld) * $K[$i][$j] - $y[$j] * ($newAlphaJ - $alphaJOld) * $K[$j][$j];

                    if ($newAlphaI > 0.0 && $newAlphaI < $this->c) {
                        $b = $b1;
                    } elseif ($newAlphaJ > 0.0 && $newAlphaJ < $this->c) {
                        $b = $b2;
                    } else {
                        $b = ($b1 + $b2) / 2.0;
                    }

                    $alphas[$i] = $newAlphaI;
                    $alphas[$j] = $newAlphaJ;

                    // Update error cache for i and j
                    $this->updateErrorCache($E, $X, $y, $alphas, $b, $K);
                    $numChangedAlphas++;
                }
            }

            if ($numChangedAlphas === 0) {
                $passes++;
            } else {
                $passes = 0;
            }
        }

        // Retain only support vectors (alphas > 1e-4) to optimize inference speed
        $supportVectors = [];
        $supportAlphas = [];
        $supportY = [];

        for ($i = 0; $i < $n; $i++) {
            if ($alphas[$i] > 1e-4) {
                $supportVectors[] = $X[$i];
                $supportAlphas[] = $alphas[$i];
                $supportY[] = $y[$i];
            }
        }

        // If no support vector selected, fallback to centroid
        if (empty($supportVectors)) {
            $supportVectors[] = $X[0];
            $supportAlphas[] = 1.0;
            $supportY[] = $y[0];
        }

        return [
            'alphas'          => $supportAlphas,
            'b'               => $b,
            'support_vectors' => $supportVectors,
            'y_binary'        => $supportY,
        ];
    }

    protected function updateErrorCache(array &$E, array $X, array $y, array $alphas, float $b, array $K): void
    {
        $n = count($X);
        for ($i = 0; $i < $n; $i++) {
            $f = $b;
            for ($k = 0; $k < $n; $k++) {
                if ($alphas[$k] > 1e-5) {
                    $f += $alphas[$k] * $y[$k] * $K[$k][$i];
                }
            }
            $E[$i] = $f - $y[$i];
        }
    }

    protected function computeKernelMatrix(array $X): array
    {
        $n = count($X);
        $K = [];
        for ($i = 0; $i < $n; $i++) {
            $K[$i][$i] = 1.0;
            for ($j = $i + 1; $j < $n; $j++) {
                $val = $this->rbfKernel($X[$i], $X[$j]);
                $K[$i][$j] = $val;
                $K[$j][$i] = $val;
            }
        }

        return $K;
    }

    protected function rbfKernel(array $x1, array $x2): float
    {
        $distSq = 0.0;
        $len = count($x1);
        for ($k = 0; $k < $len; $k++) {
            $d = ($x1[$k] ?? 0.0) - ($x2[$k] ?? 0.0);
            $distSq += $d * $d;
        }

        return exp(- $this->gamma * $distSq);
    }
}
