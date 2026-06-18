<?php

namespace App\Domain\Modal\Checkers;

use App\Domain\Modal\Contracts\CelebrationCheckerInterface;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserMission;
use Illuminate\Support\Carbon;

class CelebrationChecker implements CelebrationCheckerInterface
{
    public function shouldShow(User $user): bool
    {
        $since = $user->last_celebration_shown_at ?? now()->subYear();

        $hasNewAchievement = UserAchievement::where('user_id', $user->id)
            ->where('awarded_at', '>', $since)
            ->exists();

        $hasCompletedMission = UserMission::where('user_id', $user->id)
            ->where('is_completed', true)
            ->where('completed_at', '>', $since)
            ->exists();

        return $hasNewAchievement || $hasCompletedMission;
    }

    public function getData(User $user): array
    {
        $since = $user->last_celebration_shown_at ?? now()->subYear();

        $newAchievements = UserAchievement::with('achievement')
            ->where('user_id', $user->id)
            ->where('awarded_at', '>', $since)
            ->orderBy('awarded_at', 'desc')
            ->limit(5)
            ->get();

        $completedMissions = UserMission::with('mission.rewardAchievement')
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->where('completed_at', '>', $since)
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'achievements'      => $newAchievements,
            'missions'          => $completedMissions,
            'hasAchievements'   => $newAchievements->isNotEmpty(),
            'hasMissions'       => $completedMissions->isNotEmpty(),
        ];
    }

    public function markAsSeen(User $user): void
    {
        $user->updateQuietly(['last_celebration_shown_at' => now()]);
    }
}
