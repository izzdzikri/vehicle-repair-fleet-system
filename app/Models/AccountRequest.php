<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AccountRequest extends Model {
    protected $fillable = [
        'requested_by','company_id','type',
        'target_name','target_email','target_phone',
        'target_user_id','status','notes',
    ];

    public function requester()   { return $this->belongsTo(User::class, 'requested_by'); }
    public function company()     { return $this->belongsTo(Company::class); }
    public function targetUser()  { return $this->belongsTo(User::class, 'target_user_id'); }
}