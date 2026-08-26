<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'supplier_id', 'spare_part_id', 'quantity', 'unit_cost',
        'status', 'auto_generated', 'ordered_at', 'received_at',
        'notes', 'created_by',
    ];

    protected $casts = [
        'auto_generated' => 'boolean',
        'ordered_at'     => 'datetime',
        'received_at'    => 'datetime',
    ];

    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }

    public function sparePart() {
        return $this->belongsTo(SparePart::class);
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}