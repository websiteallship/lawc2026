<?php

namespace App\Livewire\Player;

use Livewire\Component;
use App\Models\Mission;
use App\Models\UserMission;

class MissionsQuickView extends Component
{
    public function render()
    {
        $user = auth()->user();
        if (!$user) {
            return <<<'HTML'
            <div></div>
            HTML;
        }

        // Fetch active weekly missions limit 3
        $missions = Mission::where('is_active', true)
            ->where('type', 'weekly')
            ->orderBy('difficulty', 'asc')
            ->limit(3)
            ->get();

        $userMissions = UserMission::where('user_id', $user->id)
            ->whereIn('mission_id', $missions->pluck('id'))
            ->get()
            ->keyBy('mission_id');

        return view('livewire.player.missions-quick-view', [
            'missions' => $missions,
            'userMissions' => $userMissions,
        ]);
    }
}
