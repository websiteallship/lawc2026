<?php

namespace App\Filament\Widgets;

use App\Models\Market;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class AdminMarketStatusListWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Market cần chú ý (Action Required)')
            ->query(
                Market::query()
                    ->with('match')
                    ->where(function (Builder $query) {
                        // Sắp đóng (< 60 phút)
                        $query->where('status', 'OPEN')
                              ->whereNotNull('close_at')
                              ->where('close_at', '<=', now()->addMinutes(60));
                    })
                    ->orWhere(function (Builder $query) {
                        // Cần settle: LOCKED
                        $query->where('status', 'LOCKED');
                    })
                    ->orderBy('close_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('match.match_code')
                    ->label('Trận đấu')
                    ->searchable()
                    ->html()
                    ->formatStateUsing(function ($state, Market $record) {
                        if (!$record->match) return '';
                        $home = \App\Helpers\CountryFlagHelper::renderHtml($record->match->home_team);
                        $away = \App\Helpers\CountryFlagHelper::renderHtml($record->match->away_team);
                        return "<b>{$record->match->match_code}</b><br><span class='text-xs'>{$home} vs {$away}</span>";
                    })
                    ->description(fn (Market $record): string => $record->match?->kickoff_at?->format('d/m/Y H:i') ?? ''),
                Tables\Columns\TextColumn::make('name')
                    ->label('Market')
                    ->description(fn (Market $record): string => "{$record->market_type} | {$record->period_type}"),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'OPEN' => 'primary',
                        'LOCKED' => 'warning',
                        'SETTLED' => 'success',
                        'VOIDED' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('close_at')
                    ->label('Giờ khóa')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('warning')
                    ->label('Cảnh báo')
                    ->badge()
                    ->getStateUsing(function (Market $record) {
                        if ($record->status === 'OPEN') {
                            if ($record->close_at && $record->close_at <= now()) {
                                return 'Quá giờ khóa';
                            }
                            if ($record->close_at && $record->close_at <= now()->addMinutes(5)) {
                                return 'Sắp đóng (< 5p)';
                            }
                            return 'Sắp đóng (< 60p)';
                        }
                        if ($record->status === 'LOCKED') {
                            if ($record->match && $record->match->status === 'FINISHED') {
                                return 'Chờ KQ (Match Ended)';
                            }
                            return 'Chờ Settle';
                        }
                        return '';
                    })
                    ->color(function (Market $record) {
                        if ($record->status === 'OPEN' && $record->close_at && $record->close_at <= now()) {
                            return 'danger';
                        }
                        if ($record->status === 'OPEN' && $record->close_at && $record->close_at <= now()->addMinutes(5)) {
                            return 'warning';
                        }
                        if ($record->status === 'LOCKED' && $record->match && $record->match->status === 'FINISHED') {
                            return 'danger';
                        }
                        return 'gray';
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('edit')
                    ->label('Sửa')
                    ->url(fn (Market $record): string => \App\Filament\Resources\MarketResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-m-pencil-square'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
