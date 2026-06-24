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