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
                    ->color(fn (int $state): string => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? '+'.number_format($state) : number_format($state)),
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
                    ->searchable(),
            ])
            ->filters([])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\WalletLedgerExporter::class),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
