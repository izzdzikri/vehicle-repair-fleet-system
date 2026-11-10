<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlModelMetric extends Model
{
    protected $table = 'ml_model_metrics';

    protected $fillable = [
        'experiment_id',
        'algorithm',
        'accuracy',
        'precision_macro',
        'recall_macro',
        'f1_macro',
        'high_urgency_recall',
        'confusion_matrix_json',
        'yearly_accuracy_json',
        'extra_meta_json',
    ];

    protected $casts = [
        'accuracy'             => 'float',
        'precision_macro'      => 'float',
        'recall_macro'         => 'float',
        'f1_macro'             => 'float',
        'high_urgency_recall'  => 'float',
        'confusion_matrix_json' => 'array',
        'yearly_accuracy_json'  => 'array',
        'extra_meta_json'       => 'array',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(MlExperiment::class, 'experiment_id');
    }

    public function getAlgorithmLabelAttribute(): string
    {
        return match ($this->algorithm) {
            'rule_based'    => 'Rule-Based Baseline',
            'decision_tree' => 'Decision Tree (CART)',
            'random_forest' => 'Random Forest',
            'svm'           => 'SVM (RBF)',
            default         => ucfirst(str_replace('_', ' ', $this->algorithm)),
        };
    }

    public function getAccuracyPctAttribute(): float
    {
        return round(($this->accuracy ?? 0) * 100, 2);
    }

    public function getF1PctAttribute(): float
    {
        return round(($this->f1_macro ?? 0) * 100, 2);
    }

    public function getHighRecallPctAttribute(): float
    {
        return round(($this->high_urgency_recall ?? 0) * 100, 2);
    }
}
