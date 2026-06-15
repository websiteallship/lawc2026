<?php

namespace App\Filament\Player\Widgets;

use App\Models\UserAchievement;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PlayerRecentBadgesWidget extends BaseWidget
{
    protected static ?string $heading = 'Thành Tựu Gần Đây';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 6,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UserAchievement::query()
                    ->where('user_id', Auth::id())
                    ->with('achievement')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('achievement.name')
                    ->label('Huy Hiệu')
                    ->icon(fn (UserAchievement $record) => $record->achievement->icon ?? 'heroicon-o-star')
                    ->weight('bold')
                    ->description(fn (UserAchievement $record): string => $record->achievement->description ?? ''),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời gian đạt được')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
