<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobType extends Model
{
    protected $fillable = [
        'name', 'category', 'estimated_minutes', 'base_price', 'description',
        'interval_km', 'interval_months',
    ];

    public function jobCards() {
        return $this->hasMany(JobCard::class);
    }

    public function getEstimatedTimeAttribute(): string {
        $hours   = intdiv($this->estimated_minutes, 60);
        $minutes = $this->estimated_minutes % 60;
        if ($hours > 0 && $minutes > 0) return "{$hours}h {$minutes}m";
        if ($hours > 0) return "{$hours}h";
        return "{$minutes}m";
    }

    /**
     * Whether this job type has enough schedule data (km and/or time
     * interval) for MaintenancePredictionService to project a next-due
     * date for it. Wear/fault-triggered services (brakes, battery,
     * diagnostics) intentionally have neither set.
     */
    public function isIntervalTracked(): bool {
        return $this->interval_km !== null || $this->interval_months !== null;
    }

    public function getIntervalLabelAttribute(): string {
        if (!$this->isIntervalTracked()) return '—';
        $parts = [];
        if ($this->interval_km) $parts[] = number_format($this->interval_km) . ' km';
        if ($this->interval_months) $parts[] = $this->interval_months . ' mo';
        return implode(' / ', $parts);
    }
}