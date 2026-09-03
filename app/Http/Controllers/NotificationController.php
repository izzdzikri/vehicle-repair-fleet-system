<?php
namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function go(Notification $notification) {
        abort_unless($notification->user_id === auth()->id(), 403);
        $notification->update(['is_read' => true]);
        return redirect($notification->url ?? '/');
    }

    public function readAll() {
        Notification::where('user_id', auth()->id())->where('is_read', false)->update(['is_read' => true]);
        return back();
    }
}