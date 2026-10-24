<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    public const LEAVE_TYPES = [
        'annual'    => 'Annual Leave (AL)',
        'medical'   => 'Medical Leave (MC)',
        'emergency' => 'Emergency Leave (EL)',
        'unpaid'    => 'Unpaid Leave',
    ];

    protected $fillable = [
        'staff_id', 'leave_type', 'start_date', 'end_date', 'reason', 'status', 'approved_by', 'admin_notes',
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

    public function getDaysCountAttribute(): int {
        if (!$this->start_date || !$this->end_date) return 1;
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getLeaveTypeLabelAttribute(): string {
        return self::LEAVE_TYPES[$this->leave_type] ?? ucfirst($this->leave_type ?? 'Annual Leave');
    }
}
