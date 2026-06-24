<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparePart extends Model
{
    protected $fillable = [
        'name', 'category', 'brand', 'part_number',
        'stock', 'min_stock', 'max_stock', 'unit_price',
        'expiry_date', 'manufacture_date',
        'supplier_name', 'supplier_contact', 'is_special_order',
    ];

    protected $casts = [
        'expiry_date'      => 'date',
        'manufacture_date' => 'date',
        'is_special_order' => 'boolean',
    ];

    public function jobCardParts() {
        return $this->hasMany(JobCardPart::class);
    }

    public function isLowStock(): bool {
        return $this->stock <= $this->min_stock;
    }

    public function isOverStock(): bool {
        return $this->stock >= $this->max_stock;
    }

    public function stockStatus(): string {
        if ($this->stock <= 0) return 'out';
        if ($this->isLowStock()) return 'low';
        return 'ok';
    }
}