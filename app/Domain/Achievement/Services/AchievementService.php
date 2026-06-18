<?php

namespace App\Domain\Achievement\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Models\Bet;
use App\Models\UserAchievement;

class AchievementService
{
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
                    // Thắng liên tiếp 3 — dùng settled_at để đúng thứ tự settle
                    $lv4Bets = Bet::where('user_id', $userId)
                        ->whereNotIn('status', ['PENDING'])
                        ->whereNotNull('settled_at')
                        ->orderBy('settled_at', 'desc')
                        ->limit(10)
                        ->get();
                    $lv4Streak = 0;
                    foreach ($lv4Bets as $bet) {
                        if (in_array($bet->status, ['WON', 'HALF_WON'])) {
                            $lv4Streak++;
                        } else {
                            break;
                        }
                    }
                    $shouldAward = $lv4Streak >= 3;
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
                    $shouldAward = Bet::where('user_id', $userId)
                        ->whereIn('status', ['WON', 'HALF_WON'])
                        ->count() >= 50;
                    break;
                case 'LV9_OMNISCIENT':
                    $shouldAward = Bet::where('user_id', $userId)
                        ->whereIn('status', ['WON', 'HALF_WON'])
                        ->count() >= $achievement->target_value;
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
                    $latestBets = Bet::where('user_id', $userId)
                        ->whereNotIn('status', ['PENDING'])
                        ->whereNotNull('settled_at')
                        ->orderBy('settled_at', 'desc')
                        ->limit(15)
                        ->get();
                    $winStreak = 0;
                    foreach ($latestBets as $bet) {
                        if (in_array($bet->status, ['WON', 'HALF_WON'])) {
                            $winStreak++;
                        } else {
                            break;
                        }
                    }
                    $shouldAward = $winStreak >= $achievement->target_value;
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
                    // Dùng giờ VN: 0h-5h sáng VN = 17h-22h UTC ngày hôm trước
                    $nightDaysCount = Bet::where('user_id', $userId)
                        ->selectRaw("DATE(placed_at AT TIME ZONE 'Asia/Ho_Chi_Minh') as date")
                        ->whereRaw("EXTRACT(HOUR FROM placed_at AT TIME ZONE 'Asia/Ho_Chi_Minh') >= 0")
                        ->whereRaw("EXTRACT(HOUR FROM placed_at AT TIME ZONE 'Asia/Ho_Chi_Minh') < 5")
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
                    $recentDays = Bet::where('user_id', $userId)
                        ->selectRaw("DATE(placed_at AT TIME ZONE 'Asia/Ho_Chi_Minh') as date")
                        ->groupBy('date')
                        ->orderBy('date', 'desc')
                        ->limit(7)
                        ->pluck('date')
                        ->toArray();
                    if (count($recentDays) === 7) {
                        $firstDate = \Carbon\Carbon::parse($recentDays[6])->startOfDay();
                        $lastDate  = \Carbon\Carbon::parse($recentDays[0])->startOfDay();
                        if (abs((int) $lastDate->diffInDays($firstDate)) === 6) {
                            $shouldAward = true;
                        }
                    }
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
