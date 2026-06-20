<?php

namespace App\Filament\Player\Pages;

use App\Models\Bet;
use App\Models\Season;
use Filament\Pages\Page;

class LeaderboardPage extends Page
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-trophy';
    }

    protected static ?string $navigationLabel = 'Bảng xếp hạng';
    protected static ?string $title = 'Bảng xếp hạng';
    protected static ?int $navigationSort = 70;
    protected string $view = 'filament.player.pages.leaderboard';

    public array $rankings = [];
    public string $activeTab = 'season';

    public function updatedActiveTab(): void { $this->loadRankings(); }
    public function mount(): void { $this->loadRankings(); }

    /** Các tab betting dùng net_profit/roi */
    private function isBettingTab(): bool
    {
        return in_array($this->activeTab, ['season', 'week', 'round', 'roi', 'exact_score']);
    }

    public function loadRankings(): void
    {
        $activeSeason = Season::where('status', 'active')->first();
        if (! $activeSeason) { $this->rankings = []; return; }

        $users = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'player'))
            ->where('status', 'ACTIVE')
            ->with(['wallets' => fn($q) => $q->where('season_id', $activeSeason->id)])
            ->get();

        $userIds = $users->pluck('id')->toArray();

        // ── Season-wide stats ────────────────────────────────────────────────
        $userStats = \App\Models\UserStatistic::whereIn('user_id', $userIds)
            ->get()->keyBy('user_id');

        // ── Achievement stats (level + badge count) ─────────────────────────
        // Tính trong một query: count danh hiệu và level cao nhất mỗi user
        $achievementRows = \App\Models\UserAchievement::join('achievements', 'user_achievements.achievement_id', '=', 'achievements.id')
            ->whereIn('user_achievements.user_id', $userIds)
            ->selectRaw('user_achievements.user_id, COUNT(*) as badge_count, MAX(achievements.level) as max_level')
            ->groupBy('user_achievements.user_id')
            ->get()->keyBy('user_id');

        // ── Mission stats ─────────────────────────────────────────────────────
        // Lấy các nhiệm vụ theo type để tính count
        $missionRows = \App\Models\UserMission::join('missions', 'user_missions.mission_id', '=', 'missions.id')
            ->whereIn('user_missions.user_id', $userIds)
            ->where('user_missions.is_completed', true)
            ->selectRaw("
                user_missions.user_id,
                COUNT(*) as total_missions,
                SUM(CASE WHEN missions.type = 'weekly' THEN 1 ELSE 0 END) as weekly_missions
            ")
            ->groupBy('user_missions.user_id')
            ->get()->keyBy('user_id');

        // ── Runtime stats cho tab Tuần và Vòng đấu ──────────────────────────
        $runtimeStats = [];
        if (in_array($this->activeTab, ['week', 'round'])) {
            $since = $this->activeTab === 'week'
                ? now()->timezone('Asia/Ho_Chi_Minh')->startOfWeek()->utc()
                : now()->subDays(7);

            $bets = Bet::whereIn('user_id', $userIds)
                ->whereNotIn('status', ['PENDING', 'VOIDED'])
                ->whereNotNull('settled_at')
                ->where('settled_at', '>=', $since)
                ->where('season_id', $activeSeason->id)
                ->get()->groupBy('user_id');

            foreach ($userIds as $uid) {
                $ub = $bets->get($uid, collect());
                $runtimeStats[$uid] = [
                    'netProfit'   => $ub->sum('net_result'),
                    'totalStaked' => $ub->sum('stake'),
                    'wonBets'     => $ub->filter(fn($b) => in_array(
                        $b->status instanceof \UnitEnum ? $b->status->value : $b->status,
                        ['WON', 'HALF_WON']
                    ))->count(),
                    'settledBets' => $ub->count(),
                ];
            }
        }

        // ── Build entries ────────────────────────────────────────────────────
        $entries = $users->map(function ($user) use ($userStats, $runtimeStats, $achievementRows, $missionRows) {
            $wallet = $user->wallets->first();
            $stats  = $userStats->get($user->id);
            $achRow = $achievementRows->get($user->id);
            $msRow  = $missionRows->get($user->id);

            // Stats betting
            if (in_array($this->activeTab, ['week', 'round'])) {
                $rt          = $runtimeStats[$user->id] ?? [];
                $netProfit   = $rt['netProfit'] ?? 0;
                $totalStaked = $rt['totalStaked'] ?? 0;
                $wonBets     = $rt['wonBets'] ?? 0;
                $settledBets = $rt['settledBets'] ?? 0;
                $exactScoreWins = 0;
            } else {
                $netProfit      = $stats ? (int) $stats->net_profit : 0;
                $totalStaked    = $stats ? (int) $stats->total_staked : 0;
                $wonBets        = $stats ? (int) $stats->won_bets : 0;
                $settledBets    = $stats ? (int) $stats->settled_bets : 0;
                $exactScoreWins = $stats ? (int) $stats->exact_score_wins : 0;
            }

            $winRate = $settledBets > 0 ? round($wonBets / $settledBets * 100, 1) : 0;
            $roi     = $totalStaked > 0 ? round($netProfit / $totalStaked * 100, 1) : null;

            // Stats achievement/mission
            $badgeCount    = $achRow ? (int) $achRow->badge_count : 0;
            $maxLevel      = $achRow ? (int) $achRow->max_level : 0;
            $totalMissions = $msRow  ? (int) $msRow->total_missions : 0;
            $weeklyMissions= $msRow  ? (int) $msRow->weekly_missions : 0;

            return (object) [
                'user_id'          => $user->id,
                'user'             => $user,
                'available'        => $wallet ? $wallet->available_balance : 0,
                'net_profit'       => $netProfit,
                'total_staked'     => $totalStaked,
                'roi'              => $roi,
                'win_rate'         => $winRate,
                'bets_count'       => $settledBets,
                'exact_score_wins' => $exactScoreWins,
                'settled_bets'     => $settledBets,
                'badge_count'      => $badgeCount,
                'max_level'        => $maxLevel,
                'total_missions'   => $totalMissions,
                'weekly_missions'  => $weeklyMissions,
            ];
        });

        // ── Sort & filter theo tab ───────────────────────────────────────────
        $entries = match($this->activeTab) {
            'roi'             => $entries
                ->filter(fn($e) => $e->settled_bets >= 5)
                ->sortByDesc(fn($e) => $e->roi ?? -999),
            'exact_score'     => $entries->sortBy([
                                    ['exact_score_wins', 'desc'],
                                    ['net_profit', 'desc'],
                                ]),
            'missions'        => $entries->sortBy([
                                    ['total_missions', 'desc'],
                                    ['weekly_missions', 'desc'],
                                ]),
            'level'           => $entries->sortBy([
                                    ['max_level', 'desc'],
                                    ['badge_count', 'desc'],
                                ]),
            'badges'          => $entries->sortBy([
                                    ['badge_count', 'desc'],
                                    ['max_level', 'desc'],
                                ]),
            default           => $entries->sortBy([
                                    ['net_profit', 'desc'],
                                    ['available', 'desc'],
                                ]),
        };

        $entries = $entries->take(50)->values();

        // ── Avatar & achievements ─────────────────────────────────────────────
        $animals = [
            ['name' => 'Hươu cao cổ', 'icon' => '🦒'], ['name' => 'Voi', 'icon' => '🐘'],
            ['name' => 'Ngựa vằn', 'icon' => '🦓'], ['name' => 'Bò sữa', 'icon' => '🐄'],
            ['name' => 'Cừu', 'icon' => '🐑'], ['name' => 'Dê', 'icon' => '🐐'],
            ['name' => 'Nai', 'icon' => '🦌'], ['name' => 'Thỏ', 'icon' => '🐇'],
            ['name' => 'Gấu trúc', 'icon' => '🐼'], ['name' => 'Koala', 'icon' => '🐨'],
            ['name' => 'Kangaroo', 'icon' => '🦘'], ['name' => 'Lười', 'icon' => '🦥'],
            ['name' => 'Hà mã', 'icon' => '🦛'], ['name' => 'Gorilla', 'icon' => '🦍'],
            ['name' => 'Lạc đà', 'icon' => '🐪'], ['name' => 'Ngựa', 'icon' => '🐎'],
            ['name' => 'Tê giác', 'icon' => '🦏'], ['name' => 'Trâu', 'icon' => '🐃'],
            ['name' => 'Sóc', 'icon' => '🐿️'], ['name' => 'Hải ly', 'icon' => '🦫'],
            ['name' => 'Chuột lang', 'icon' => '🐹'], ['name' => 'Rùa', 'icon' => '🐢'],
            ['name' => 'Đười ươi', 'icon' => '🦧'], ['name' => 'Cự đà', 'icon' => '🦎'],
            ['name' => 'Ốc sên', 'icon' => '🐌'], ['name' => 'Cào cào', 'icon' => '🦗'],
            ['name' => 'Bướm', 'icon' => '🦋'], ['name' => 'Sâu', 'icon' => '🐛'],
        ];
        $adjectives = [
            'Vui vẻ', 'Nhanh nhẹn', 'Lười biếng', 'Ham ăn', 'Ngái ngủ', 'Láu lỉnh', 'Nhút nhát', 'Dũng cảm',
            'Thông minh', 'Ngốc nghếch', 'Hào phóng', 'Thân thiện', 'Hay cáu', 'Bướng bỉnh', 'Chậm chạp',
            'Mạnh mẽ', 'Xinh xắn', 'Đáng yêu', 'Mũm mĩm', 'Trầm ngâm', 'Hay quên', 'Thích đùa',
        ];

        // Tính tổng số nhiệm vụ tuần đang active để chia %
        $totalWeeklyActive = \App\Models\Mission::where('type', 'weekly')->where('is_active', true)->count();

        $this->rankings = $entries->map(function ($entry, $index) use ($animals, $adjectives, $totalWeeklyActive) {
            $hash      = md5($entry->user_id . config('app.key'));
            $animal    = $animals[hexdec(substr($hash, 0, 4)) % count($animals)];
            $adjective = $adjectives[hexdec(substr($hash, 4, 4)) % count($adjectives)];

            $userAchievements = \App\Models\UserAchievement::where('user_id', $entry->user_id)
                ->join('achievements', 'user_achievements.achievement_id', '=', 'achievements.id')
                ->select('achievements.*')
                ->get();

            $weeklyRate = $totalWeeklyActive > 0 ? round(($entry->weekly_missions / $totalWeeklyActive) * 100) : 0;
            
            // perfect_weeks placeholder: nếu chưa có trong DB nhưng tuần này đã 100% thì tính là 1
            $dbPerfectWeeks = $entry->perfect_weeks ?? 0;
            $perfectWeeks = $dbPerfectWeeks + ($weeklyRate >= 100 ? 1 : 0);

            return [
                'rank'             => $index + 1,
                'name'             => $animal['name'] . ' ' . strtolower($adjective),
                'avatar'           => $animal['icon'],
                'profit_trend'     => $entry->net_profit > 0 ? 'positive' : ($entry->net_profit < 0 ? 'negative' : 'neutral'),
                'roi'              => $entry->roi,
                'win_rate'         => $entry->win_rate,
                'bets_count'       => $entry->bets_count,
                'exact_score_wins' => $entry->exact_score_wins,
                'level'            => $entry->max_level,
                'badge_count'      => $entry->badge_count,
                'total_missions'   => $entry->total_missions,
                'weekly_missions'  => $entry->weekly_missions,
                'weekly_rate'      => $weeklyRate,
                'perfect_weeks'    => $perfectWeeks,
                'is_me'            => $entry->user_id === auth()->id(),
                'achievements'     => $userAchievements->map(fn($ach) => [
                    'name'        => $ach->name,
                    'description' => $ach->description,
                    'icon'        => $ach->icon,
                    'color'       => $ach->color,
                    'is_main'     => !is_null($ach->level),
                ])->toArray(),
            ];
        })->toArray();
    }
}
