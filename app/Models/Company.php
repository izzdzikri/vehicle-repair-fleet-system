<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name', 'registration_no', 'address',
        'phone', 'email', 'person_in_charge', 'status',
    ];

    public function representatives() {
        return $this->hasMany(User::class);
    }

    public function vehicles() {
        return $this->hasManyThrough(Vehicle::class, User::class);
    }
}