<?php

namespace App\Domain\Achievement\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Models\Bet;
use App\Models\UserAchievement;

class AchievementService
{
    /**
     * Trả về SQL expression DATE theo timezone VN (UTC+7),
     * tương thích cả PostgreSQL lẫn SQLite (test).
     */
    private function localDate(string $column = 'placed_at'): string
    {
        $driver = config('database.default');
        $conn   = config("database.connections.{$driver}.driver");

        return match ($conn) {
            'pgsql'  => "DATE({$column} AT TIME ZONE 'Asia/Ho_Chi_Minh')",
            'sqlite' => "DATE(datetime({$column}, '+7 hours'))",
            default  => "DATE({$column})",
        };
    }

    private function localHour(string $column = 'placed_at'): string
    {
        $driver = config('database.default');
        $conn   = config("database.connections.{$driver}.driver");

        return match ($conn) {
            'pgsql'  => "EXTRACT(HOUR FROM {$column} AT TIME ZONE 'Asia/Ho_Chi_Minh')",
            'sqlite' => "CAST(strftime('%H', datetime({$column}, '+7 hours')) AS INTEGER)",
            default  => "HOUR({$column})",
        };
    }

    /**
     * Kiểm tra và trao các thành tựu cho người dùng.
     */
    public function checkAndAward(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $achievements = Achievement::orderBy('level')->get();
        $userAchievements = UserAchievement::where('user_id', $userId)
            ->pluck('achievement_id')
            ->toArray();

        // Lấy cấp bậc cao nhất hiện tại của user
        $maxUnlockedLevel = Achievement::whereIn('id', $userAchievements)->max('level') ?? 0;

        foreach ($achievements as $achievement) {
            // Nếu không lặp lại và đã nhận rồi thì bỏ qua
            if (!$achievement->is_repeatable && in_array($achievement->id, $userAchievements)) {
                continue;
            }

            // Ràng buộc tính tuần tự cho các level chính (không nhảy cóc)
            if ($achievement->level !== null) {
                if ($achievement->level > $maxUnlockedLevel + 1) {
                    continue; // Bắt buộc phải đạt level trước đó mới được xét
                }
            }

            $shouldAward = false;

            switch ($achievement->code) {
                case 'LV1_APPRENTICE':
                    $shouldAward = Bet::where('user_id', $userId)->exists();
                    break;
                case 'LV2_LUCKY_HUNTER':
                    $shouldAward = Bet::where('user_id', $userId)
                        ->whereIn('status', ['WON', 'HALF_WON'])
                        ->count() >= $achievement->target_value;
                    break;
                case 'LV3_DECODER':
                    $shouldAward = Bet::where('user_id', $userId)
                        ->where('market_type_snapshot', 'EXACT_SCORE')
                        ->where('status', 'WON')
                        ->count() >= $achievement->target_value;
                    break;
                case 'LV4_EXPERT':
                    $longestWinStreak = \App\Models\UserStatistic::where('user_id', $userId)->value('longest_win_streak') ?? 0;
                    $shouldAward = $longestWinStreak >= 3;
                    break;
                case 'LV5_PROPHET':
                    $wins = Bet::where('user_id', $userId)
                        ->whereIn('status', ['WON', 'HALF_WON'])
                        ->count();
                    $hasAsian = Bet::where('user_id', $userId)->where('market_type_snapshot', 'ASIAN_HANDICAP')->exists();
                    $hasOU = Bet::where('user_id', $userId)->where('market_type_snapshot', 'OVER_UNDER')->exists();
                    $hasExact = Bet::where('user_id', $userId)->where('market_type_snapshot', 'EXACT_SCORE')->exists();
                    
                    $shouldAward = ($wins >= $achievement->target_value) && $hasAsian && $hasOU && $hasExact;
                    break;
                case 'LV6_FUTURE_ENVOY':
                    $wins = Bet::where('user_id', $userId)
                        ->whereIn('status', ['WON', 'HALF_WON'])
                        ->count();
                    $totalNet = Bet::where('user_id', $userId)->whereNotNull('net_result')->sum('net_result');
                    $shouldAward = ($wins >= $achievement->target_value) && ($totalNet > 0);
                    break;
                case 'LV7_LORD_OF_DESTINY':
                    $shouldAward = Bet::where('user_id', $userId)
                        ->where('market_type_snapshot', 'EXACT_SCORE')
                        ->where('status', 'WON')
                        ->count() >= $achievement->target_value;
                    break;
                case 'LV8_COSMIC':
                    // Streak >= 15 AND win-rate >= 75% AND settled >= 100
                    $longestStreakLv8 = \App\Models\UserStatistic::where('user_id', $userId)->value('longest_win_streak') ?? 0;
                    $settledLv8 = Bet::where('user_id', $userId)->whereNotIn('status', ['PENDING', 'VOIDED'])->count();
                    $winsLv8    = Bet::where('user_id', $userId)->whereIn('status', ['WON', 'HALF_WON'])->count();
                    $wrLv8      = $settledLv8 >= 100 ? ($winsLv8 / $settledLv8 * 100) : 0;
                    $shouldAward = $longestStreakLv8 >= $achievement->target_value && $wrLv8 >= 75 && $settledLv8 >= 100;
                    break;
                case 'LV9_OMNISCIENT':
                    // 100 wins AND ROI >= 20% AND exact score >= 10
                    $winsLv9   = Bet::where('user_id', $userId)->whereIn('status', ['WON', 'HALF_WON'])->count();
                    $stakedLv9 = Bet::where('user_id', $userId)->sum('stake');
                    $netLv9    = Bet::where('user_id', $userId)->whereNotNull('net_result')->sum('net_result');
                    $roiLv9    = $stakedLv9 > 0 ? ($netLv9 / $stakedLv9 * 100) : 0;
                    $exactLv9  = Bet::where('user_id', $userId)->where('market_type_snapshot', 'EXACT_SCORE')->where('status', 'WON')->count();
                    $shouldAward = $winsLv9 >= $achievement->target_value && $roiLv9 >= 20 && $exactLv9 >= 10;
                    break;
                case 'LV10_LEGEND':
                    // 200 wins + streak >= 12 + 10 exact + ROI >= 20% + 3 kèo
                    $winsLv10   = Bet::where('user_id', $userId)->whereIn('status', ['WON', 'HALF_WON'])->count();
                    $streakLv10 = \App\Models\UserStatistic::where('user_id', $userId)->value('longest_win_streak') ?? 0;
                    $exactLv10  = Bet::where('user_id', $userId)->where('market_type_snapshot', 'EXACT_SCORE')->where('status', 'WON')->count();
                    $stakedLv10 = Bet::where('user_id', $userId)->sum('stake');
                    $netLv10    = Bet::where('user_id', $userId)->whereNotNull('net_result')->sum('net_result');
                    $roiLv10    = $stakedLv10 > 0 ? ($netLv10 / $stakedLv10 * 100) : 0;
                    $hasAH10    = Bet::where('user_id', $userId)->where('market_type_snapshot', 'ASIAN_HANDICAP')->exists();
                    $hasOU10    = Bet::where('user_id', $userId)->where('market_type_snapshot', 'OVER_UNDER')->exists();
                    $hasES10    = Bet::where('user_id', $userId)->where('market_type_snapshot', 'EXACT_SCORE')->exists();
                    $shouldAward = $winsLv10 >= $achievement->target_value
                        && $streakLv10 >= 12
                        && $exactLv10 >= 10
                        && $roiLv10 >= 20
                        && $hasAH10 && $hasOU10 && $hasES10;
                    break;
                case 'DEDICATION_100_BETS':
                    $shouldAward = Bet::where('user_id', $userId)->count() >= 100;
                    break;
                case 'HIGH_ROLLER':
                    $shouldAward = Bet::where('user_id', $userId)->where('stake', '>=', 5000000)->exists();
                    break;

                // Win Streak Quests — dùng settled_at để đúng thứ tự settle
                case 'WIN_STREAK_3':
                case 'WIN_STREAK_5':
                case 'WIN_STREAK_10':
                    $longestWinStreak = \App\Models\UserStatistic::where('user_id', $userId)->value('longest_win_streak') ?? 0;
                    $shouldAward = $longestWinStreak >= $achievement->target_value;
                    break;

                // Easy Quests
                case 'EARLY_BIRD':
                    $latestBetsForEB = Bet::where('user_id', $userId)
                        ->join('matches', 'bets.match_id', '=', 'matches.id')
                        ->orderBy('bets.placed_at', 'desc')
                        ->limit(15)
                        ->select('bets.placed_at', 'matches.kickoff_at')
                        ->get();
                    $ebStreak = 0;
                    foreach ($latestBetsForEB as $b) {
                        if ($b->placed_at < $b->kickoff_at) {
                            $ebStreak++;
                        } else {
                            break;
                        }
                    }
                    $shouldAward = $ebStreak >= $achievement->target_value;
                    break;
                case 'NIGHT_OWL':
                    // Dùng giờ VN: 0h-5h sáng
                    $nightDaysCount = Bet::where('user_id', $userId)
                        ->selectRaw("{$this->localDate()} as date")
                        ->whereRaw("{$this->localHour()} >= 0")
                        ->whereRaw("{$this->localHour()} < 5")
                        ->groupBy('date')
                        ->get()
                        ->count();
                    $shouldAward = $nightDaysCount >= $achievement->target_value;
                    break;
                case 'MULTI_MARKET':
                    $distinctMarketsCount = Bet::where('user_id', $userId)
                        ->select('market_type_snapshot')
                        ->distinct()
                        ->count('market_type_snapshot');
                    $shouldAward = $distinctMarketsCount >= 3;
                    break;

                // Accuracy Quests
                case 'ACCURACY_SNIPER':
                    $totalBetsForSniper = Bet::where('user_id', $userId)->whereNotIn('status', ['PENDING'])->count();
                    if ($totalBetsForSniper >= 50) {
                        $winsForSniper = Bet::where('user_id', $userId)->whereIn('status', ['WON', 'HALF_WON'])->count();
                        $shouldAward = ($winsForSniper / $totalBetsForSniper) >= 0.8;
                    }
                    break;
                case 'ACCURACY_MATH':
                    $shouldAward = Bet::where('user_id', $userId)
                        ->where('market_type_snapshot', 'EXACT_SCORE')
                        ->where('status', 'WON')
                        ->count() >= 3;
                    break;
                case 'BIG_WINNER':
                    $shouldAward = Bet::where('user_id', $userId)
                        ->where('profit_rate_snapshot', '>=', 5.0)
                        ->whereIn('status', ['WON', 'HALF_WON'])
                        ->exists();
                    break;

                // Fun & Bad Luck
                case 'BAD_LUCK_5':
                    $latestBetsBL = Bet::where('user_id', $userId)
                        ->whereNotIn('status', ['PENDING'])
                        ->orderBy('placed_at', 'desc')
                        ->limit(10)
                        ->get();
                    $loseStreak = 0;
                    foreach ($latestBetsBL as $bet) {
                        if (in_array($bet->status, ['LOST', 'HALF_LOST'])) {
                            $loseStreak++;
                        } else {
                            break;
                        }
                    }
                    $shouldAward = $loseStreak >= 5;
                    break;
                case 'BAD_LUCK_NARROW':
                    $shouldAward = Bet::where('user_id', $userId)->where('status', 'HALF_LOST')->count() >= 3;
                    break;
                case 'BAD_LUCK_DRAW':
                    $shouldAward = Bet::where('user_id', $userId)->where('status', 'PUSH')->count() >= 5;
                    break;
                case 'DEDICATION_7_DAYS':
                    $localDate = $this->localDate();
                    $allBetDays = Bet::where('user_id', $userId)
                        ->selectRaw("{$localDate} as date")
                        ->groupBy('date')
                        ->orderBy('date', 'desc')
                        ->pluck('date')
                        ->toArray();
                    $dedicationStreak = 0;
                    if (count($allBetDays) > 0) {
                        $dedicationStreak = 1;
                        for ($i = 0; $i < count($allBetDays) - 1; $i++) {
                            // Dùng native DateTime để tránh Carbon 3 diffInDays behavior thay đổi
                            $d1 = new \DateTime(substr((string) $allBetDays[$i], 0, 10));
                            $d2 = new \DateTime(substr((string) $allBetDays[$i + 1], 0, 10));
                            $diff = (int) $d1->diff($d2)->days;
                            if ($diff === 1) {
                                $dedicationStreak++;
                            } else {
                                break;
                            }
                        }
                    }
                    $shouldAward = $dedicationStreak >= 7;
                    break;
            }

            if ($shouldAward) {
                $this->awardAchievement($userId, $achievement);
                
                // Cập nhật lại max level ngay lập tức để xét tiếp các level sau trong cùng một lần chạy
                if ($achievement->level !== null && $achievement->level > $maxUnlockedLevel) {
                    $maxUnlockedLevel = $achievement->level;
                }
            }
        }
    }

    /**
     * Award achievement trực tiếp (bypass condition check).
     * Dùng khi achievement được trigger bởi mission completion.
     */
    public function awardDirectly(int $userId, Achievement $achievement): void
    {
        $this->awardAchievement($userId, $achievement);
    }

    protected function awardAchievement(int $userId, Achievement $achievement): void

    {
        // Kiểm tra logic cooldown đối với repeatable achievement
        if ($achievement->is_repeatable && $achievement->cooldown_period) {
            $lastAwarded = UserAchievement::where('user_id', $userId)
                ->where('achievement_id', $achievement->id)
                ->latest('awarded_at')
                ->first();

            if ($lastAwarded) {
                if ($achievement->cooldown_period === 'daily' && $lastAwarded->awarded_at->isToday()) {
                    return;
                }
                if ($achievement->cooldown_period === 'weekly' && $lastAwarded->awarded_at->isCurrentWeek()) {
                    return;
                }
            }
        }

        // Trao thành tựu sử dụng transaction và firstOrCreate để tránh Race Condition
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $achievement) {
                $created = UserAchievement::firstOrCreate([
                    'user_id' => $userId,
                    'achievement_id' => $achievement->id,
                ], [
                    'awarded_at' => now(),
                ]);

                // Nếu vừa mới được tạo ra (mới được thưởng)
                if ($created->wasRecentlyCreated) {
                    $user = User::find($userId);
                    if ($user) {
                        app(\App\Domain\Notification\Services\NotificationService::class)
                            ->notifyAchievementUnlocked($user, $achievement);
                    }
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Ignore unique constraint violations due to race conditions
            if ($e->getCode() !== '23000' && $e->getCode() !== '23505') {
                throw $e;
            }
        }
    }
}
