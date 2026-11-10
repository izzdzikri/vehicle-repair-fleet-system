<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MlExperiment extends Model
{
    protected $table = 'ml_experiments';

    protected $fillable = [
        'dataset_id',
        'run_name',
        'split_ratio',
        'cv_folds',
        'status',
        'error_message',
        'summary_json',
        'hyperparams_json',
        'run_by',
    ];

    protected $casts = [
        'split_ratio'      => 'float',
        'cv_folds'         => 'integer',
        'summary_json'     => 'array',
        'hyperparams_json' => 'array',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(MlDataset::class, 'dataset_id');
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(MlModelMetric::class, 'experiment_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(MlPredictionComparison::class, 'experiment_id');
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'emerald',
            'running'   => 'blue',
            'queued'    => 'amber',
            'failed'    => 'rose',
            default     => 'slate',
        };
    }

    public function getModelDir(): string
    {
        return storage_path("app/ml_models/exp_{$this->id}");
    }
}
