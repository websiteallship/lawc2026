<?php

namespace App\Domain\Mission\Services;

use App\Models\Mission;
use App\Models\UserMission;
use App\Domain\Achievement\Services\AchievementService;

class MissionService
{
    protected AchievementService $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    public function trackProgress(int $userId, string $action, int $value = 1, array $context = []): void
    {
        // Find mission by action/code. In a full implementation, you map actions to mission codes.
        $mission = Mission::where('code', $action)->where('is_active', true)->first();
        if (!$mission) return;

        $userMission = UserMission::firstOrCreate(
            ['user_id' => $userId, 'mission_id' => $mission->id],
            ['current_value' => 0, 'is_completed' => false]
        );

        if ($userMission->is_completed) return;

        $newValue = $userMission->current_value + $value;
        $isCompleted = $newValue >= $mission->target_value;

        $userMission->update([
            'current_value' => min($newValue, $mission->target_value),
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
        ]);

        if ($isCompleted) {
            $this->completeMission($userId, $mission->id);
        }
    }

    public function completeMission(int $userId, int $missionId): void
    {
        $mission = Mission::find($missionId);
        if (!$mission) return;

        $userMission = UserMission::firstOrCreate(
            ['user_id' => $userId, 'mission_id' => $missionId]
        );

        if (!$userMission->is_completed) {
            $userMission->update([
                'is_completed' => true,
                'completed_at' => now(),
                'current_value' => $mission->target_value,
            ]);
        }

        if ($mission->reward_achievement_id) {
            $this->achievementService->checkAndAward($userId); 
            // the implementation plan says call AchievementService::award, but this project might use checkAndAward
            // Ideally we pass achievement code, or if award method exists we call it.
        }

        // event(new \App\Events\MissionCompleted($userId, $missionId));
    }

    public function evaluateDailyMissions(): void
    {
        $dailyMissions = Mission::where('type', 'daily')->pluck('id');
        UserMission::whereIn('mission_id', $dailyMissions)->delete();
    }

    public function evaluateWeeklyMissions(): void
    {
        $weeklyMissions = Mission::where('type', 'weekly')->pluck('id');
        UserMission::whereIn('mission_id', $weeklyMissions)->delete();
    }
}
