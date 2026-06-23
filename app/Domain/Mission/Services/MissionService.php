<?php

namespace App\Domain\Mission\Services;

use App\Models\Mission;
use App\Models\UserMission;
use App\Models\UserMissionCompletion;
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
            'is_completed'  => $isCompleted,
            'completed_at'  => $isCompleted ? now() : null,
        ]);

        if ($isCompleted) {
            $this->completeMission($userId, $mission->id);
        }
    }

    /**
     * Cập nhật mission bằng giá trị tuyệt đối (overwrite, không cộng dồn).
     * Dùng cho các mission đo streak/count tuần tính từ DB.
     */
    public function trackAbsolute(int $userId, string $action, int $value): void
    {
        $mission = Mission::where('code', $action)->where('is_active', true)->first();
        if (!$mission) return;

        $userMission = UserMission::firstOrCreate(
            ['user_id' => $userId, 'mission_id' => $mission->id],
            ['current_value' => 0, 'is_completed' => false]
        );

        if ($userMission->is_completed) return;

        // Chỉ update nếu giá trị mới lớn hơn (tránh lùi progress)
        if ($value <= $userMission->current_value) return;

        $capped = min($value, $mission->target_value);
        $isCompleted = $capped >= $mission->target_value;

        $userMission->update([
            'current_value' => $capped,
            'is_completed'  => $isCompleted,
            'completed_at'  => $isCompleted ? now() : null,
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

        // ── Ghi lịch sử lũy kế — KHÔNG bao giờ xóa ──
        $weekKey = $mission->type === 'weekly'
            ? UserMissionCompletion::weekKey()
            : ($mission->type === 'daily' ? now('Asia/Ho_Chi_Minh')->format('Y-m-d') : null);

        UserMissionCompletion::firstOrCreate(
            [
                'user_id'    => $userId,
                'mission_id' => $missionId,
                'week_key'   => $weekKey,
            ],
            [
                'mission_code' => $mission->code,
                'mission_type' => $mission->type,
                'completed_at' => now(),
            ]
        );

        if ($mission->reward_achievement_id) {
            $achievement = \App\Models\Achievement::find($mission->reward_achievement_id);
            if ($achievement) {
                // Award trực tiếp achievement gắn với mission — không re-check condition
                $this->achievementService->awardDirectly($userId, $achievement);
            }
        }
    }

    public function evaluateDailyMissions(): void
    {
        $dailyMissions = Mission::where('type', 'daily')->pluck('id');
        UserMission::whereIn('mission_id', $dailyMissions)->delete();
    }

    public function evaluateWeeklyMissions(): void
    {
        // 1. Reset toàn bộ user_missions weekly (xoá progress tuần cũ)
        $weeklyMissionIds = Mission::where('type', 'weekly')->pluck('id');
        UserMission::whereIn('mission_id', $weeklyMissionIds)->delete();

        // 2. Chỉ deactivate pool rotation (WEEKLY_W*), không động vào always-on missions
        Mission::where('type', 'weekly')
            ->where('code', 'like', 'WEEKLY_W%')
            ->update(['is_active' => false]);

        // 3. Activate lại 5 missions ngẫu nhiên từ rotation pool
        $randomWeeklyIds = Mission::where('type', 'weekly')
            ->where('code', 'like', 'WEEKLY_W%')
            ->inRandomOrder()
            ->limit(5)
            ->pluck('id');

        if ($randomWeeklyIds->isNotEmpty()) {
            Mission::whereIn('id', $randomWeeklyIds)->update(['is_active' => true]);
        }

        // 4. Đảm bảo always-on missions (không có code WEEKLY_W*) luôn active
        Mission::where('type', 'weekly')
            ->where('code', 'not like', 'WEEKLY_W%')
            ->update(['is_active' => true]);
    }
}
