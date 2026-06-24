<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCard extends Model
{
    protected $fillable = [
        'appointment_id', 'vehicle_id', 'staff_id', 'job_type_id',
        'current_stage', 'diagnosis', 'symptoms', 'technician_notes',
        'total_cost', 'estimated_completion',
    ];

    protected $casts = [
        'estimated_completion' => 'datetime',
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
}
