<?php

namespace App\View\Components\header;

use App\Models\Notification;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class NotificationDropdown extends Component
{
    /** @var Collection<int, Notification> */
    public Collection $notifications;

    public int $unreadCount;

    public function __construct()
    {
        $user = auth()->user();

        $this->notifications = $user
            ? $user->appNotifications()->latest('id')->limit(20)->get()
            : collect();

        $this->unreadCount = $user
            ? (int) $user->appNotifications()->unread()->count()
            : 0;
    }

    public function render(): View|Closure|string
    {
        return view('components.header.notification-dropdown', [
            'notifications' => $this->notifications,
            'unreadCount' => $this->unreadCount,
        ]);
    }
}
