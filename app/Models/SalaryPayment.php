<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $fillable = [
        'staff_id', 'period', 'amount', 'base_salary', 'allowances', 'deductions', 'method', 'paid_at', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'paid_at'     => 'date',
        'amount'      => 'decimal:2',
        'base_salary' => 'decimal:2',
        'allowances'  => 'decimal:2',
        'deductions'  => 'decimal:2',
    ];

    public function staff() {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function recorder() {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getSlipNumberAttribute(): string {
        $p = str_replace('-', '', $this->period);
        return 'PAY-' . $p . '-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }
}
