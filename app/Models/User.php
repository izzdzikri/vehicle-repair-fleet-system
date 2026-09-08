<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Staff sub-roles (a staff member has exactly one, purely for display/labeling).
     */
    public const STAFF_ROLES = [
        'mechanic'          => 'Mechanic',
        'accountant'        => 'Accountant',
        'inventory_manager' => 'Inventory Manager',
        'front_desk'        => 'Front Desk / Receiving',
    ];

    /**
     * Granular permissions — a staff member can hold multiple at once
     * (e.g. Accountant + Inventory Manager), independent of staff_role.
     * Admins implicitly have every permission (see hasPermission()).
     */
    public const PERMISSIONS = [
        'inventory.manage'      => 'Manage Inventory (add/edit/delete parts & stock)',
        'pricing.manage'        => 'Manage Pricing (job types & part prices)',
        'invoice.manage'        => 'Generate Invoices & Record Payments',
        'job_cards.manage_all'  => 'Edit Any Job Card (not just their own assigned jobs)',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'staff_role',
        'permissions',
        'status',
        'contact_no',
        'company_id',
        'avatar',
        'pic_role',
        'specialties',
        'monthly_salary',
        'hire_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'specialties'       => 'array',
        'permissions'       => 'array',
        'hire_date'         => 'date',
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

    public function jobCards() {
        return $this->hasMany(JobCard::class, 'staff_id');
    }

    public function attendances() {
        return $this->hasMany(StaffAttendance::class, 'staff_id');
    }

    public function leaveRequests() {
        return $this->hasMany(LeaveRequest::class, 'staff_id');
    }

    public function salaryPayments() {
        return $this->hasMany(SalaryPayment::class, 'staff_id');
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

    // ----------------------------------------------------------------
    // Staff sub-role / permission helpers
    // ----------------------------------------------------------------

    /**
     * Admins implicitly hold every permission. Staff need it explicitly
     * granted via the permissions[] array (set on their profile page).
     */
    public function hasPermission(string $permission): bool {
        if ($this->role === 'admin') return true;
        return in_array($permission, $this->permissions ?? [], true);
    }

    public function getStaffRoleLabelAttribute(): ?string {
        return self::STAFF_ROLES[$this->staff_role] ?? null;
    }
}