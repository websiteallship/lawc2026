<?php

namespace App\Filament\Player\Pages;

use Filament\Pages\Page;

class UserGuidePage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-information-circle';
    protected static ?string $navigationLabel = 'Hướng dẫn';
    protected static ?string $title = 'Thể lệ & Hướng dẫn';
    protected static ?int $navigationSort = 90;
    
    public static function getNavigationGroup(): ?string
    {
        return 'Hướng dẫn & Thể lệ';
    }
    
    protected string $view = 'filament.player.pages.user-guide-page';
}
