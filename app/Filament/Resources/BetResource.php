<?php

namespace App\Filament\Resources;

use App\Enums\BetStatus;
use App\Filament\Resources\BetResource\Pages;
use App\Helpers\CountryFlagHelper;
use App\Models\Bet;
use App\Models\FootballMatch;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BetResource extends Resource
{
    protected static ?string $model = Bet::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-ticket';
    }

    public static function getModelLabel(): string
    {
        return 'Vé dự đoán';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Vé dự đoán';
    }

    public static function getNavigationGroup(): string|\BackedEnum|null
    {
        return 'Quản lý Giao dịch';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('public_code')
                    ->label('Mã phiếu')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Người chơi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('market.match.match_code')
                    ->label('Trận đấu')
                    ->html()
                    ->formatStateUsing(function ($state, Bet $record) {
                        if (! $record->market || ! $record->market->match) {
                            return '-';
                        }
                        $home = CountryFlagHelper::renderHtml($record->market->match->home_team);
                        $away = CountryFlagHelper::renderHtml($record->market->match->away_team);

                        return "<b>{$record->market->match->match_code}</b><br><span class='text-xs'>{$home} vs {$away}</span>";
                    }),
                Tables\Columns\TextColumn::make('market_type_snapshot')
                    ->label('Loại kèo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'ASIAN_HANDICAP' => 'Handicap',
                        'OVER_UNDER' => 'Tài/Xỉu',
                        'EXACT_SCORE' => 'Tỉ số',
                        '1X2' => '1X2',
                        default => $state
                    }),
                Tables\Columns\TextColumn::make('label_snapshot')
                    ->label('Lựa chọn')
                    ->description(fn (Bet $record) => $record->display_odds_snapshot),
                Tables\Columns\TextColumn::make('stake')
                    ->label('Số Lá')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (BetStatus $state): string => match ($state->value) {
                        'WON', 'HALF_WON' => 'success',
                        'LOST', 'HALF_LOST', 'VOIDED' => 'danger',
                        'PUSH' => 'info',
                        'PENDING' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (BetStatus $state) => match ($state->value) {
                        'PENDING' => 'Chưa mở',
                        'WON' => 'Thắng đủ',
                        'HALF_WON' => 'Thắng nửa',
                        'LOST' => 'Thua đủ',
                        'HALF_LOST' => 'Thua nửa',
                        'PUSH' => 'Hòa',
                        'VOIDED' => 'Hoàn',
                        'CORRECTED' => 'Điều chỉnh',
                        default => $state->value,
                    }),
                Tables\Columns\TextColumn::make('gross_payout')
                    ->label('Trả thưởng')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('placed_at')
                    ->label('Đặt lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'PENDING' => 'Chưa mở',
                        'WON' => 'Thắng đủ',
                        'HALF_WON' => 'Thắng nửa',
                        'LOST' => 'Thua đủ',
                        'HALF_LOST' => 'Thua nửa',
                        'PUSH' => 'Hòa',
                        'VOIDED' => 'Đã hoàn',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Người chơi')
                    ->options(User::role('player')->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('market_match_id')
                    ->label('Trận đấu')
                    ->options(FootballMatch::all()->mapWithKeys(function ($m) {
                        return [$m->id => "{$m->match_code} | {$m->home_team} vs {$m->away_team}"];
                    }))
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('market', function ($q) use ($data) {
                                $q->where('match_id', $data['value']);
                            });
                        }
                    })
                    ->searchable(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('export_csv')
                        ->label('Export CSV')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $filename = 'bets_export_'.now()->format('Ymd_His').'.csv';
                            $headers = [
                                'Content-type' => 'text/csv',
                                'Content-Disposition' => "attachment; filename=$filename",
                                'Pragma' => 'no-cache',
                                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                                'Expires' => '0',
                            ];

                            $callback = function () use ($records) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, ['Mã phiếu', 'Người chơi', 'Trận đấu', 'Kèo', 'Lựa chọn', 'Tỉ lệ', 'Tiền cược', 'Trạng thái', 'Trả thưởng', 'Ngày đặt']);

                                foreach ($records as $bet) {
                                    $matchName = $bet->market && $bet->market->match ? "{$bet->market->match->home_team} vs {$bet->market->match->away_team}" : '';
                                    fputcsv($file, [
                                        $bet->public_code,
                                        $bet->user->name ?? '',
                                        $matchName,
                                        $bet->market_type_snapshot,
                                        $bet->label_snapshot,
                                        $bet->display_odds_snapshot,
                                        $bet->stake,
                                        $bet->status->value,
                                        $bet->gross_payout,
                                        $bet->placed_at?->format('Y-m-d H:i:s'),
                                    ]);
                                }
                                fclose($file);
                            };

                            return response()->stream($callback, 200, $headers);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin vé')
                    ->schema([
                        TextEntry::make('public_code')->label('Mã phiếu'),
                        TextEntry::make('user.name')->label('Người chơi'),
                        TextEntry::make('placed_at')->label('Đặt lúc')->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('status')
                            ->label('Trạng thái')
                            ->badge()
                            ->color(fn (BetStatus $state): string => match ($state->value) {
                                'WON', 'HALF_WON' => 'success',
                                'LOST', 'HALF_LOST', 'VOIDED' => 'danger',
                                'PUSH' => 'info',
                                'PENDING' => 'gray',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn (BetStatus $state) => match ($state->value) {
                                'PENDING' => 'Chưa mở',
                                'WON' => 'Thắng đủ',
                                'HALF_WON' => 'Thắng nửa',
                                'LOST' => 'Thua đủ',
                                'HALF_LOST' => 'Thua nửa',
                                'PUSH' => 'Hòa',
                                'VOIDED' => 'Hoàn',
                                'CORRECTED' => 'Điều chỉnh',
                                default => $state->value,
                            }),
                    ])->columns(4),

                Section::make('Chi tiết dự đoán')
                    ->schema([
                        TextEntry::make('market.match.match_code')
                            ->label('Trận đấu')
                            ->state(function (Bet $record) {
                                if (! $record->market || ! $record->market->match) {
                                    return '-';
                                }

                                return "{$record->market->match->match_code} | {$record->market->match->home_team} vs {$record->market->match->away_team}";
                            }),
                        TextEntry::make('market_type_snapshot')->label('Loại kèo'),
                        TextEntry::make('period_type_snapshot')->label('Thời gian'),
                        TextEntry::make('display_odds_snapshot')->label('Lựa chọn (Tỉ lệ)'),
                        TextEntry::make('stake')->label('Tiền cược (Lá)')->numeric(),
                        TextEntry::make('gross_payout')->label('Trả thưởng (Lá)')->numeric(),
                        TextEntry::make('net_result')->label('Lãi / Lỗ')->numeric(),
                    ])->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBets::route('/'),
        ];
    }
}
