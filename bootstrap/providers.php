<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\PlayerPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    PlayerPanelProvider::class,
];
