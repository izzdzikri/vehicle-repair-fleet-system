<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MaintenanceAlert extends Model
{
    protected $fillable = [
        'vehicle_id', 'source', 'job_type_id', 'alert_type', 'urgency',
        'recommendation', 'predicted_due_date', 'is_read',
        'is_verified', 'verified_by', 'verified_at', 'admin_notes',
        'overridden_due_date', 'overridden_urgency',
        // ML R&D fields — additive only, nullable
        'predicted_by_algo', 'confidence_score', 'ml_staff_verified', 'ml_verified_by_user_id',
    ];

    protected $casts = [
        'predicted_due_date' => 'date',
        'overridden_due_date' => 'date',
        'is_read' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getEffectiveDueDateAttribute(): ?Carbon
    {
        $val = $this->overridden_due_date ?: $this->predicted_due_date;
        if (! $val) {
            return null;
        }

        return $val instanceof Carbon ? $val : Carbon::parse($val);
    }

    public function getEffectiveUrgencyAttribute(): string
    {
        return $this->attributes['overridden_urgency'] ?? ($this->urgency ?? 'medium');
    }

    public function getIsOverriddenAttribute(): bool
    {
        return ! empty($this->attributes['overridden_due_date']) || ! empty($this->attributes['overridden_urgency']);
    }

    public function getIsVerifiedAttribute(): bool
    {
        return (bool) ($this->attributes['is_verified'] ?? false);
    }
}
