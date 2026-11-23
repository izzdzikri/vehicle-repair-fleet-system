<?php

declare(strict_types=1);

namespace App\Services\Prediction;

/**
 * Class DecisionTreePredictor
 * 
 * Standard Classification and Regression Tree (CART) implemented in pure PHP.
 * Recursively partitions feature space by minimizing Gini impurity at each decision boundary:
 * 
 *   Gini(S) = 1 - \sum_{c \in C} p_c^2
 *   \Delta Gini = Gini(Parent) - [ (N_L / N) * Gini(Left) + (N_R / N) * Gini(Right) ]
 */
class DecisionTreePredictor implements PredictorInterface
{
    protected int $maxDepth;
    protected int $minSamplesSplit;
    protected ?array $tree = null;
    protected array $classes = ['LOW', 'MEDIUM', 'HIGH'];

    /**
     * @param int|null $maxDepth
     * @param int|null $minSamplesSplit
     */
    public function __construct(?int $maxDepth = null, ?int $minSamplesSplit = null)
    {
        $cfg = config('prediction.decision_tree', []);
        $this->maxDepth = $maxDepth ?? (int) ($cfg['max_depth'] ?? 6);
        $this->minSamplesSplit = $minSamplesSplit ?? (int) ($cfg['min_samples_split'] ?? 5);
    }

    public function getKey(): string
    {
        return 'decision_tree';
    }

    public function getName(): string
    {
        return 'Decision Tree (CART)';
    }

    public function fit(array $samples, array $labels): void
    {
        if (empty($samples) || count($samples) !== count($labels)) {
            $this->tree = $this->createLeafNode(['LOW' => 1, 'MEDIUM' => 1, 'HIGH' => 1]);
            return;
        }

        // Convert raw sample maps into numeric feature vectors
        $X = [];
        $y = [];
        foreach ($samples as $i => $sample) {
            $X[] = FeatureExtractor::extractVector($sample);
            $y[] = strtoupper((string) ($labels[$i] ?? 'LOW'));
        }

        $this->tree = $this->buildTree($X, $y, 0);
    }

    public function predict(array $sample): string
    {
        return $this->predictProba($sample)['prediction'];
    }

    public function predictProba(array $sample): array
    {
        if ($this->tree === null) {
            return [
                'prediction'    => 'LOW',
                'confidence'    => 50.0,
                'probabilities' => ['LOW' => 50.0, 'MEDIUM' => 25.0, 'HIGH' => 25.0],
            ];
        }

        $vector = FeatureExtractor::extractVector($sample);
        $leaf = $this->traverseTree($this->tree, $vector);

        $probabilities = $leaf['probabilities'];
        $prediction = $leaf['prediction'];
        $confidence = $leaf['confidence'];

        return [
            'prediction'    => $prediction,
            'confidence'    => $confidence,
            'probabilities' => $probabilities,
            'split_path'    => $leaf['path'] ?? [],
        ];
    }

    /**
     * Recursive tree construction.
     */
    protected function buildTree(array $X, array $y, int $depth): array
    {
        $numSamples = count($y);
        $classCounts = $this->countClasses($y);

        // Stopping criteria: pure node, max depth reached, or fewer than min_samples_split
        if ($depth >= $this->maxDepth || $numSamples < $this->minSamplesSplit || count($classCounts) <= 1) {
            return $this->createLeafNode($classCounts);
        }

        $bestSplit = $this->findBestSplit($X, $y);
        if ($bestSplit === null) {
            return $this->createLeafNode($classCounts);
        }

        $leftNode = $this->buildTree($bestSplit['left_X'], $bestSplit['left_y'], $depth + 1);
        $rightNode = $this->buildTree($bestSplit['right_X'], $bestSplit['right_y'], $depth + 1);

        return [
            'is_leaf'   => false,
            'feature'   => $bestSplit['feature'],
            'threshold' => $bestSplit['threshold'],
            'left'      => $leftNode,
            'right'     => $rightNode,
        ];
    }

    /**
     * Find the optimal feature and threshold that maximizes Gini impurity decrease.
     */
    protected function findBestSplit(array $X, array $y, ?array $featureIndices = null): ?array
    {
        $numSamples = count($y);
        if ($numSamples <= 1) {
            return null;
        }

        $numFeatures = count($X[0]);
        $featuresToTry = $featureIndices ?? range(0, $numFeatures - 1);
        $currentGini = $this->giniImpurity($y);

        $bestGain = 0.0;
        $bestSplit = null;

        foreach ($featuresToTry as $fIdx) {
            // Extract feature values and sort them to test midpoints
            $values = [];
            foreach ($X as $i => $row) {
                $values[] = $row[$fIdx];
            }
            sort($values);
            $uniqueValues = array_values(array_unique($values));
            $countValues = count($uniqueValues);

            if ($countValues <= 1) {
                continue;
            }

            // Cap candidate split evaluations for performance (test up to 15 percentiles)
            $step = max(1, (int) floor($countValues / 15));
            for ($k = 0; $k < $countValues - 1; $k += $step) {
                $threshold = ($uniqueValues[$k] + $uniqueValues[$k + 1]) / 2.0;

                $leftY = [];
                $rightY = [];
                $leftX = [];
                $rightX = [];

                foreach ($X as $i => $row) {
                    if ($row[$fIdx] <= $threshold) {
                        $leftX[] = $row;
                        $leftY[] = $y[$i];
                    } else {
                        $rightX[] = $row;
                        $rightY[] = $y[$i];
                    }
                }

                if (empty($leftY) || empty($rightY)) {
                    continue;
                }

                $pLeft = count($leftY) / $numSamples;
                $pRight = count($rightY) / $numSamples;
                $gain = $currentGini - ($pLeft * $this->giniImpurity($leftY) + $pRight * $this->giniImpurity($rightY));

                if ($gain > $bestGain) {
                    $bestGain = $gain;
                    $bestSplit = [
                        'feature'   => $fIdx,
                        'threshold' => $threshold,
                        'left_X'    => $leftX,
                        'left_y'    => $leftY,
                        'right_X'   => $rightX,
                        'right_y'   => $rightY,
                    ];
                }
            }
        }

        return $bestSplit;
    }

    /**
     * Compute Gini Impurity: 1 - sum(p_i^2)
     */
    protected function giniImpurity(array $labels): float
    {
        $n = count($labels);
        if ($n === 0) {
            return 0.0;
        }

        $counts = array_count_values($labels);
        $sumSq = 0.0;
        foreach ($counts as $cnt) {
            $p = $cnt / $n;
            $sumSq += ($p * $p);
        }

        return 1.0 - $sumSq;
    }

    /**
     * Count frequencies of classes in sample slice.
     */
    protected function countClasses(array $labels): array
    {
        $counts = array_fill_keys($this->classes, 0);
        foreach ($labels as $label) {
            $u = strtoupper((string) $label);
            if (isset($counts[$u])) {
                $counts[$u]++;
            }
        }

        return $counts;
    }

    /**
     * Create terminal leaf representation with posterior distribution.
     */
    protected function createLeafNode(array $classCounts): array
    {
        $total = array_sum($classCounts);
        $total = max(1, $total);

        $probabilities = [];
        $bestClass = 'LOW';
        $maxCount = -1;

        foreach ($this->classes as $cls) {
            $cnt = $classCounts[$cls] ?? 0;
            $prob = round(($cnt / $total) * 100.0, 1);
            $probabilities[$cls] = $prob;

            if ($cnt > $maxCount) {
                $maxCount = $cnt;
                $bestClass = $cls;
            }
        }

        $confidence = min(99.0, max(50.0, $probabilities[$bestClass]));

        return [
            'is_leaf'       => true,
            'prediction'    => $bestClass,
            'confidence'    => $confidence,
            'probabilities' => $probabilities,
            'counts'        => $classCounts,
        ];
    }

    /**
     * Traverse decision tree until terminal leaf node is reached.
     */
    protected function traverseTree(array $node, array $vector, array $path = []): array
    {
        if ($node['is_leaf']) {
            $node['path'] = $path;
            return $node;
        }

        $fIdx = $node['feature'];
        $thresh = $node['threshold'];

        if (($vector[$fIdx] ?? 0.0) <= $thresh) {
            $path[] = "Feature #{$fIdx} <= " . round($thresh, 2);
            return $this->traverseTree($node['left'], $vector, $path);
        }

        $path[] = "Feature #{$fIdx} > " . round($thresh, 2);
        return $this->traverseTree($node['right'], $vector, $path);
    }

    /**
     * Public accessor for tree internal state (useful for Random Forest subspacing).
     */
    public function buildSubspacedTree(array $X, array $y, int $depth, float $maxFeaturesRatio): array
    {
        $numSamples = count($y);
        $classCounts = $this->countClasses($y);

        if ($depth >= $this->maxDepth || $numSamples < $this->minSamplesSplit || count($classCounts) <= 1) {
            return $this->createLeafNode($classCounts);
        }

        $numFeatures = count($X[0]);
        $subspaceCount = max(1, (int) round($numFeatures * $maxFeaturesRatio));
        $allIndices = range(0, $numFeatures - 1);
        shuffle($allIndices);
        $featureSubset = array_slice($allIndices, 0, $subspaceCount);

        $bestSplit = $this->findBestSplit($X, $y, $featureSubset);
        if ($bestSplit === null) {
            return $this->createLeafNode($classCounts);
        }

        $left = $this->buildSubspacedTree($bestSplit['left_X'], $bestSplit['left_y'], $depth + 1, $maxFeaturesRatio);
        $right = $this->buildSubspacedTree($bestSplit['right_X'], $bestSplit['right_y'], $depth + 1, $maxFeaturesRatio);

        return [
            'is_leaf'   => false,
            'feature'   => $bestSplit['feature'],
            'threshold' => $bestSplit['threshold'],
            'left'      => $left,
            'right'     => $right,
        ];
    }

    public function setTree(array $tree): void
    {
        $this->tree = $tree;
    }
}
