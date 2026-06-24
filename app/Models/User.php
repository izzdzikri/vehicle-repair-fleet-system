<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'contact_no',
        'company_id',
        'avatar',
        'pic_role',
        'specialties',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'specialties'       => 'array',
    ];

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function vehicles() {
        return $this->hasMany(Vehicle::class);
    }

    public function appointments() {
        return $this->hasMany(Appointment::class);
    }

    // Staff assigned job cards
    public function jobCards() {
        return $this->hasMany(JobCard::class, 'staff_id');
    }

    // ----------------------------------------------------------------
    // Avatar
    // ----------------------------------------------------------------

    public function getAvatarUrlAttribute(): string {
        if ($this->avatar && file_exists(storage_path('app/public/' . $this->avatar))) {
            return asset('storage/' . $this->avatar);
        }
        $name = urlencode($this->name ?? 'User');
        return "https://ui-avatars.com/api/?name={$name}&background=3B82F6&color=fff&size=128&bold=true";
    }

    // ----------------------------------------------------------------
    // PIC role helpers
    // ----------------------------------------------------------------

    public function isPrimaryPic(): bool {
        return $this->role === 'corporate' && $this->pic_role === 'primary';
    }

    public function isSecondaryPic(): bool {
        return $this->role === 'corporate' && $this->pic_role === 'secondary';
    }

    public function canBook(): bool {
        if ($this->status === 'inactive') return false;
        if ($this->isSecondaryPic()) return false;
        return true;
    }

    public function canManage(): bool {
        return $this->isPrimaryPic() || in_array($this->role, ['admin', 'staff']);
    }
}