<?php

namespace App\Filament\Player\Widgets;

use App\Models\Market;
use Filament\Widgets\Widget;

class PlayerUpcomingMatchesWidget extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.player.widgets.player-upcoming-matches-widget';

    public array $upcomingMarkets = [];

    public function mount(): void
    {
        $this->upcomingMarkets = Market::with(['match'])
            ->where('status', 'OPEN')
            ->where('close_at', '>', now())
            ->orderBy('close_at')
            ->take(3)
            ->get()
            ->toArray();
    }
}
