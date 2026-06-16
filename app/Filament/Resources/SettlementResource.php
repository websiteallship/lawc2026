<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettlementResource\Pages;
use App\Models\Settlement;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SettlementResource extends Resource
{
    protected static ?string $model = Settlement::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function getModelLabel(): string
    {
        return 'Kết quả mở thưởng';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kết quả mở thưởng';
    }

    public static function getNavigationGroup(): string|\BackedEnum|null
    {
        return 'Quản lý Giao dịch';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['match', 'market', 'executedBy']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Settlement ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('market.name')
                    ->label('Kèo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('match')
                    ->label('Trận đấu')
                    ->state(function (Settlement $record): string {
                        if (! $record->match) {
                            return '-';
                        }

                        return "{$record->match->match_code} | {$record->match->home_team} vs {$record->match->away_team}";
                    }),
                Tables\Columns\TextColumn::make('result_home_score')
                    ->label('Kết quả')
                    ->state(fn (Settlement $record) => "{$record->result_home_score} - {$record->result_away_score}"),
                Tables\Columns\TextColumn::make('executedBy.name')
                    ->label('Người execute')
                    ->default('-'),
                Tables\Columns\TextColumn::make('executed_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_bets')
                    ->label('Số vé')
                    ->numeric(),
                Tables\Columns\TextColumn::make('total_payout')
                    ->label('Tổng trả (Lá)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'EXECUTED' => 'success',
                        'PROCESSING' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin Settlement')
                    ->schema([
                        TextEntry::make('id')->label('ID'),
                        TextEntry::make('market.name')->label('Kèo'),
                        TextEntry::make('result')
                            ->label('Kết quả')
                            ->state(fn (Settlement $record) => "{$record->result_home_score} - {$record->result_away_score}"),
                        TextEntry::make('total_bets')->label('Số vé'),
                        TextEntry::make('total_stake')->label('Tổng cược')->numeric(),
                        TextEntry::make('total_payout')->label('Tổng trả')->numeric(),
                        TextEntry::make('executedBy.name')->label('Người thực hiện'),
                        TextEntry::make('executed_at')->label('Thời gian')->dateTime('d/m/Y H:i'),
                        TextEntry::make('reason')->label('Ghi chú'),
                    ])->columns(3),

                Section::make('Chi tiết từng vé')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('bet.public_code')->label('Mã phiếu'),
                                TextEntry::make('user.name')
                                    ->label('Người chơi')
                                    ->html()
                                    ->state(function ($record) {
                                        $name = $record->user?->name ?? '-';
                                        $time = $record->bet?->created_at?->format('d/m/Y H:i:s') ?? '';
                                        return "{$name}<br><span class='text-xs text-gray-500'>{$time}</span>";
                                    }),
                                TextEntry::make('stake')->label('Cược')->numeric(),
                                TextEntry::make('result_status')
                                    ->label('KQ')
                                    ->badge()
                                    ->color(fn (string $state) => match ($state) {
                                        'WON', 'HALF_WON' => 'success',
                                        'LOST', 'HALF_LOST', 'VOIDED' => 'danger',
                                        'PUSH' => 'info',
                                        default => 'gray',
                                    }),
                                TextEntry::make('gross_payout')->label('Trả thưởng')->numeric(),
                                TextEntry::make('net_result')->label('Lãi/Lỗ')->numeric(),
                            ])->columns(6),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettlements::route('/'),
            'view' => Pages\ViewSettlement::route('/{record}'),
        ];
    }
}
