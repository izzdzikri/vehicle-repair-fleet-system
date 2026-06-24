<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model {
    protected $fillable = ['user_id', 'session_token', 'messages'];
    protected $casts    = ['messages' => 'array'];
}