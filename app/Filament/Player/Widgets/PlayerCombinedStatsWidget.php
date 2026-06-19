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

            // 3. User Statistics (Win Rate, ROI, Net Profit)
            $stats = UserStatistic::where('user_id', $user->id)->first();
            $netProfit  = $stats ? (int) $stats->net_profit : 0;
            $winRate    = $stats ? $stats->win_rate : 0;
            $roi        = $stats ? $stats->roi : 0;
            $wonBets    = $stats ? (int) $stats->won_bets : 0;
            $lostBets   = $stats ? (int) $stats->lost_bets : 0;

            // Tính tổng lá thắng về và tổng lá đã mất từ bảng Bet trực tiếp
            // để tránh net-out effect (người thắng tổng vẫn có tiền mất từng vé thua)
            $betAmounts = Bet::where('user_id', $user->id)
                ->whereNotIn('status', ['PENDING', 'VOIDED'])
                ->selectRaw('
                    COALESCE(SUM(gross_payout), 0) as total_payout,
                    COALESCE(SUM(CASE WHEN net_result < 0 THEN ABS(net_result) ELSE 0 END), 0) as total_lost
                ')
                ->first();

            $totalPayout = $betAmounts ? (int) $betAmounts->total_payout : 0;
            $totalLost   = $betAmounts ? (int) $betAmounts->total_lost : 0;

            return [
                Stat::make('Lá khả dụng', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . number_format($available) . " lá</span>"))
                    ->description('Số lá có thể đặt cược')
                    ->descriptionIcon('heroicon-m-wallet')
                    ->color('success'),
                    
                Stat::make('Đang khóa', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . number_format($locked) . " lá</span>"))
                    ->description($pendingBetCount . ' phiếu đang chờ')
                    ->descriptionIcon('heroicon-m-lock-closed')
                    ->color('warning'),
                    
                Stat::make('Lãi ròng', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . (($netProfit >= 0 ? '+' : '') . number_format($netProfit) . ' lá') . "</span>"))
                    ->description('Lãi/lỗ mùa giải này')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color($netProfit >= 0 ? 'success' : 'danger'),

                Stat::make('Tổng lá thắng về', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . number_format($totalPayout) . " lá</span>"))
                    ->description('Tổng payout nhận về từ vé đã settle')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success'),

                Stat::make('Tổng lá đã mất', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'>" . number_format($totalLost) . " lá</span>"))
                    ->description('Tổng lỗ từ các vé thua/nửa thua')
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

                Stat::make('Thắng / Thua', new \Illuminate\Support\HtmlString("<span class='!text-lg sm:!text-xl md:!text-3xl font-semibold block truncate'><span class='text-emerald-500'>" . $wonBets . "</span> / <span class='text-red-400'>" . $lostBets . "</span></span>"))
                    ->description('Số vé thắng / số vé thua')
                    ->descriptionIcon('heroicon-m-scale')
                    ->color($wonBets > $lostBets ? 'success' : ($wonBets < $lostBets ? 'danger' : 'gray')),
            ];
        });
    }
}
