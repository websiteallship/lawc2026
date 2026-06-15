<?php

namespace App\Livewire\Player;

use Livewire\Component;

class AchievementBadge extends Component
{
    public int $unreadCount = 0;

    protected $listeners = [
        'database-notifications.sent' => 'loadUnreadCount'
    ];

    public function mount()
    {
        $this->loadUnreadCount();
    }

    public function loadUnreadCount()
    {
        $user = filament()->auth()->user();
        $this->unreadCount = $user
            ? $user->unreadNotifications()
                ->where('data', 'like', '%heroicon-o-trophy%')
                ->count()
            : 0;
    }

    public function render()
    {
        return view('livewire.player.achievement-badge');
    }
}
