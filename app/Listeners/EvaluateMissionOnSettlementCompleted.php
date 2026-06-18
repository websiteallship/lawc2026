<?php

namespace App\Listeners;

use App\Events\SettlementCompleted;
use App\Jobs\EvaluateMissionsJob;
use App\Models\Bet;
use App\Models\Mission;
use App\Models\UserStatistic;
use Carbon\Carbon;

class EvaluateMissionOnSettlementCompleted
{
    public function __construct() {}

    public function handle(SettlementCompleted $event): void
    {
        $userId = $event->userId;

        $activeCodes = Mission::where('is_active', true)
            ->whereIn('code', ['WEEKLY_W5', 'WEEKLY_W6', 'WEEKLY_W7', 'WEEKLY_W9', 'WEEKLY_W10'])
            ->pluck('code');

        if ($activeCodes->isEmpty()) {
            return;
        }

        // Lấy số liệu cần thiết một lần, tránh N+1
        $weekStart = now()->startOfWeek();

        $stats = UserStatistic::where('user_id', $userId)->first();
        $currentStreak = $stats?->current_win_streak ?? 0;

        $weeklyWins = Bet::where('user_id', $userId)
            ->whereIn('status', ['WON', 'HALF_WON'])
            ->where('settled_at', '>=', $weekStart)
            ->count();

        $weeklyWins5 = $weeklyWins; // alias cho W7

        $hasExactScoreWin = Bet::where('user_id', $userId)
            ->where('market_type_snapshot', 'EXACT_SCORE')
            ->whereIn('status', ['WON'])
            ->where('settled_at', '>=', $weekStart)
            ->exists();

        foreach ($activeCodes as $code) {
            match ($code) {
                // Thắng ít nhất 3 vé trong tuần — absolute count từ DB
                'WEEKLY_W5' => EvaluateMissionsJob::dispatch($userId, 'WEEKLY_W5', $weeklyWins, true),
                // Chuỗi thắng liên tiếp 3 — absolute streak
                'WEEKLY_W6' => EvaluateMissionsJob::dispatch($userId, 'WEEKLY_W6', $currentStreak, true),
                // Thắng ít nhất 5 vé trong tuần
                'WEEKLY_W7' => EvaluateMissionsJob::dispatch($userId, 'WEEKLY_W7', $weeklyWins5, true),
                // Đoán đúng tỉ số (EXACT_SCORE WON) — event-based, cộng dồn 1
                'WEEKLY_W9' => $hasExactScoreWin
                    ? EvaluateMissionsJob::dispatch($userId, 'WEEKLY_W9', 1, false)
                    : null,
                // Chuỗi thắng liên tiếp 5
                'WEEKLY_W10' => EvaluateMissionsJob::dispatch($userId, 'WEEKLY_W10', $currentStreak, true),
                default => null,
            };
        }

    }
}
