<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function open(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 404);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        $url = $notification->data['url'] ?? null;

        if (is_string($url) && $url !== '') {
            return redirect()->to($url);
        }

        return redirect()->route('dashboard');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->appNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
