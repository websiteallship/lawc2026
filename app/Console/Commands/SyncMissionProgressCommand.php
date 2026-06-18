<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Mission;
use App\Models\User;
use App\Models\UserMission;
use App\Models\UserStatistic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMissionProgressCommand extends Command
{
    protected $signature = 'app:sync-missions
                            {--user= : Chỉ sync 1 user_id cụ thể}
                            {--dry-run : Chạy thử, không ghi DB}';

    protected $description = 'Tính lại toàn bộ mission progress từ lịch sử vé đã settle (retroactive sync)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $targetUserId = $this->option('user');

        $weekStart = now()->startOfWeek();
        $weekEnd   = now()->endOfWeek();

        // Lấy danh sách mission đang active cần sync
        $missions = Mission::where('is_active', true)
            ->whereIn('code', ['WEEKLY_W5', 'WEEKLY_W6', 'WEEKLY_W7', 'WEEKLY_W9', 'WEEKLY_W10'])
            ->get()
            ->keyBy('code');

        if ($missions->isEmpty()) {
            $this->warn('Không có mission nào đang active để sync.');
            return 0;
        }

        $this->info('Mission đang active: ' . $missions->keys()->implode(', '));
        $this->newLine();

        // Query users
        // Query users có vé settle trong tuần
        $usersQuery = User::whereIn('id', function ($q) use ($weekStart, $weekEnd) {
            $q->select('user_id')
              ->from('bets')
              ->whereIn('status', ['WON', 'HALF_WON', 'LOST', 'HALF_LOST', 'PUSH'])
              ->whereBetween('settled_at', [$weekStart, $weekEnd]);
        });

        if ($targetUserId) {
            $usersQuery->where('id', $targetUserId);
        }

        $users = $usersQuery->pluck('id');
        $this->info("Tổng user cần sync: {$users->count()}");
        $this->newLine();

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $updated = 0;

        foreach ($users as $userId) {
            // Dữ liệu từ DB, không dùng cache
            $stats = UserStatistic::where('user_id', $userId)->first();
            $currentStreak = $stats?->current_win_streak ?? 0;

            $weeklyWins = Bet::where('user_id', $userId)
                ->whereIn('status', ['WON', 'HALF_WON'])
                ->whereBetween('settled_at', [$weekStart, $weekEnd])
                ->count();

            $hasExactScoreWin = Bet::where('user_id', $userId)
                ->where('market_type_snapshot', 'EXACT_SCORE')
                ->whereIn('status', ['WON'])
                ->whereBetween('settled_at', [$weekStart, $weekEnd])
                ->exists();

            $updates = [
                'WEEKLY_W5'  => $weeklyWins,       // thắng >= 3
                'WEEKLY_W6'  => $currentStreak,     // chuỗi >= 3
                'WEEKLY_W7'  => $weeklyWins,        // thắng >= 5
                'WEEKLY_W10' => $currentStreak,     // chuỗi >= 5
            ];

            if ($hasExactScoreWin) {
                $updates['WEEKLY_W9'] = 1;
            }

            foreach ($updates as $code => $value) {
                if (!$missions->has($code)) continue;
                $mission = $missions[$code];

                $userMission = UserMission::where('user_id', $userId)
                    ->where('mission_id', $mission->id)
                    ->first();

                $currentValue = $userMission?->current_value ?? 0;
                $isAlreadyCompleted = $userMission?->is_completed ?? false;

                if ($isAlreadyCompleted) continue;
                if ($value <= $currentValue) continue; // chỉ forward, không lùi

                $newValue   = min($value, $mission->target_value);
                $isCompleted = $newValue >= $mission->target_value;

                $this->line(
                    "  User #{$userId} | {$code}: {$currentValue} -> {$newValue}" .
                    ($isCompleted ? ' [HOÀN THÀNH]' : '') .
                    ($dryRun ? ' [DRY RUN]' : '')
                );

                if (!$dryRun) {
                    UserMission::updateOrCreate(
                        ['user_id' => $userId, 'mission_id' => $mission->id],
                        [
                            'current_value' => $newValue,
                            'is_completed'  => $isCompleted,
                            'completed_at'  => $isCompleted ? now() : null,
                        ]
                    );
                }

                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Sync hoàn tất. Tổng bản ghi cập nhật: {$updated}" . ($dryRun ? ' (DRY RUN - không ghi DB)' : ''));

        return 0;
    }
}
