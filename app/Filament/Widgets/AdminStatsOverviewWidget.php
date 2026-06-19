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
            $apiStat = Stat::make(new HtmlString('<span class="text-xs sm:text-sm">API Quota</span>'), new HtmlString('<span class="text-lg sm:text-3xl font-bold">0 / ' . number_format($limit) . '</span>'))
                ->description(new HtmlString('<span class="text-[10px] sm:text-sm">Chưa có request nào</span>'))
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
            $apiStat = Stat::make(new HtmlString('<span class="text-xs sm:text-sm">API Quota</span>'), new HtmlString('<span class="text-lg sm:text-3xl font-bold">' . number_format($used) . ' / ' . number_format($limit) . '</span>'))
                ->description(new HtmlString('<span class="text-[10px] sm:text-sm">Còn lại: ' . number_format($remaining) . ' (' . round($percentage, 1) . '%)</span>'))
                ->icon('heroicon-o-chart-pie')
                ->color($color);
        }

        return [
            $apiStat,

            Stat::make(new HtmlString('<span class="text-xs sm:text-sm">Người chơi HĐ</span>'), new HtmlString('<span class="text-lg sm:text-3xl font-bold">' . number_format($activeUsers) . '</span>'))
                ->description(new HtmlString('<span class="text-[10px] sm:text-sm">Tài khoản ACTIVE</span>'))
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make(new HtmlString('<span class="text-xs sm:text-sm">Lá lưu hành</span>'), new HtmlString('<span class="text-lg sm:text-3xl font-bold">' . number_format($totalAvailable) . '</span>'))
                ->description(new HtmlString('<span class="text-[10px] sm:text-sm">' . ($activeSeason ? "Mùa: {$activeSeason->name}" : 'Chưa có mùa giải') . '</span>'))
                ->icon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make(new HtmlString('<span class="text-xs sm:text-sm">Lá bị khoá</span>'), new HtmlString('<span class="text-lg sm:text-3xl font-bold">' . number_format($totalLocked) . '</span>'))
                ->description(new HtmlString('<span class="text-[10px] sm:text-sm">Đang trong phiếu</span>'))
                ->icon('heroicon-o-lock-closed')
                ->color('warning'),

            Stat::make(new HtmlString('<span class="text-xs sm:text-sm">Kèo đang mở</span>'), new HtmlString('<span class="text-lg sm:text-3xl font-bold">' . number_format($openMarkets) . '</span>'))
                ->description(new HtmlString('<span class="text-[10px] sm:text-sm">Trạng thái OPEN</span>'))
                ->icon('heroicon-o-ticket')
                ->color('primary'),

            Stat::make(new HtmlString('<span class="text-xs sm:text-sm">Kèo chờ KQ</span>'), new HtmlString('<span class="text-lg sm:text-3xl font-bold">' . number_format($settleNeeded) . '</span>'))
                ->description(new HtmlString('<span class="text-[10px] sm:text-sm">LOCKED — cần Execute</span>'))
                ->icon('heroicon-o-clock')
                ->color($settleNeeded > 0 ? 'danger' : 'gray'),
                
            Stat::make(new HtmlString('<span class="text-xs sm:text-sm">Cảnh báo Ví âm</span>'), new HtmlString('<span class="text-lg sm:text-3xl font-bold">' . number_format($negativeWallets) . '</span>'))
                ->description(new HtmlString('<span class="text-[10px] sm:text-sm">Do Correction</span>'))
                ->icon('heroicon-o-exclamation-triangle')
                ->color($negativeWallets > 0 ? 'danger' : 'success'),
                
            Stat::make(new HtmlString('<span class="text-xs sm:text-sm">Lỗi Failed Jobs</span>'), new HtmlString('<span class="text-lg sm:text-3xl font-bold">' . number_format($failedJobs) . '</span>'))
                ->description(new HtmlString('<span class="text-[10px] sm:text-sm">Queue Health</span>'))
                ->icon('heroicon-o-server-stack')
                ->color($failedJobs > 0 ? 'danger' : 'success'),
        ];
    }
}
