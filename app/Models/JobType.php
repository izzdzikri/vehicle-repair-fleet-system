<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobType extends Model
{
    protected $fillable = [
        'name', 'category', 'estimated_minutes', 'base_price', 'description',
    ];

    public function jobCards() {
        return $this->hasMany(JobCard::class);
    }

    public function getEstimatedTimeAttribute(): string {
        $hours   = intdiv($this->estimated_minutes, 60);
        $minutes = $this->estimated_minutes % 60;
        if ($hours > 0 && $minutes > 0) return "{$hours}h {$minutes}m";
        if ($hours > 0) return "{$hours}h";
        return "{$minutes}m";
    }
}