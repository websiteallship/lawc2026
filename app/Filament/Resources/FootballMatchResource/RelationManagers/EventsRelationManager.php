<?php

namespace App\Filament\Resources\FootballMatchResource\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Read-only, no form needed
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('minute')
            ->columns([
                TextColumn::make('minute')
                    ->label('Phút')
                    ->formatStateUsing(fn ($record) => $record->minute . '\'' . ($record->injury_time ? ' +' . $record->injury_time : ''))
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Loại sự kiện')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'GOAL' => 'success',
                        'CARD' => 'danger',
                        'SUB' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('team_type')
                    ->label('Đội')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'HOME' => 'primary',
                        'AWAY' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('player_name')
                    ->label('Cầu thủ chính')
                    ->searchable(),
                TextColumn::make('related_player_name')
                    ->label('Cầu thủ liên quan (Kiến tạo/Ra sân)')
                    ->searchable(),
                TextColumn::make('detail')
                    ->label('Chi tiết (Thẻ/Pen/OG)')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('syncApi')
                    ->label('Đồng bộ từ API')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function ($livewire) {
                        $match = $livewire->getOwnerRecord();
                        $syncService = app(\App\Domain\Match\Services\MatchSyncService::class);
                        $syncService->syncMatchById($match);
                        \Filament\Notifications\Notification::make()->title('Đã đồng bộ sự kiện trận đấu')->success()->send();
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('minute', 'desc');
    }
}
