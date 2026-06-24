<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class JobCardPart extends Model {
    protected $fillable = [
        'job_card_id','spare_part_id','quantity','unit_price'
    ];
    public function jobCard() {
        return $this->belongsTo(JobCard::class);
    }
    public function sparePart() {
        return $this->belongsTo(SparePart::class);
    }
}