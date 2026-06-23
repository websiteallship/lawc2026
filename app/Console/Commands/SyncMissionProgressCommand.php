<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Mission;
use App\Models\User;
use App\Models\UserMission;
use App\Models\UserMissionCompletion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMissionProgressCommand extends Command
{
    protected $signature = 'app:sync-missions
                            {--user= : Chỉ sync 1 user_id cụ thể}
                            {--weeks=1 : Số tuần cần sync (1=tuần này, 4=4 tuần gần nhất)}
                            {--dry-run : Chạy thử, không ghi DB}';

    protected $description = 'Tính lại toàn bộ mission progress từ lịch sử bets (retroactive sync)';

    public function handle(): int
    {
        $dryRun       = $this->option('dry-run');
        $targetUserId = $this->option('user');
        $weeksBack    = max(1, (int) $this->option('weeks'));

        $totalUpdated = 0;

        for ($w = $weeksBack - 1; $w >= 0; $w--) {
            $weekStart = now('Asia/Ho_Chi_Minh')->subWeeks($w)->startOfWeek()->utc();
            $weekEnd   = now('Asia/Ho_Chi_Minh')->subWeeks($w)->endOfWeek()->utc();
            $weekKey   = \App\Models\UserMissionCompletion::weekKey(
                now('Asia/Ho_Chi_Minh')->subWeeks($w)->startOfWeek()
            );

            $this->info("=== Sync tuần {$weekKey}: {$weekStart->format('Y-m-d')} → {$weekEnd->format('Y-m-d')} ===");
            $totalUpdated += $this->syncWeek($weekStart, $weekEnd, $weekKey, $targetUserId, $dryRun);
            $this->newLine();
        }

        $this->info("Tổng tất cả tuần: {$totalUpdated} bản ghi" . ($dryRun ? ' (DRY RUN)' : ''));
        return 0;
    }

    private function syncWeek($weekStart, $weekEnd, string $weekKey, ?string $targetUserId, bool $dryRun): int
    {
        // Lấy missions đang active (dùng tất cả missions để retroactive — không chỉ is_active)
        $missions = Mission::get()->keyBy('code');

        if ($missions->isEmpty()) {
            $this->warn('Không có mission nào.');
            return 0;
        }

        $usersQuery = User::whereIn('id', function ($q) use ($weekStart, $weekEnd) {
            $q->select('user_id')
              ->from('bets')
              ->whereBetween('created_at', [$weekStart, $weekEnd])
              ->orWhereBetween('settled_at', [$weekStart, $weekEnd]);
        });

        if ($targetUserId) {
            $usersQuery->where('id', $targetUserId);
        }

        $users = $usersQuery->pluck('id');
        $this->line("  Tổng user cần sync: {$users->count()}");

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        $updated = 0;

        foreach ($users as $userId) {
            // Dữ liệu từ DB, không dùng cache
            // Tính streak trong tuần từ bets đã settle (theo thứ tự settled_at)
            $settledThisWeek = Bet::where('user_id', $userId)
                ->whereIn('status', ['WON', 'HALF_WON', 'LOST', 'HALF_LOST', 'PUSH'])
                ->whereBetween('settled_at', [$weekStart, $weekEnd])
                ->orderBy('settled_at')
                ->pluck('status')
                ->map(fn($s) => is_object($s) ? $s->value : $s);

            $currentStreak = 0;
            $maxStreakThisWeek = 0;
            foreach ($settledThisWeek as $status) {
                if (in_array($status, ['WON', 'HALF_WON'])) {
                    $currentStreak++;
                    $maxStreakThisWeek = max($maxStreakThisWeek, $currentStreak);
                } elseif (in_array($status, ['LOST', 'HALF_LOST'])) {
                    $currentStreak = 0;
                }
                // PUSH không reset streak
            }
            // Dùng chuỗi thắng dài nhất trong tuần để check mission
            $weeklyStreak = $maxStreakThisWeek;


            // Tất cả vé đã tạo trong tuần
            $weeklyBets = collect(DB::select("SELECT id, created_at, market_type_snapshot FROM bets WHERE user_id = ? AND created_at BETWEEN ? AND ?", [$userId, $weekStart, $weekEnd]));
            
            // Số ngày đặt cược khác nhau
            $distinctDays = $weeklyBets->map(fn($b) => date('Y-m-d', strtotime($b->created_at)))->unique()->count();
            
            // Số loại kèo khác nhau
            $distinctMarkets = $weeklyBets->pluck('market_type_snapshot')->unique()->count();

            // Số trận khác nhau (dựa vào group by market_id)
            $distinctMatchBets = DB::select("SELECT COUNT(DISTINCT m.match_id) as cnt FROM bets b JOIN markets m ON b.market_id = m.id WHERE b.user_id = ? AND b.created_at BETWEEN ? AND ?", [$userId, $weekStart, $weekEnd]);
            $distinctMatches = $distinctMatchBets[0]->cnt ?? 0;

            // Đặt cược sau 23:00 (Cú đêm)
            $nightBets = $weeklyBets->filter(function($b) {
                $hour = (int)date('H', strtotime($b->created_at));
                return $hour >= 23 || $hour <= 5;
            })->count();

            // Lấy vé thắng
            $weeklyWins = Bet::where('user_id', $userId)
                ->whereIn('status', ['WON', 'HALF_WON'])
                ->whereBetween('settled_at', [$weekStart, $weekEnd])
                ->count();

            $hasExactScoreWin = Bet::where('user_id', $userId)
                ->where('market_type_snapshot', 'EXACT_SCORE')
                ->whereIn('status', ['WON'])
                ->whereBetween('settled_at', [$weekStart, $weekEnd])
                ->exists();

            $maxStakeBet = DB::select("SELECT MAX(stake) as max_stake FROM bets WHERE user_id = ? AND created_at BETWEEN ? AND ?", [$userId, $weekStart, $weekEnd]);
            $maxStake = $maxStakeBet[0]->max_stake ?? 0;

            $updates = [
                'WEEKLY_ACTIVE_PLAYER'   => $weeklyBets->count(),
                'WEEKLY_MARKET_EXPLORER' => $distinctMarkets,
                'SMART_STAKE'            => $weeklyBets->count(),
                'WEEKLY_W1'              => $distinctMatches,
                'WEEKLY_W2'              => $nightBets,
                'WEEKLY_W3'              => $distinctMarkets,
                'WEEKLY_W4'              => $distinctDays,
                'WEEKLY_W5'              => $weeklyWins,
                'WEEKLY_W6'              => $weeklyStreak,
                'WEEKLY_W7'              => $weeklyWins,
                'WEEKLY_W8'              => $maxStake,
                'WEEKLY_W9'              => $hasExactScoreWin ? 1 : 0,
                'WEEKLY_W10'             => $weeklyStreak,
            ];

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

                    // Ghi lịch sử lũy kế khi hoàn thành (dùng $weekKey của tuần đang sync)
                    if ($isCompleted) {
                        UserMissionCompletion::firstOrCreate(
                            ['user_id' => $userId, 'mission_id' => $mission->id, 'week_key' => $weekKey],
                            ['mission_code' => $mission->code, 'mission_type' => $mission->type, 'completed_at' => now()]
                        );
                    }
                }

                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->line("  Sync xong. Cập nhật: {$updated} bản ghi" . ($dryRun ? ' (DRY RUN)' : ''));

        return $updated;
    }
}
