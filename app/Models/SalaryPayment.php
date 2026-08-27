<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $fillable = [
        'staff_id', 'period', 'amount', 'method', 'paid_at', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'paid_at' => 'date',
    ];

    public function staff() {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function recorder() {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}