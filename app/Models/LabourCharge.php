<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LabourCharge extends Model {
    protected $fillable = ['job_card_id', 'description', 'charge', 'remark'];

    public function jobCard() {
        return $this->belongsTo(JobCard::class);
    }
}