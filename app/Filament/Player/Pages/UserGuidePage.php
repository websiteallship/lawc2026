<?php

namespace App\Filament\Player\Pages;

use Filament\Pages\Page;

class UserGuidePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Hướng dẫn';
    protected static ?string $title = 'Thể lệ & Hướng dẫn';
    protected static ?int $navigationSort = 10;
    
    protected string $view = 'filament.player.pages.user-guide-page';

    protected function getViewData(): array
    {
        $path = base_path('docs/product/10_USER_GUIDE_THE_LE.md');
        $content = file_exists($path) ? file_get_contents($path) : 'Tài liệu đang được cập nhật.';
        
        // Remove yaml frontmatter
        $content = preg_replace('/^---\n.*?\n---\n/s', '', $content);
        
        return [
            'guideContent' => \Illuminate\Support\Str::markdown($content),
        ];
    }
}
