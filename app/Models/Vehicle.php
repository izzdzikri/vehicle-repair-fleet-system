<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model {
    protected $fillable = [
        'user_id','plate_number','brand','model','year','mileage'
    ];
    public function owner() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isFleet(): bool {
        return $this->owner && $this->owner->role === 'corporate';
    }

    public function isIndividual(): bool {
        return !$this->isFleet();
    }

    public function getOwnerTypeLabelAttribute(): string {
        return $this->isFleet() ? 'Corporate Fleet' : 'Individual Customer';
    }

    public function getOwnerDisplayNameAttribute(): string {
        if (!$this->owner) {
            return 'Unassigned Owner';
        }
        if ($this->isFleet() && $this->owner->company) {
            return $this->owner->company->name . ' (PIC: ' . $this->owner->name . ')';
        }
        return $this->owner->name;
    }

    public function getCompanyAttribute() {
        return ($this->owner && $this->owner->company) ? $this->owner->company : null;
    }
    public function appointments() {
        return $this->hasMany(Appointment::class);
    }
    public function jobCards() {
        return $this->hasMany(JobCard::class);
    }
    public function serviceHistory() {
        return $this->hasMany(ServiceHistory::class);
    }
    public function tripLogs() {
        return $this->hasMany(TripLog::class);
    }
    public function maintenanceAlerts() {
        return $this->hasMany(MaintenanceAlert::class);
    }
}