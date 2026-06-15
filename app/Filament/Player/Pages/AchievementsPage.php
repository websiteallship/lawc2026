<?php

namespace App\Filament\Player\Pages;

use App\Models\Achievement;
use App\Models\UserAchievement;
use Filament\Pages\Page;

class AchievementsPage extends Page
{
    protected string $view = 'filament.player.pages.achievements-page';
    protected static ?string $slug = 'achievements';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-trophy';
    }

    public static function getNavigationLabel(): string
    {
        return 'Con Đường Danh Vọng';
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Hành Trình Chinh Phục';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Nhiệm vụ & Danh hiệu';
    }

    public static function getNavigationSort(): ?int
    {
        return 60;
    }


    public function getViewData(): array
    {
        $user = auth()->user();
        // Lấy danh sách thông báo thành tựu chưa đọc
        $unreadCount = $user->unreadNotifications()
            ->where('data', 'like', '%heroicon-o-trophy%')
            ->count();
            
        $newAchievementsToShow = [];
        if ($unreadCount > 0) {
            $newAchievementsToShow = \App\Models\Achievement::select('achievements.*')
                ->join('user_achievements', 'achievements.id', '=', 'user_achievements.achievement_id')
                ->where('user_achievements.user_id', $user->id)
                ->orderBy('user_achievements.created_at', 'desc')
                ->limit($unreadCount)
                ->get();
        }

        // Đánh dấu các thông báo thành tựu là đã đọc
        if ($unreadCount > 0) {
            $user->unreadNotifications()
                ->where('data', 'like', '%heroicon-o-trophy%')
                ->update(['read_at' => now()]);
        }

        $achievements = Achievement::orderBy('level')->get();
        $userAchievements = UserAchievement::where('user_id', $user->id)->pluck('achievement_id')->toArray();

        // Calculate progress stats
        $totalBets = \App\Models\Bet::where('user_id', $user->id)->count();
        $totalWins = \App\Models\Bet::where('user_id', $user->id)->whereIn('status', ['WON', 'HALF_WON'])->count();
        $exactScoreWins = \App\Models\Bet::where('user_id', $user->id)->where('market_type_snapshot', 'EXACT_SCORE')->where('status', 'WON')->count();
        
        // Calculate Win Streak
        $latestBets = \App\Models\Bet::where('user_id', $user->id)
            ->whereNotIn('status', ['PENDING'])
            ->orderBy('placed_at', 'desc')
            ->limit(20)
            ->get();
        $winStreak = 0;
        foreach ($latestBets as $bet) {
            if (in_array($bet->status, ['WON', 'HALF_WON'])) {
                $winStreak++;
            } else {
                break;
            }
        }

        $latestBetsForEarlyBird = \App\Models\Bet::where('user_id', $user->id)
            ->join('matches', 'bets.match_id', '=', 'matches.id')
            ->orderBy('bets.placed_at', 'desc')
            ->limit(10)
            ->select('bets.placed_at', 'matches.kickoff_at')
            ->get();
        $earlyBirdStreak = 0;
        foreach ($latestBetsForEarlyBird as $b) {
            if ($b->placed_at < $b->kickoff_at) {
                $earlyBirdStreak++;
            } else {
                break;
            }
        }

        $recentDays = \App\Models\Bet::where('user_id', $user->id)
            ->selectRaw('DATE(placed_at) as date')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->pluck('date')
            ->toArray();
        $dedicationStreak = 0;
        if (count($recentDays) > 0) {
            $dedicationStreak = 1;
            for ($i = 0; $i < count($recentDays) - 1; $i++) {
                $d1 = \Carbon\Carbon::parse($recentDays[$i])->startOfDay();
                $d2 = \Carbon\Carbon::parse($recentDays[$i+1])->startOfDay();
                if ($d1->diffInDays($d2) === 1) {
                    $dedicationStreak++;
                } else {
                    break;
                }
            }
        }

        // Calculate Muted and other states
        $hasAsian = \App\Models\Bet::where('user_id', $user->id)->where('market_type_snapshot', 'ASIAN_HANDICAP')->exists();
        $hasOU = \App\Models\Bet::where('user_id', $user->id)->where('market_type_snapshot', 'OVER_UNDER')->exists();
        $hasExact = \App\Models\Bet::where('user_id', $user->id)->where('market_type_snapshot', 'EXACT_SCORE')->exists();
        $typesCount = ($hasAsian ? 1 : 0) + ($hasOU ? 1 : 0) + ($hasExact ? 1 : 0);

        $totalNet = \App\Models\Bet::where('user_id', $user->id)->whereNotNull('net_result')->sum('net_result');

        $progressMap = [
            'LV1_APPRENTICE' => ['current' => $totalBets, 'target' => 1],
            'LV2_LUCKY_HUNTER' => ['current' => $totalWins, 'target' => 5],
            'LV3_DECODER' => ['current' => $exactScoreWins, 'target' => 1],
            'LV4_EXPERT' => ['current' => $winStreak, 'target' => 3],
            'LV5_PROPHET' => ['current' => $totalWins, 'target' => 20], // UI can also show "3/3 loại kèo"
            'LV6_FUTURE_ENVOY' => ['current' => $totalWins, 'target' => 50],
            'LV7_LORD_OF_DESTINY' => ['current' => $exactScoreWins, 'target' => 5],
            'LV8_COSMIC' => ['current' => $winStreak, 'target' => 7],
            'LV9_OMNISCIENT' => ['current' => $totalWins, 'target' => 100],
            
            // Side quests
            'WIN_STREAK_3' => ['current' => $winStreak, 'target' => 3],
            'WIN_STREAK_5' => ['current' => $winStreak, 'target' => 5],
            'WIN_STREAK_10' => ['current' => $winStreak, 'target' => 10],
            'EARLY_BIRD' => ['current' => $earlyBirdStreak, 'target' => 10],
            'NIGHT_OWL' => ['current' => \App\Models\Bet::where('user_id', $user->id)->whereTime('placed_at', '>=', '00:00:00')->whereTime('placed_at', '<=', '05:00:00')->selectRaw('DATE(placed_at) as date')->groupBy('date')->get()->count(), 'target' => 7],
            'MULTI_MARKET' => ['current' => \App\Models\Bet::where('user_id', $user->id)->select('market_type_snapshot')->distinct()->count('market_type_snapshot'), 'target' => 3],
            'ACCURACY_SNIPER' => ['current' => $totalBets >= 50 ? round(($totalWins / $totalBets) * 100) : 0, 'target' => 80],
            'ACCURACY_MATH' => ['current' => $exactScoreWins, 'target' => 3],
            'BIG_WINNER' => ['current' => \App\Models\Bet::where('user_id', $user->id)->where('profit_rate_snapshot', '>=', 5.0)->whereIn('status', ['WON', 'HALF_WON'])->count(), 'target' => 1],
            'BAD_LUCK_5' => ['current' => 0, 'target' => 5], // Needs complex streak logic
            'BAD_LUCK_NARROW' => ['current' => \App\Models\Bet::where('user_id', $user->id)->where('status', 'HALF_LOST')->count(), 'target' => 3],
            'BAD_LUCK_DRAW' => ['current' => \App\Models\Bet::where('user_id', $user->id)->where('status', 'PUSH')->count(), 'target' => 5],
            'DEDICATION_7_DAYS' => ['current' => $dedicationStreak, 'target' => 7],
            'DEDICATION_100_BETS' => ['current' => $totalBets, 'target' => 100],
            'HIGH_ROLLER' => ['current' => \App\Models\Bet::where('user_id', $user->id)->max('stake') ?: 0, 'target' => 5000000],
        ];

        // Find current level status
        $unlockedMainAchievements = $achievements->whereNotNull('level')->filter(function ($achievement) use ($userAchievements) {
            return in_array($achievement->id, $userAchievements);
        });

        $currentLevelNo = $unlockedMainAchievements->max('level') ?: 0;
        $currentLevelAchievement = $achievements->where('level', $currentLevelNo)->first();
        $currentLevelName = $currentLevelAchievement ? $currentLevelAchievement->name : 'Chưa có cấp bậc';
        
        $nextLevelNo = $currentLevelNo + 1;
        $nextLevelAchievement = $achievements->where('level', $nextLevelNo)->first();
        
        $nextLevelName = '';
        $nextLevelReq = '';
        $nextLevelPercent = 0;
        $nextLevelCurrent = 0;
        $nextLevelTarget = 1;

        if ($nextLevelAchievement) {
            $nextLevelName = $nextLevelAchievement->name;
            $nextLevelReq = $nextLevelAchievement->description;
            $nextProg = $progressMap[$nextLevelAchievement->code] ?? ['current' => 0, 'target' => 1];
            $nextLevelCurrent = $nextProg['current'];
            $nextLevelTarget = $nextProg['target'];
            $nextLevelPercent = min(100, $nextLevelTarget > 0 ? round(($nextLevelCurrent / $nextLevelTarget) * 100) : 0);
        }

        $missions = \App\Models\Mission::where('is_active', true)->get();
        $userMissions = \App\Models\UserMission::where('user_id', $user->id)->get()->keyBy('mission_id');

        return [
            'achievements' => $achievements,
            'userAchievements' => $userAchievements,
            'progressMap' => $progressMap,
            'extraStats' => [
                'typesCount' => $typesCount,
                'totalNet' => $totalNet,
            ],
            'levelStatus' => [
                'currentNo' => $currentLevelNo,
                'currentName' => $currentLevelName,
                'nextNo' => $nextLevelNo,
                'nextName' => $nextLevelName,
                'nextReq' => $nextLevelReq,
                'nextPercent' => $nextLevelPercent,
                'nextCurrent' => $nextLevelCurrent,
                'nextTarget' => $nextLevelTarget,
            ],
            'newAchievementsToShow' => $newAchievementsToShow,
            'missions' => $missions,
            'userMissions' => $userMissions,
        ];
    }
}
