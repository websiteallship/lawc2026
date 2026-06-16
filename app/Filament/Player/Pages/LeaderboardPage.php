<?php

namespace App\Filament\Player\Pages;

use App\Models\Bet;
use App\Models\Season;
use App\Models\Wallet;
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

    public function updatedActiveTab(): void
    {
        $this->loadRankings();
    }

    public function mount(): void
    {
        $this->loadRankings();
    }

    public function loadRankings(): void
    {
        $activeSeason = Season::where('status', 'active')->first();

        if (! $activeSeason) {
            $this->rankings = [];
            return;
        }

        // Lấy tất cả user có role 'player'
        $usersQuery = \App\Models\User::whereHas('roles', function ($q) {
                $q->where('name', 'player');
            })
            ->where('status', 'ACTIVE')
            ->with(['wallets' => function ($q) use ($activeSeason) {
                $q->where('season_id', $activeSeason->id);
            }]);
            
        $users = $usersQuery->get();

        // Tùy theo tab mà lấy ledger để tính toán
        $wallets = $users->map(function ($user) use ($activeSeason) {
            $wallet = $user->wallets->first();
            $walletId = $wallet ? $wallet->id : null;
            
            $netProfit = 0;
            $totalStaked = 0;
            $betsCount = 0;
            $wonCount = 0;
            
            if ($walletId) {
                $query = \App\Models\WalletLedger::withoutGlobalScope('player_isolation')
                    ->where('wallet_id', $walletId)
                    ->whereIn('type', [
                        \App\Enums\LedgerType::BET_WON->value, 
                        \App\Enums\LedgerType::BET_LOST->value, 
                        \App\Enums\LedgerType::BET_PUSH->value, 
                        \App\Enums\LedgerType::BET_HALF_WON->value, 
                        \App\Enums\LedgerType::BET_HALF_LOST->value
                    ]);

                if ($this->activeTab === 'week') {
                    $query->where('created_at', '>=', now()->startOfWeek());
                } elseif ($this->activeTab === 'exact_score') {
                    // Logic tính riêng cho kèo tỉ số cần join bảng bet. Để đơn giản MVP Phase 2, ta có thể dùng bảng user_statistics
                    // Tuy nhiên vì hệ thống dùng WalletLedger, ta tạm lọc qua relationship (nếu cần thiết).
                    // Tạm thời nếu ko có user_statistics, ta dùng wallet tổng hợp.
                }

                // Dành cho season, roi, vv: dùng sẵn cache trên wallet để nhanh
                if (in_array($this->activeTab, ['season', 'roi'])) {
                    $netProfit = $wallet->net_profit;
                    $totalStaked = $wallet->total_staked;
                } else {
                    // Tính runtime cho Week/Round
                    $ledgers = $query->get();
                    // ... tính toán (Sẽ bổ sung sau khi có bảng user_statistics chuẩn, hiện tại MVP Phase 2 yêu cầu bảng UserStatistics)
                    // Do spec đã nói có bảng user_statistics (Thêm UserStatistic table (đã định nghĩa ở script trước)), nên ta dùng bảng đó.
                }
            }
            
            return (object) [
                'user_id' => $user->id,
                'wallet_id' => $walletId,
                'available_balance' => $wallet ? $wallet->available_balance : 0,
                'net_profit' => $wallet ? $wallet->net_profit : 0,
                'total_staked' => $wallet ? $wallet->total_staked : 0,
                'user' => $user,
            ];
        });

        // Tạm thời sắp xếp theo Mùa giải cho các tab cho tới khi tích hợp UserStatistic 
        if ($this->activeTab === 'roi') {
            $wallets = $wallets->filter(fn($w) => $w->total_staked >= 500)
                               ->sortByDesc(fn($w) => $w->total_staked > 0 ? ($w->net_profit / $w->total_staked) : -999);
        } else {
            $wallets = $wallets->sortBy([
                ['net_profit', 'desc'],
                ['available_balance', 'desc'],
            ]);
        }
        
        $wallets = $wallets->take(50)->values();

        $this->rankings = $wallets->map(function ($wallet, $index) {
            $betsCount = $wallet->wallet_id ? \App\Models\WalletLedger::withoutGlobalScope('player_isolation')
                ->where('wallet_id', $wallet->wallet_id)
                ->whereIn('type', [
                    \App\Enums\LedgerType::BET_WON->value, 
                    \App\Enums\LedgerType::BET_LOST->value, 
                    \App\Enums\LedgerType::BET_PUSH->value, 
                    \App\Enums\LedgerType::BET_HALF_WON->value, 
                    \App\Enums\LedgerType::BET_HALF_LOST->value
                ])
                ->count() : 0;
            $wonCount = $wallet->wallet_id ? \App\Models\WalletLedger::withoutGlobalScope('player_isolation')
                ->where('wallet_id', $wallet->wallet_id)
                ->whereIn('type', [\App\Enums\LedgerType::BET_WON->value, \App\Enums\LedgerType::BET_HALF_WON->value])
                ->count() : 0;

            $userAchievements = \App\Models\UserAchievement::where('user_id', $wallet->user_id)
                ->join('achievements', 'user_achievements.achievement_id', '=', 'achievements.id')
                ->select('achievements.*')
                ->get();
            $mainLevel = $userAchievements->whereNotNull('level')->max('level') ?: 0;
            
            $winRate = $betsCount > 0
                ? round(($wonCount / $betsCount) * 100, 1)
                : 0;

            $roi = $wallet->total_staked > 0
                ? round(($wallet->net_profit / $wallet->total_staked) * 100, 1)
                : null;

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

            $hash = md5($wallet->user_id.config('app.key'));
            $animalIndex = hexdec(substr($hash, 0, 4)) % count($animals);
            $adjIndex = hexdec(substr($hash, 4, 4)) % count($adjectives);

            $animal = $animals[$animalIndex];
            $adjective = $adjectives[$adjIndex];

            $avatarName = $animal['name'].' '.strtolower($adjective);

            return [
                'rank' => $index + 1,
                'name' => $avatarName,
                'avatar' => $animal['icon'],
                'net_profit' => $wallet->net_profit,
                'roi' => $roi,
                'win_rate' => $winRate,
                'bets_count' => $betsCount,
                'available' => $wallet->available_balance,
                'level' => $mainLevel,
                'achievements' => $userAchievements->map(function ($ach) {
                    return [
                        'name' => $ach->name,
                        'description' => $ach->description,
                        'icon' => $ach->icon,
                        'color' => $ach->color,
                        'is_main' => !is_null($ach->level),
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}
