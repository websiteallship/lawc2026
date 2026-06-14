<?php

namespace App\Filament\Widgets;

use App\Models\Market;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeSeason = Season::where('status', 'active')->first();

        $activeUsers = User::where('status', 'ACTIVE')->count();

        $totalAvailable = 0;
        $totalLocked = 0;
        if ($activeSeason) {
            $totalAvailable = Wallet::where('season_id', $activeSeason->id)->sum('available_balance');
            $totalLocked = Wallet::where('season_id', $activeSeason->id)->sum('locked_balance');
        }

        $openMarkets = Market::where('status', 'OPEN')->count();
        $settleNeeded = Market::where('status', 'LOCKED')->count();

        return [
            Stat::make('Người chơi đang hoạt động', number_format($activeUsers))
                ->description('Tài khoản ACTIVE')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Lá đang lưu hành', number_format($totalAvailable))
                ->description($activeSeason ? "Mùa: {$activeSeason->name}" : 'Chưa có mùa giải')
                ->icon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make('Lá đang bị khoá', number_format($totalLocked))
                ->description('Đang trong phiếu chưa mở thưởng')
                ->icon('heroicon-o-lock-closed')
                ->color('warning'),

            Stat::make('Kèo đang mở', number_format($openMarkets))
                ->description('Trạng thái OPEN')
                ->icon('heroicon-o-ticket')
                ->color('primary'),

            Stat::make('Kèo chờ mở thưởng', number_format($settleNeeded))
                ->description('Trạng thái LOCKED — cần Execute Settlement')
                ->icon('heroicon-o-clock')
                ->color($settleNeeded > 0 ? 'danger' : 'gray'),
        ];
    }
}
