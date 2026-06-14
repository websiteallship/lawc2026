<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Spatie\Activitylog\Models\Activity;

class AdminRecentAuditLogsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Nhật ký hệ thống gần đây';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->label('Sự kiện')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Mô tả')
                    ->limit(60),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Người thực hiện')
                    ->default('Hệ thống'),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
