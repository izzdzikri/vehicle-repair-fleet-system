<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'description', 'subject_type', 'subject_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an activity entry. $subject is optional — pass the model
     * instance the action relates to (e.g. the User, Invoice, JobType)
     * so subject_type/subject_id are captured for future reference.
     */
    public static function record(string $action, string $description, $subject = null): void {
        static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject->id ?? null,
        ]);
    }
}