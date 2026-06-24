<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MaintenanceAlert extends Model {
    protected $fillable = [
        'vehicle_id','alert_type','urgency','recommendation','is_read'
    ];
    public function vehicle() {
        return $this->belongsTo(Vehicle::class);
    }
}