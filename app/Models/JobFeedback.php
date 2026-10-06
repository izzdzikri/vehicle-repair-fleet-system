<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobFeedback extends Model
{
    protected $table = 'job_feedbacks';

    protected $fillable = ['job_card_id', 'rating', 'comment'];

    public function jobCard() {
        return $this->belongsTo(JobCard::class);
    }
}