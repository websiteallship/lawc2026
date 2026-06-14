<?php

namespace App\Filament\Player\Widgets;

use App\Models\Season;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PlayerWalletWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $user = Auth::user();
        $activeSeason = Season::where('status', 'active')->first();
        $wallet = null;

        if ($activeSeason) {
            $wallet = Wallet::where('user_id', $user->id)
                ->where('season_id', $activeSeason->id)
                ->first();
        }

        $available = $wallet ? $wallet->available_balance : 0;
        $locked = $wallet ? $wallet->locked_balance : 0;
        $total = $wallet ? $wallet->total_balance : 0;

        return [
            Stat::make('Lá khả dụng', number_format($available).' lá')
                ->description('Số lá có thể dùng để đặt cược')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('success'),
            Stat::make('Đang khóa', number_format($locked).' lá')
                ->description('Số lá đang nằm trong các phiếu chưa duyệt')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('warning'),
            Stat::make('Tổng lá', number_format($total).' lá')
                ->description('Tổng số lá hiện có')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),
        ];
    }
}
