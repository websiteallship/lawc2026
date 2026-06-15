<?php

namespace App\Filament\Player\Pages;

use App\Filament\Player\Widgets\PlayerUpcomingMatchesWidget;
use App\Filament\Player\Widgets\PlayerCombinedStatsWidget;
use App\Filament\Player\Widgets\PlayerProfitChartWidget;
use App\Filament\Player\Widgets\PlayerMissionsWidget;
use App\Filament\Player\Widgets\PlayerRecentBadgesWidget;
use Filament\Pages\Page;

class PlayerDashboard extends Page
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-home';
    }

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.player.pages.player-dashboard-empty';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('view_markets')
                ->label('Kèo đang mở')
                ->icon('heroicon-m-bolt')
                ->url(\App\Filament\Player\Pages\MatchListPage::getUrl())
                ->color('primary'),
            \Filament\Actions\Action::make('view_bets')
                ->label('Phiếu cược')
                ->icon('heroicon-m-ticket')
                ->url(\App\Filament\Player\Pages\MyBetsPage::getUrl())
                ->color('info'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlayerCombinedStatsWidget::class,
            PlayerProfitChartWidget::class,
            PlayerMissionsWidget::class,
            PlayerUpcomingMatchesWidget::class,
            PlayerRecentBadgesWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 12,
        ];
    }
}
