<?php

namespace App\Filament\Player\Pages;

use Filament\Pages\Page;

class UserGuidePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static ?string $navigationLabel = 'Hướng dẫn';
    protected static ?string $title = 'Thể lệ & Hướng dẫn';
    protected static ?int $navigationSort = 10;
    
    protected string $view = 'filament.player.pages.user-guide-page';
}
