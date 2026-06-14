<?php

namespace App\Filament\Player\Widgets;

use Filament\Widgets\Widget;

class PlayerQuickLinksWidget extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.player.widgets.player-quick-links-widget';
}
