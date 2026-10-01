<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCard extends Model
{
    protected $fillable = [
        'appointment_id', 'vehicle_id', 'staff_id', 'job_type_id',
        'current_stage', 'diagnosis', 'symptoms', 'technician_notes',
        'inspection_photos', 'total_cost', 'estimated_completion', 'completed_at',
    ];

    protected $casts = [
        'estimated_completion' => 'datetime',
        'completed_at'         => 'datetime',
        'symptoms'             => 'array',
        'inspection_photos'    => 'array',
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

    public function feedback() {
        return $this->hasOne(JobFeedback::class);
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

    /**
     * Can this user edit this job card (update stage, add parts/labour,
     * update diagnosis)? Admins and coordinators can edit any job card
     * unconditionally — coordinators are a workshop-assistant role with
     * the same edit surface as a mechanic, granted regardless of
     * ownership since a coordinator has no "own" jobs to scope to (see
     * CoordinatorController's class-level doc-comment for the full
     * rationale). Staff holding job_cards.manage_all can also edit any
     * job card. A regular mechanic can only edit jobs assigned to them —
     * they can still VIEW every other job card, just not change it.
     */
    public function canBeEditedBy(User $user): bool {
        if ($user->role === 'admin') return true;
        if ($user->role === 'coordinator') return true;
        if ($user->hasPermission('job_cards.manage_all')) return true;
        return $this->staff_id === $user->id;
    }
}