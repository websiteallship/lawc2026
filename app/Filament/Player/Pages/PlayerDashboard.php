<?php

namespace App\Filament\Player\Pages;

use App\Filament\Player\Widgets\PlayerPendingBetsWidget;
use App\Filament\Player\Widgets\PlayerQuickLinksWidget;
use App\Filament\Player\Widgets\PlayerUpcomingMatchesWidget;
use App\Filament\Player\Widgets\PlayerWalletWidget;
use Filament\Pages\Page;

class PlayerDashboard extends Page
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-home';
    }

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.player.pages.player-dashboard-empty';

    protected function getHeaderWidgets(): array
    {
        return [
            PlayerWalletWidget::class,
            PlayerPendingBetsWidget::class,
            PlayerUpcomingMatchesWidget::class,
            PlayerQuickLinksWidget::class,
        ];
    }
}
