<?php

declare(strict_types=1);

namespace App\Services\Prediction;

/**
 * Class RandomForestPredictor
 * 
 * Standard Random Forest (Breiman, 2001) Bagging Ensemble in pure PHP.
 * Trains an ensemble of B decorrelated decision trees on bootstrap resamples
 * with random feature subspacing, aggregating tree votes into a posterior probability distribution.
 */
class RandomForestPredictor implements PredictorInterface
{
    protected int $nEstimators;
    protected int $maxDepth;
    protected int $minSamplesSplit;
    protected float $maxFeaturesRatio;

    /** @var array<int, array> Serialized tree structures */
    protected array $trees = [];

    protected array $classes = ['LOW', 'MEDIUM', 'HIGH'];

    public function __construct(?int $nEstimators = null, ?int $maxDepth = null)
    {
        $cfg = config('prediction.random_forest', []);
        $this->nEstimators = $nEstimators ?? (int) ($cfg['n_estimators'] ?? 30);
        $this->maxDepth = $maxDepth ?? (int) ($cfg['max_depth'] ?? 7);
        $this->minSamplesSplit = (int) ($cfg['min_samples_split'] ?? 4);
        $this->maxFeaturesRatio = (float) ($cfg['max_features_ratio'] ?? 0.6);
    }

    public function getKey(): string
    {
        return 'random_forest';
    }

    public function getName(): string
    {
        return 'Random Forest (' . $this->nEstimators . ' Trees)';
    }

    public function fit(array $samples, array $labels): void
    {
        $n = count($samples);
        if ($n === 0 || $n !== count($labels)) {
            $this->trees = [];
            return;
        }

        // Vectorize all input samples
        $X = [];
        $y = [];
        foreach ($samples as $i => $sample) {
            $X[] = FeatureExtractor::extractVector($sample);
            $y[] = strtoupper((string) ($labels[$i] ?? 'LOW'));
        }

        $this->trees = [];
        $treeBuilder = new DecisionTreePredictor($this->maxDepth, $this->minSamplesSplit);

        // Train B randomized trees
        for ($b = 0; $b < $this->nEstimators; $b++) {
            // 1. Bootstrap sample (with replacement)
            $bootX = [];
            $bootY = [];
            for ($i = 0; $i < $n; $i++) {
                $randIdx = mt_rand(0, $n - 1);
                $bootX[] = $X[$randIdx];
                $bootY[] = $y[$randIdx];
            }

            // 2. Build tree with random feature subspacing
            $tree = $treeBuilder->buildSubspacedTree($bootX, $bootY, 0, $this->maxFeaturesRatio);
            $this->trees[] = $tree;
        }
    }

    public function predict(array $sample): string
    {
        return $this->predictProba($sample)['prediction'];
    }

    public function predictProba(array $sample): array
    {
        if (empty($this->trees)) {
            return [
                'prediction'    => 'LOW',
                'confidence'    => 50.0,
                'probabilities' => ['LOW' => 50.0, 'MEDIUM' => 25.0, 'HIGH' => 25.0],
                'vote_counts'   => ['LOW' => 0, 'MEDIUM' => 0, 'HIGH' => 0],
            ];
        }

        $vector = FeatureExtractor::extractVector($sample);
        $votes = array_fill_keys($this->classes, 0);
        $treeHelper = new DecisionTreePredictor();

        foreach ($this->trees as $tree) {
            $treeHelper->setTree($tree);
            $pred = $treeHelper->predict($sample);
            if (isset($votes[$pred])) {
                $votes[$pred]++;
            }
        }

        $totalTrees = count($this->trees);
        $probabilities = [];
        $bestClass = 'LOW';
        $maxVotes = -1;

        foreach ($this->classes as $cls) {
            $v = $votes[$cls];
            $probabilities[$cls] = round(($v / $totalTrees) * 100.0, 1);
            if ($v > $maxVotes) {
                $maxVotes = $v;
                $bestClass = $cls;
            }
        }

        $confidence = min(99.0, max(50.0, $probabilities[$bestClass]));

        return [
            'prediction'    => $bestClass,
            'confidence'    => $confidence,
            'probabilities' => $probabilities,
            'vote_counts'   => $votes,
            'total_trees'   => $totalTrees,
        ];
    }
}
