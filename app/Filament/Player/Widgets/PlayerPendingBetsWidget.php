<?php

namespace App\Filament\Player\Widgets;

use App\Models\Bet;
use App\Models\Season;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PlayerPendingBetsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $user = Auth::user();

        $pendingBets = Bet::where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->get();

        $pendingBetCount = $pendingBets->count();
        $pendingStakeTotal = $pendingBets->sum('stake');

        $activeSeason = Season::where('status', 'active')->first();
        $netProfit = null;

        if ($activeSeason) {
            $wallet = Wallet::where('user_id', $user->id)
                ->where('season_id', $activeSeason->id)
                ->first();
            if ($wallet) {
                $netProfit = $wallet->net_profit;
            }
        }

        $profitStat = Stat::make('Thứ hạng / Lãi lỗ', ($netProfit !== null) ? (($netProfit >= 0 ? '+' : '').number_format($netProfit).' lá') : 'Chưa có dữ liệu')
            ->description('Lãi/lỗ mùa giải này')
            ->descriptionIcon('heroicon-m-trophy');

        if ($netProfit !== null && $netProfit >= 0) {
            $profitStat->color('success');
        } elseif ($netProfit !== null && $netProfit < 0) {
            $profitStat->color('danger');
        } else {
            $profitStat->color('gray');
        }

        return [
            Stat::make('Phiếu đang chờ', $pendingBetCount.' phiếu')
                ->description('Tổng lá đang khóa: '.number_format($pendingStakeTotal))
                ->descriptionIcon('heroicon-m-ticket')
                ->color('info'),

            $profitStat,
        ];
    }
}
