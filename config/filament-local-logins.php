<?php

use BetterFuturesStudio\FilamentLocalLogins\Filament\Pages\Auth\LoginPage;

return [
    'panels' => [
        'admin' => [
            'enabled' => env('ADMIN_PANEL_LOCAL_LOGINS_ENABLED', true),
            'emails' => ['admin@example.com'],
            'login_page' => LoginPage::class,
        ],
        'player' => [
            'enabled' => env('PLAYER_PANEL_LOCAL_LOGINS_ENABLED', true),
            'emails' => ['player1@test.com', 'player2@test.com', 'player3@test.com', 'player4@test.com', 'player5@test.com'],
            'login_page' => LoginPage::class,
        ],
    ],
];
