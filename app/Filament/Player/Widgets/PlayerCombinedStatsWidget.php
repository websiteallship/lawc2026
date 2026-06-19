<?php

namespace App\Filament\Player\Widgets;

use App\Models\Bet;
use App\Models\Season;
use App\Models\Wallet;
use App\Models\UserStatistic;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PlayerCombinedStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = [
        'default' => 2,
        'sm'      => 2,
        'md'      => 4,
        'lg'      => 4,
        'xl'      => 4,
    ];

    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        return \Illuminate\Support\Facades\Cache::remember("player_combined_stats_{$user->id}", 900, function () use ($user) {
            // 1. Wallet Stats
            $activeSeason = Season::where('status', 'active')->first();
            $wallet = null;
            if ($activeSeason) {
                $wallet = Wallet::where('user_id', $user->id)
                    ->where('season_id', $activeSeason->id)
                    ->first();
            }
            $available = $wallet ? $wallet->available_balance : 0;
            $locked = $wallet ? $wallet->locked_balance : 0;

            // 2. Pending Bets Stats
            $pendingBetCount = Bet::where('user_id', $user->id)->where('status', 'PENDING')->count();

            // 3. User Statistics (Win Rate, ROI, Streak, Net Profit)
            $stats = UserStatistic::where('user_id', $user->id)->first();
            $netProfit    = $stats ? $stats->net_profit : 0;
            $winRate      = $stats ? $stats->win_rate : 0;
            $roi          = $stats ? $stats->roi : 0;
            $currentStreak = $stats ? $stats->current_win_streak : 0;
            $totalPayout  = $stats ? $stats->total_payout : 0;    // tổng lá nhận về từ các vé thắng/hòa
            $totalStaked  = $stats ? $stats->total_staked : 0;    // tổng lá đã đặt (chỉ settled)
            $totalLost    = max(0, $totalStaked - $totalPayout);   // tổng lá bị mất

            return [
                Stat::make('Lá khả dụng', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . number_format($available) . " lá</span>"))
                    ->description('Số lá có thể đặt cược')
                    ->descriptionIcon('heroicon-m-wallet')
                    ->color('success'),
                    
                Stat::make('Đang khóa', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . number_format($locked) . " lá</span>"))
                    ->description($pendingBetCount . ' phiếu đang chờ')
                    ->descriptionIcon('heroicon-m-lock-closed')
                    ->color('warning'),
                    
                Stat::make('Lãi ròng', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . (($netProfit !== null) ? (($netProfit >= 0 ? '+' : '') . number_format($netProfit) . ' lá') : '0 lá') . "</span>"))
                    ->description('Lãi/lỗ mùa giải này')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color($netProfit !== null && $netProfit >= 0 ? 'success' : 'danger'),

                Stat::make('Tổng lá thắng về', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . number_format($totalPayout) . " lá</span>"))
                    ->description('Tổng payout từ các vé đã settle')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success'),

                Stat::make('Tổng lá đã mất', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . number_format($totalLost) . " lá</span>"))
                    ->description('Cược - payout (vé thua/nửa thua)')
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->color($totalLost > 0 ? 'danger' : 'gray'),
                    
                Stat::make('Tỉ lệ thắng (Win Rate)', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . number_format($winRate, 2) . "%</span>"))
                    ->description('Vé thắng / vé đã đóng')
                    ->descriptionIcon('heroicon-m-chart-bar')
                    ->color($winRate >= 50 ? 'success' : ($winRate > 0 ? 'warning' : 'danger')),
                    
                Stat::make('Tỉ suất lợi nhuận (ROI)', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . number_format($roi, 2) . "%</span>"))
                    ->description('Hiệu quả trên tổng cược')
                    ->descriptionIcon('heroicon-m-presentation-chart-line')
                    ->color($roi >= 0 ? 'success' : 'danger'),
                    
                Stat::make('Chuỗi thắng hiện tại', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . $currentStreak . "</span>"))
                    ->description('Liên tiếp: ' . $currentStreak . ' vé')
                    ->descriptionIcon('heroicon-m-fire')
                    ->color($currentStreak > 0 ? 'warning' : 'gray'),
            ];
        });
    }
}
