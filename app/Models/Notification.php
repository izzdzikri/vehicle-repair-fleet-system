<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'title', 'message', 'url', 'is_read'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public static function send($userId, string $title, string $message, ?string $url = null): void {
        if (!$userId) return;
        static::create(['user_id' => $userId, 'title' => $title, 'message' => $message, 'url' => $url]);
    }

    public static function sendToMany($userIds, string $title, string $message, ?string $url = null): void {
        foreach ($userIds as $id) {
            static::send($id, $title, $message, $url);
        }
    }
}