<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['Owner', 'Staff']), 403);

        if (
            $notification->notifiable_type !== User::class
            || (int) $notification->notifiable_id !== (int) $request->user()->id
        ) {
            abort(404);
        }

        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['Owner', 'Staff']), 403);

        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return back();
    }
}
