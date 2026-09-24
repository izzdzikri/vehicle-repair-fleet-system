<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MaintenanceAlert extends Model {
    protected $fillable = [
        'vehicle_id', 'source', 'job_type_id', 'alert_type', 'urgency',
        'recommendation', 'predicted_due_date', 'is_read',
    ];

    protected $casts = [
        'predicted_due_date' => 'date',
        'is_read'            => 'boolean',
    ];

    public function vehicle() {
        return $this->belongsTo(Vehicle::class);
    }

    public function jobType() {
        return $this->belongsTo(JobType::class);
    }
}