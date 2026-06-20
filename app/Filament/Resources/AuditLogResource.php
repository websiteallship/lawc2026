<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Builder;

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

    public static function canView($record): bool
    {
        return auth()->user()->can('ViewAny:Activity');
    }

    /**
     * Render diff between old and new values in a readable format.
     */
    protected static function renderDiff(array $properties): string
    {
        $attributes = $properties['attributes'] ?? null;
        $old        = $properties['old'] ?? null;

        if (! $attributes && ! $old) {
            if (empty($properties)) {
                return '-';
            }
            return collect($properties)
                ->map(fn ($v, $k) => "{$k}: " . (is_array($v) ? json_encode($v) : $v))
                ->implode(' | ');
        }

        if ($attributes && $old) {
            $lines = [];
            foreach ($attributes as $key => $newVal) {
                $oldVal = $old[$key] ?? null;
                $newStr = is_array($newVal) ? json_encode($newVal) : $newVal;
                $oldStr = is_array($oldVal) ? json_encode($oldVal) : $oldVal;
                if ($newStr !== $oldStr) {
                    $lines[] = "{$key}: {$oldStr} → {$newStr}";
                }
            }
            return implode(' | ', $lines) ?: '-';
        }

        if ($attributes) {
            return collect($attributes)
                ->map(fn ($v, $k) => "{$k}: " . (is_array($v) ? json_encode($v) : $v))
                ->implode(' | ');
        }

        return '-';
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
                        default   => 'gray',
                    })
                    ->searchable()
                    ->width('90px'),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(fn (?string $state, $record) => $state
                        ? class_basename($state) . ' #' . ($record->subject_id ?? '')
                        : '-')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->width('160px'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Thay đổi')
                    ->formatStateUsing(function ($state, $record) {
                        $props = $record->properties->toArray();
                        return static::renderDiff($props);
                    })
                    ->wrap()
                    ->lineClamp(2)
                    ->searchable()
                    ->grow(),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Người thực hiện')
                    ->default('Hệ thống')
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => $record->causer_id ? 'primary' : 'gray')
                    ->width('130px'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->width('140px'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Sự kiện')
                    ->options([
                        'created' => 'Tạo mới',
                        'updated' => 'Cập nhật',
                        'deleted' => 'Xoá',
                    ]),

                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Log')
                    ->options(
                        Activity::query()
                            ->distinct()
                            ->pluck('log_name', 'log_name')
                            ->filter()
                            ->toArray()
                    )
                    ->searchable(),

                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Loại đối tượng')
                    ->options(
                        Activity::query()
                            ->distinct()
                            ->whereNotNull('subject_type')
                            ->pluck('subject_type', 'subject_type')
                            ->mapWithKeys(fn ($v, $k) => [$k => class_basename($k)])
                            ->toArray()
                    )
                    ->searchable(),

                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('Người thực hiện')
                    ->options(User::pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\Filter::make('system_only')
                    ->label('Chỉ hành động hệ thống')
                    ->query(fn (Builder $q) => $q->whereNull('causer_id'))
                    ->toggle(),

                Tables\Filters\Filter::make('date_range')
                    ->label('Khoảng thời gian')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null)  $indicators[] = Tables\Filters\Indicator::make('Từ: ' . $data['from']);
                        if ($data['until'] ?? null) $indicators[] = Tables\Filters\Indicator::make('Đến: ' . $data['until']);
                        return $indicators;
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100])
            ->recordAction('view')
            ->actions([
                Tables\Actions\ViewAction::make()->label(''),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                InfoSection::make('Thông tin chung')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('event')
                            ->label('Sự kiện')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'info',
                                'deleted' => 'danger',
                                default   => 'gray',
                            }),
                        TextEntry::make('log_name')
                            ->label('Log name'),
                        TextEntry::make('causer.name')
                            ->label('Người thực hiện')
                            ->default('Hệ thống'),
                        TextEntry::make('subject_type')
                            ->label('Loại đối tượng')
                            ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                        TextEntry::make('subject_id')
                            ->label('ID đối tượng')
                            ->default('-'),
                        TextEntry::make('created_at')
                            ->label('Thời gian')
                            ->dateTime('d/m/Y H:i:s'),
                    ]),

                InfoSection::make('Chi tiết thay đổi')
                    ->schema([
                        TextEntry::make('properties_diff')
                            ->label('Diff (old → new)')
                            ->getStateUsing(function ($record) {
                                $props = $record->properties->toArray();
                                $old   = $props['old'] ?? null;
                                $new   = $props['attributes'] ?? null;

                                if (! $new && ! $old) {
                                    return json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                }

                                $lines = [];
                                foreach ((array)$new as $key => $newVal) {
                                    $oldVal = $old[$key] ?? null;
                                    $newStr = is_array($newVal) ? json_encode($newVal, JSON_UNESCAPED_UNICODE) : $newVal;
                                    $oldStr = is_array($oldVal) ? json_encode($oldVal, JSON_UNESCAPED_UNICODE) : $oldVal;
                                    $changed = $newStr !== $oldStr;
                                    $lines[] = ($old !== null)
                                        ? ($changed ? "[changed] {$key}: {$oldStr} → {$newStr}" : "[same]    {$key}: {$newStr}")
                                        : "          {$key}: {$newStr}";
                                }

                                return implode(PHP_EOL, $lines);
                            })
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view'  => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
