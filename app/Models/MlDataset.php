<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MlDataset extends Model
{
    protected $table = 'ml_datasets';

    protected $fillable = [
        'name',
        'source_type',
        'file_path',
        'row_count',
        'feature_mapping_json',
        'uploaded_by',
    ];

    protected $casts = [
        'feature_mapping_json' => 'array',
        'row_count' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function experiments(): HasMany
    {
        return $this->hasMany(MlExperiment::class, 'dataset_id');
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_type) {
            'synthetic'         => 'Synthetic System Data',
            'kaggle_benchmark'  => 'Kaggle Benchmark Dataset',
            'system_live'       => 'Live System Export',
            'unlabeled_test'    => 'Unlabeled New Fleet Data',
            default             => ucfirst($this->source_type),
        };
    }

    public function getSourceBadgeColorAttribute(): string
    {
        return match ($this->source_type) {
            'synthetic'         => 'emerald',
            'kaggle_benchmark'  => 'blue',
            'system_live'       => 'violet',
            'unlabeled_test'    => 'amber',
            default             => 'slate',
        };
    }

    public function getAbsolutePathAttribute(): string
    {
        return storage_path('app/' . ltrim($this->file_path, '/'));
    }
}
