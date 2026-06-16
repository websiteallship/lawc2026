<?php

namespace App\Filament\Widgets;

use App\Domain\Market\Services\ApiQuotaService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApiQuotaWidget extends BaseWidget
{
    protected static ?int $sort = -3;
    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return ! request()->routeIs('filament.admin.pages.dashboard');
    }

    protected function getStats(): array
    {
        $quotaService = app(ApiQuotaService::class);
        $remaining = $quotaService->getQuotaRemaining();
        $limit = $quotaService->getQuotaLimit() ?? 7500;

        if ($remaining === null) {
            return [
                Stat::make('API-Sports Quota Đã Dùng', '0 / ' . number_format($limit))
                    ->description('Chưa có request nào được thực hiện')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('gray'),
            ];
        }

        $used = $limit - $remaining;
        $percentage = $limit > 0 ? ($remaining / $limit) * 100 : 0;

        $color = 'success';
        if ($percentage <= 10) {
            $color = 'danger';
        } elseif ($percentage <= 30) {
            $color = 'warning';
        }

        return [
            Stat::make('API-Sports Quota Đã Dùng', number_format($used) . ' / ' . number_format($limit))
                ->description('Còn lại: ' . number_format($remaining) . ' requests (' . round($percentage, 1) . '%)')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($color),
        ];
    }
}
