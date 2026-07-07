<?php

namespace App\Livewire\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Topbar notification bell: unread count badge + a dropdown with the
 * most recent notifications, polling every 30s so it stays fresh
 * without a page reload.
 */
class Bell extends Component
{
    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function recent(): Collection
    {
        return Auth::user()->notifications()->latest()->limit(8)->get();
    }

    public function markAsRead(string $id): void
    {
        $notification = Auth::user()->notifications()->whereKey($id)->first();

        $notification?->markAsRead();

        unset($this->unreadCount, $this->recent);
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();

        unset($this->unreadCount, $this->recent);
    }

    public function render()
    {
        return view('livewire.notifications.bell');
    }
}
