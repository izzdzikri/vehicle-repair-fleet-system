<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCardCheckin extends Model
{
    protected $fillable = ['job_card_id', 'coordinator_id', 'note'];

    public function jobCard() {
        return $this->belongsTo(JobCard::class);
    }

    public function coordinator() {
        return $this->belongsTo(User::class, 'coordinator_id');
    }
}