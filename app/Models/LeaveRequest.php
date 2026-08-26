<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'staff_id', 'start_date', 'end_date', 'reason', 'status', 'approved_by', 'admin_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function staff() {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function approver() {
        return $this->belongsTo(User::class, 'approved_by');
    }
}