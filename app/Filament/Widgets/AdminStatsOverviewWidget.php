<?php

namespace App\Filament\Widgets;

use App\Domain\Market\Services\ApiQuotaService;
use App\Models\Market;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class AdminStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | array | null $columns = [
        'default' => 2,
        'sm' => 2,
        'md' => 3,
        'lg' => 4,
    ];

    protected function getStats(): array
    {
        $activeSeason = Season::where('status', 'active')->first();

        // Lấy IDs của test users để loại khỏi báo cáo
        $testUserIds = User::where('is_test_user', true)->pluck('id');

        $activeUsers = User::where('status', 'ACTIVE')
            ->where('is_test_user', false)
            ->count();

        $totalAvailable = 0;
        $totalLocked = 0;
        $negativeWallets = 0;
        
        if ($activeSeason) {
            $walletBase = \App\Models\Wallet::where('season_id', $activeSeason->id)
                ->whereNotIn('user_id', $testUserIds);
            $totalAvailable  = (clone $walletBase)->sum('available_balance');
            $totalLocked     = (clone $walletBase)->sum('locked_balance');
            $negativeWallets = (clone $walletBase)->where('available_balance', '<', 0)->count();
        } else {
            $negativeWallets = \App\Models\Wallet::whereNotIn('user_id', $testUserIds)
                ->where('available_balance', '<', 0)->count();
        }

        $openMarkets   = Market::where('status', 'OPEN')->count();
        $settleNeeded  = Market::where('status', 'LOCKED')->count();
        
        $failedJobs = DB::table('failed_jobs')->count();

        // API Quota calculation
        $quotaService = app(ApiQuotaService::class);
        $remaining = $quotaService->getQuotaRemaining();
        $limit = $quotaService->getQuotaLimit() ?? 7500;

        if ($remaining === null) {
            $apiStat = Stat::make('API Quota', '0 / ' . number_format($limit))
                ->description('Chưa có request nào')
                ->icon('heroicon-o-information-circle')
                ->color('gray');
        } else {
            $used = $limit - $remaining;
            $percentage = $limit > 0 ? ($remaining / $limit) * 100 : 0;
            $color = 'success';
            if ($percentage <= 10) {
                $color = 'danger';
            } elseif ($percentage <= 30) {
                $color = 'warning';
            }
            $apiStat = Stat::make('API Quota', number_format($used) . ' / ' . number_format($limit))
                ->description('Còn lại: ' . number_format($remaining) . ' (' . round($percentage, 1) . '%)')
                ->icon('heroicon-o-chart-pie')
                ->color($color);
        }

        return [
            $apiStat,

            Stat::make('Người chơi HĐ', number_format($activeUsers))
                ->description('Tài khoản ACTIVE')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Lá lưu hành', number_format($totalAvailable))
                ->description($activeSeason ? "Mùa: {$activeSeason->name}" : 'Chưa có mùa giải')
                ->icon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make('Lá bị khoá', number_format($totalLocked))
                ->description('Đang trong phiếu')
                ->icon('heroicon-o-lock-closed')
                ->color('warning'),

            Stat::make('Kèo đang mở', number_format($openMarkets))
                ->description('Trạng thái OPEN')
                ->icon('heroicon-o-ticket')
                ->color('primary'),

            Stat::make('Kèo chờ KQ', number_format($settleNeeded))
                ->description('LOCKED — cần Execute')
                ->icon('heroicon-o-clock')
                ->color($settleNeeded > 0 ? 'danger' : 'gray'),
                
            Stat::make('Cảnh báo Ví âm', number_format($negativeWallets))
                ->description('Do Correction')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($negativeWallets > 0 ? 'danger' : 'success'),
                
            Stat::make('Lỗi Failed Jobs', number_format($failedJobs))
                ->description('Queue Health')
                ->icon('heroicon-o-server-stack')
                ->color($failedJobs > 0 ? 'danger' : 'success'),
        ];
    }
}
