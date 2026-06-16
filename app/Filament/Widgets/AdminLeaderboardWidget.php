<?php

namespace App\Filament\Widgets;

use App\Models\LeaderboardSnapshot;
use App\Models\Season;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AdminLeaderboardWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected static ?string $heading = 'Top 10 Bảng Xếp Hạng';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $activeSeason = Season::where('status', 'active')->first();

        $latestSnapshotDate = LeaderboardSnapshot::query()
            ->when($activeSeason, fn ($q) => $q->where('season_id', $activeSeason->id))
            ->max('snapshot_at');

        return $table
            ->query(
                LeaderboardSnapshot::query()
                    ->when($activeSeason, fn ($q) => $q->where('season_id', $activeSeason->id))
                    ->when($latestSnapshotDate, fn ($q) => $q->where('snapshot_at', $latestSnapshotDate))
                    ->orderBy('rank')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 1 => 'warning',
                        $state <= 3 => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Người chơi'),
                Tables\Columns\TextColumn::make('total_balance')
                    ->label('Tổng Lá')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_profit')
                    ->label('Lãi/Lỗ')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                Tables\Columns\TextColumn::make('total_bets')
                    ->label('Số phiếu'),
                Tables\Columns\TextColumn::make('won_bets')
                    ->label('Thắng'),
                Tables\Columns\TextColumn::make('win_rate')
                    ->label('Win rate')
                    ->formatStateUsing(fn ($state) => $state ? round($state * 100, 1).'%' : '-'),
                Tables\Columns\TextColumn::make('snapshot_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated(false);
    }
}
