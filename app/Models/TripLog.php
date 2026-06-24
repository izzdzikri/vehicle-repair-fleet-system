<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TripLog extends Model {
    protected $fillable = [
        'vehicle_id','trip_date','distance_km','terrain_type','notes'
    ];
    public function vehicle() {
        return $this->belongsTo(Vehicle::class);
    }
}