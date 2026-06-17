<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletLedgersRelationManager extends RelationManager
{
    protected static string $relationship = 'walletLedgers';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Lịch sử giao dịch Lá (Ledger)')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Loại GD')
                    ->badge()
                    ->searchable(),
                TextColumn::make('amount_available')
                    ->label('Biến động')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state): string => (int)$state > 0 ? 'success' : ((int)$state < 0 ? 'danger' : 'gray'))
                    ->formatStateUsing(fn ($state): string => (int)$state > 0 ? '+'.number_format((int)$state) : number_format((int)$state)),
                TextColumn::make('balance_available_after')
                    ->label('Số dư sau GD')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Ghi chú')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('actor.name')
                    ->label('Người thực hiện')
                    ->state(function ($record) {
                        if ($record->actor) {
                            return $record->actor->name;
                        }
                        
                        if ($record->type?->value === 'BET_PLACED') {
                            return 'Người chơi';
                        }
                        
                        return 'Hệ thống';
                    })
                    ->badge()
                    ->color(function ($record) {
                        if ($record->actor) {
                            return 'warning';
                        }
                        if ($record->type?->value === 'BET_PLACED') {
                            return 'info';
                        }
                        return 'gray';
                    })
                    ->searchable(),
            ])
            ->filters([])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\WalletLedgerExporter::class),
            ])
            ->actions([
                \Filament\Actions\Action::make('viewBet')
                    ->label('Xem phiếu')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->visible(fn ($record) => $record->bet_id !== null)
                    ->modalHeading('Chi tiết phiếu dự đoán')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->modalContent(function ($record) {
                        $bet = \App\Models\Bet::with('market.match')->find($record->bet_id);
                        if (! $bet) {
                            return null;
                        }
                        return view('filament.player.components.bet-card', ['bet' => $bet, 'isAdmin' => true]);
                    }),
            ])
            ->bulkActions([]);
    }
}
