<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'job_card_id', 'invoice_number', 'customer_name',
        'subtotal', 'tax_rate', 'tax_amount', 'total',
        'amount_paid', 'status', 'due_date', 'notes', 'generated_by',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function jobCard() {
        return $this->belongsTo(JobCard::class);
    }

    public function payments() {
        return $this->hasMany(Payment::class);
    }

    public function generator() {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function recalculate(): void {
        $paid = $this->payments()->sum('amount');

        if ($this->total <= 0) {
            // Nothing owed — treat as settled immediately.
            $status = 'paid';
        } elseif ($paid >= $this->total) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        } else {
            $status = 'unpaid';
        }

        $this->update(['amount_paid' => $paid, 'status' => $status]);
    }

    public function getBalanceAttribute(): float {
        return max(0, $this->total - $this->amount_paid);
    }
}