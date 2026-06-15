<?php

namespace App\Livewire\Player;

use Livewire\Component;
use App\Models\Mission;
use App\Models\UserMission;

class WeeklyMissionsWidget extends Component
{
    public function render()
    {
        $user = auth()->user();
        if (!$user) {
            return <<<'HTML'
            <div></div>
            HTML;
        }

        // Fetch active weekly missions
        $missions = Mission::where('is_active', true)
            ->where('type', 'weekly')
            ->orderBy('difficulty', 'asc')
            ->get();

        $userMissions = UserMission::where('user_id', $user->id)
            ->whereIn('mission_id', $missions->pluck('id'))
            ->get()
            ->keyBy('mission_id');

        // General progress: completed missions count out of 5 (since we rotate and have 5 active weekly missions)
        $completedCount = $userMissions->where('is_completed', true)->count();
        $totalCount = $missions->count();

        // Calculate time remaining in the current week
        // Weekly missions end on Sunday 23:59:59
        $endOfWeek = now()->endOfWeek();
        $diff = now()->diff($endOfWeek);
        $timeRemaining = "Còn " . $diff->d . " ngày " . $diff->h . " giờ";

        return view('livewire.player.weekly-missions-widget', [
            'missions' => $missions,
            'userMissions' => $userMissions,
            'completedCount' => $completedCount,
            'totalCount' => $totalCount ?: 5,
            'timeRemaining' => $timeRemaining,
        ]);
    }
}
