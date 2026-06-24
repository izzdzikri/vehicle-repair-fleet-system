<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ServiceHistory extends Model
{
    protected $table = 'service_history';

    protected $fillable = [
        'vehicle_id','job_card_id','service_date','description','cost'
    ];

    public function vehicle() {
        return $this->belongsTo(Vehicle::class);
    }

    public function jobCard() {
        return $this->belongsTo(JobCard::class);
    }
}