<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class AuditLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-shield-check';
    }

    public static function getModelLabel(): string
    {
        return 'Nhật ký hệ thống';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Nhật ký hệ thống';
    }

    public static function getNavigationGroup(): string|\BackedEnum|null
    {
        return 'Hệ thống';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('ViewAny:Activity');
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
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->label('Sự kiện')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Mô tả')
                    ->formatStateUsing(function (?string $state, $record) {
                        if ($state === $record->event) {
                            $props = $record->properties->toArray();
                            if (empty($props)) {
                                return $state;
                            }

                            return collect($props)->map(fn ($v, $k) => "{$k}: ".(is_array($v) ? json_encode($v) : $v))->implode(' | ');
                        }

                        return $state;
                    })
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Người thực hiện')
                    ->default('Hệ thống')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(fn (?string $state, $record) => $state
                        ? class_basename($state).' #'.($record->subject_id ?? '')
                        : '-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Thuộc tính')
                    ->formatStateUsing(function ($record) {
                        $props = $record->properties->toArray();
                        if (empty($props)) {
                            return '-';
                        }
                        $out = collect($props)->map(fn ($v, $k) => "{$k}: ".(is_array($v) ? json_encode($v) : $v))->take(3)->implode(' | ');

                        return $out;
                    })
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Sự kiện')
                    ->options([
                        'created' => 'Tạo mới',
                        'updated' => 'Cập nhật',
                        'deleted' => 'Xoá',
                    ]),
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('Người thực hiện')
                    ->options(User::pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\Filter::make('date_range')
                    ->label('Khoảng thời gian')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
