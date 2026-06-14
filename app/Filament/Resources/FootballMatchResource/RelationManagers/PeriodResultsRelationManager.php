<?php

namespace App\Filament\Resources\FootballMatchResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PeriodResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'periodResults';

    protected static ?string $title = 'Tổng hợp tỉ số (API)';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('period_type')
                    ->label('Hiệp đấu')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'FIRST_HALF' => 'Hết hiệp 1',
                        'SECOND_HALF' => 'Hiệp 2',
                        'FULL_TIME' => 'Cả trận',
                        'EXTRA_TIME' => 'Hiệp phụ',
                        'PENALTY' => 'Luân lưu',
                        default => $state,
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('home_score')
                    ->label('Đội nhà')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('away_score')
                    ->label('Đội khách')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('id', 'asc');
    }
}
