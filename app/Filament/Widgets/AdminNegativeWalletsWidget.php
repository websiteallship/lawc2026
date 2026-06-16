<?php

namespace App\Filament\Widgets;

use App\Models\Wallet;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class AdminNegativeWalletsWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Cảnh báo Ví Âm (Cần xử lý)')
            ->query(
                Wallet::query()
                    ->with(['user', 'season'])
                    ->where('available_balance', '<', 0)
                    ->orderBy('available_balance', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Người chơi')
                    ->searchable()
                    ->description(fn (Wallet $record): string => $record->user?->email ?? ''),
                Tables\Columns\TextColumn::make('season.name')
                    ->label('Mùa giải'),
                Tables\Columns\TextColumn::make('available_balance')
                    ->label('Số dư khả dụng')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => number_format($state) . ' lá'),
                Tables\Columns\TextColumn::make('locked_balance')
                    ->label('Số dư khóa')
                    ->formatStateUsing(fn ($state) => number_format($state) . ' lá'),
            ])
            ->actions([
                \Filament\Actions\Action::make('view_ledgers')
                    ->label('Lịch sử')
                    ->url(fn (Wallet $record): string => \App\Filament\Resources\WalletLedgerResource::getUrl('index', ['tableFilters' => ['wallet_id' => ['value' => $record->id]]]))
                    ->icon('heroicon-m-clock'),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
