<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model {
    protected $fillable = [
        'user_id', 'vehicle_id', 'date', 'time',
        'service_type', 'status', 'notes',
        'is_walkin', 'walkin_name', 'walkin_contact',
    ];

    protected $casts = [
        'is_walkin' => 'boolean',
        'date'      => 'date',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function vehicle() {
        return $this->belongsTo(Vehicle::class);
    }

    public function jobCard() {
        return $this->hasOne(JobCard::class);
    }

    /**
     * Customer display name — works for both registered and walk-in.
     */
    public function getCustomerNameAttribute(): string {
        if ($this->is_walkin) {
            return $this->walkin_name ?? 'Walk-in Customer';
        }
        return $this->user->name ?? '—';
    }

    /**
     * Customer contact — works for both registered and walk-in.
     */
    public function getCustomerContactAttribute(): string {
        if ($this->is_walkin) {
            return $this->walkin_contact ?? '—';
        }
        return $this->user->contact_no ?? $this->user->email ?? '—';
    }
}
