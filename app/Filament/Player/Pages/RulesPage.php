<?php

namespace App\Filament\Player\Pages;

use Filament\Pages\Page;

class RulesPage extends Page
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-book-open';
    }

    protected static ?string $navigationLabel = 'Thể lệ';

    protected static ?string $title = 'Thể lệ tham gia';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.player.pages.rules';
}
