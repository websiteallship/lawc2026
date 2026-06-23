<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Mission;
use App\Models\User;
use App\Models\UserMission;

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

        // ← BẮT BUỘC dùng timezone VN để weekStart = thứ 2 00:00 giờ VN
        $weekStart = now('Asia/Ho_Chi_Minh')->startOfWeek()->utc();
        $weekEnd   = now('Asia/Ho_Chi_Minh')->endOfWeek()->utc();

        $this->info("Tuần: {$weekStart->format('Y-m-d H:i')} UTC → {$weekEnd->format('Y-m-d H:i')} UTC");


        // Lấy danh sách mission đang active cần sync
        $missions = Mission::where('is_active', true)
            ->get()
            ->keyBy('code');

        if ($missions->isEmpty()) {
            $this->warn('Không có mission nào đang active để sync.');
            return 0;
        }

        $this->info('Mission đang active: ' . $missions->keys()->implode(', '));
        $this->newLine();

        // Query users
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
        $this->info("Tổng user cần sync: {$users->count()}");
        $this->newLine();

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
