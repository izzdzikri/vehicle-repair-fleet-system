<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCard extends Model
{
    protected $fillable = [
        'appointment_id', 'vehicle_id', 'staff_id', 'job_type_id',
        'current_stage', 'diagnosis', 'symptoms', 'technician_notes',
        'total_cost', 'estimated_completion', 'completed_at',
    ];

    protected $casts = [
        'estimated_completion' => 'datetime',
        'completed_at'         => 'datetime',
        'symptoms'             => 'array',
    ];

    public function appointment() {
        return $this->belongsTo(Appointment::class);
    }

    public function vehicle() {
        return $this->belongsTo(Vehicle::class);
    }

    public function staff() {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function jobType() {
        return $this->belongsTo(JobType::class);
    }

    public function parts() {
        return $this->hasMany(JobCardPart::class);
    }

    public function serviceHistory() {
        return $this->hasOne(ServiceHistory::class);
    }

    public function labourCharges() {
        return $this->hasMany(LabourCharge::class);
    }

    public function invoice() {
        return $this->hasOne(Invoice::class);
    }

    public function checkins() {
        return $this->hasMany(JobCardCheckin::class)->latest();
    }

    /**
     * "Stale" = not completed, and hasn't had ANY touch (stage change,
     * parts/labour/notes added) in over 3 hours. Used to nudge the
     * coordinator to check in on jobs that may have been forgotten
     * mid-repair — a busy mechanic under a car may simply not have had
     * a moment to log in and update the system.
     */
    public function getIsStaleAttribute(): bool {
        if ($this->current_stage === 'completed') return false;
        return $this->updated_at->lt(now()->subHours(3));
    }

    public function getHoursSinceUpdateAttribute(): int {
        return (int) $this->updated_at->diffInHours(now());
    }

    public function getLastCheckinAttribute() {
        return $this->relationLoaded('checkins') ? $this->checkins->first() : $this->checkins()->first();
    }
}