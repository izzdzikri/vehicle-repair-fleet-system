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
        'predicted_by_algo', 'confidence_score', 'agreement_rate', 'ensemble_votes_json', 'ml_staff_verified', 'ml_verified_by_user_id',
    ];

    protected $casts = [
        'predicted_due_date' => 'date',
        'overridden_due_date' => 'date',
        'is_read' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'ensemble_votes_json' => 'array',
        'confidence_score' => 'float',
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

    /**
     * Plain-language agreement format (supervisor requirement: e.g. '4 of 5 models agreed')
     */
    public function getPlainAgreementRateAttribute(): ?string
    {
        if (! $this->agreement_rate) {
            return null;
        }

        $parts = explode('/', (string) $this->agreement_rate);
        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            return "{$parts[0]} of {$parts[1]} models agreed";
        }

        return (string) $this->agreement_rate;
    }

    /**
     * Plain-language confidence label (never show raw model names)
     */
    public function getPlainConfidenceAttribute(): string
    {
        if ($this->confidence_score !== null) {
            return round((float) $this->confidence_score) . '% Confidence';
        }

        return 'Standard Interval';
    }

    /**
     * Plain-language verification status
     */
    public function getPlainVerificationStatusAttribute(): string
    {
        if ($this->is_verified || $this->ml_staff_verified === 'verified') {
            return 'Verified';
        }

        if ($this->ml_staff_verified === 'rejected') {
            return 'Flagged';
        }

        return 'Pending Review';
    }
}
