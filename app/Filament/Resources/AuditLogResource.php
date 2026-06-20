<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\Bet;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\Settlement;
use App\Models\User;
use App\Models\WalletLedger;
use Filament\Actions\ViewAction;
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

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function canView($record): bool
    {
        return auth()->user()->can('ViewAny:Activity');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    protected static function renderDiff(array $properties): string
    {
        $attributes = $properties['attributes'] ?? null;
        $old        = $properties['old'] ?? null;

        if (! $attributes && ! $old) {
            return empty($properties) ? '-'
                : collect($properties)->map(fn ($v, $k) => "{$k}: " . (is_array($v) ? json_encode($v) : $v))->implode(' | ');
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
            return collect($attributes)->map(fn ($v, $k) => "{$k}: " . (is_array($v) ? json_encode($v) : $v))->implode(' | ');
        }

        return '-';
    }

    /**
     * Load subject model and return array of human-readable context rows.
     */
    protected static function resolveContext(Activity $record): array
    {
        $type = $record->subject_type;
        $id   = $record->subject_id;

        if (! $type || ! $id) {
            return [];
        }

        try {
            return match (class_basename($type)) {
                'MarketOutcome' => static::contextMarketOutcome($id),
                'Settlement'    => static::contextSettlement($id),
                'Bet'           => static::contextBet($id),
                'Market'        => static::contextMarket($id),
                'WalletLedger'  => static::contextWalletLedger($id),
                'FootballMatch' => static::contextFootballMatch($id),
                default         => [],
            };
        } catch (\Throwable) {
            return [];
        }
    }

    private static function matchLabel(?FootballMatch $match): string
    {
        if (! $match) return 'N/A';
        $score = ($match->home_score !== null && $match->away_score !== null)
            ? " ({$match->home_score} - {$match->away_score})"
            : '';
        return "{$match->home_team} vs {$match->away_team}{$score}";
    }

    private static function contextMarketOutcome(int $id): array
    {
        $outcome = MarketOutcome::with(['market.match'])->find($id);
        if (! $outcome) {
            return [['label' => 'Trạng thái', 'value' => 'Không tìm thấy MarketOutcome #' . $id]];
        }
        $market = $outcome->market;
        $match  = $market?->match;

        return [
            ['label' => 'Trận đấu',       'value' => static::matchLabel($match)],
            ['label' => 'Loại kèo',       'value' => ($market?->market_type ?? '-') . ' | ' . ($market?->period_type ?? '-')],
            ['label' => 'Kèo (label)',     'value' => $outcome->label ?? '-'],
            ['label' => 'Tỉ lệ hiện tại', 'value' => 'ăn ' . $outcome->profit_rate],
            ['label' => 'Trạng thái',      'value' => $outcome->status ?? '-'],
        ];
    }

    private static function contextSettlement(int $id): array
    {
        $s = Settlement::with(['match', 'market'])->find($id);
        if (! $s) {
            return [['label' => 'Trạng thái', 'value' => 'Không tìm thấy Settlement #' . $id]];
        }

        return [
            ['label' => 'Trận đấu',    'value' => static::matchLabel($s->match)],
            ['label' => 'Tỉ số KQ',    'value' => ($s->result_home_score ?? '?') . ' - ' . ($s->result_away_score ?? '?')],
            ['label' => 'Loại kèo',    'value' => ($s->market?->market_type ?? '-') . ' | ' . ($s->period_type ?? '-')],
            ['label' => 'Tổng phiếu',  'value' => number_format((int)$s->total_bets) . ' phiếu'],
            ['label' => 'Tổng cược',   'value' => number_format((int)$s->total_stake) . ' lá'],
            ['label' => 'Tổng trả',    'value' => number_format((int)$s->total_payout) . ' lá'],
            ['label' => 'Trạng thái',  'value' => $s->status ?? '-'],
            ['label' => 'Lý do',       'value' => $s->reason ?? '-'],
        ];
    }

    private static function contextBet(int $id): array
    {
        $bet = Bet::with(['match', 'market', 'outcome'])->find($id);
        if (! $bet) {
            return [['label' => 'Trạng thái', 'value' => 'Không tìm thấy Bet #' . $id]];
        }

        $playerLabel = 'Player #' . $bet->user_id;
        $statusVal   = $bet->status instanceof \BackedEnum ? $bet->status->value : (string)$bet->status;

        $result = match ($statusVal) {
            'WON'       => 'Thắng',
            'LOST'      => 'Thua',
            'PUSH'      => 'Hoà vốn',
            'HALF_WON'  => 'Thắng nửa',
            'HALF_LOST' => 'Thua nửa',
            'VOIDED'    => 'Huỷ',
            'PENDING'   => 'Chờ kết quả',
            default     => $statusVal,
        };

        return [
            ['label' => 'Người chơi',   'value' => $playerLabel],
            ['label' => 'Trận đấu',     'value' => static::matchLabel($bet->match)],
            ['label' => 'Kèo',          'value' => $bet->display_odds_snapshot ?? $bet->label_snapshot ?? '-'],
            ['label' => 'Số lá cược',   'value' => number_format((int)$bet->stake) . ' lá'],
            ['label' => 'Kết quả',      'value' => $result],
            ['label' => 'Trả về',       'value' => $bet->gross_payout !== null ? number_format((int)$bet->gross_payout) . ' lá' : '-'],
            ['label' => 'Lãi/lỗ',       'value' => $bet->net_result !== null ? number_format((int)$bet->net_result) . ' lá' : '-'],
        ];
    }

    private static function contextMarket(int $id): array
    {
        $market = Market::with(['match'])->find($id);
        if (! $market) {
            return [['label' => 'Trạng thái', 'value' => 'Không tìm thấy Market #' . $id]];
        }

        return [
            ['label' => 'Trận đấu',   'value' => static::matchLabel($market->match)],
            ['label' => 'Loại kèo',   'value' => $market->market_type . ' | ' . $market->period_type],
            ['label' => 'Tên kèo',    'value' => $market->name ?? '-'],
            ['label' => 'Trạng thái', 'value' => $market->status ?? '-'],
            ['label' => 'Đóng lúc',   'value' => $market->close_at?->format('d/m/Y H:i') ?? '-'],
        ];
    }

    private static function contextWalletLedger(int $id): array
    {
        $ledger = WalletLedger::find($id);
        if (! $ledger) {
            return [['label' => 'Trạng thái', 'value' => 'Không tìm thấy WalletLedger #' . $id]];
        }

        $playerLabel = 'Player #' . $ledger->user_id;
        $amount      = (($ledger->amount ?? 0) >= 0 ? '+' : '') . number_format((int)($ledger->amount ?? 0)) . ' lá';

        return [
            ['label' => 'Người chơi',     'value' => $playerLabel],
            ['label' => 'Loại GD',        'value' => $ledger->type ?? '-'],
            ['label' => 'Thay đổi',       'value' => $amount],
            ['label' => 'Số dư trước',    'value' => number_format((int)($ledger->balance_before ?? 0)) . ' lá'],
            ['label' => 'Số dư sau',      'value' => number_format((int)($ledger->balance_after ?? 0)) . ' lá'],
            ['label' => 'Ghi chú',        'value' => $ledger->description ?? $ledger->note ?? '-'],
        ];
    }

    private static function contextFootballMatch(int $id): array
    {
        $match = FootballMatch::find($id);
        if (! $match) {
            return [['label' => 'Trạng thái', 'value' => 'Không tìm thấy Match #' . $id]];
        }

        return [
            ['label' => 'Trận đấu',   'value' => static::matchLabel($match)],
            ['label' => 'Kickoff',    'value' => $match->kickoff_at?->format('d/m/Y H:i') ?? '-'],
            ['label' => 'Trạng thái', 'value' => $match->status ?? '-'],
            ['label' => 'Vòng đấu',  'value' => ($match->stage ?? '') . ($match->group ? ' | ' . $match->group : '')],
        ];
    }

    // ─── Table ────────────────────────────────────────────────────────────────

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
                        Activity::query()->distinct()->pluck('log_name', 'log_name')->filter()->toArray()
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
                ViewAction::make()->label(''),
            ])
            ->bulkActions([]);
    }

    // ─── Infolist ─────────────────────────────────────────────────────────────

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                // Section 1: General info
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

                // Section 2: Rich context
                InfoSection::make('Ngữ cảnh')
                    ->description('Thông tin liên quan đến đối tượng bị thay đổi')
                    ->schema([
                        TextEntry::make('_context')
                            ->label('')
                            ->getStateUsing(function (Activity $record): string {
                                $rows = static::resolveContext($record);
                                if (empty($rows)) {
                                    return 'Không có ngữ cảnh cho loại đối tượng này.';
                                }
                                return collect($rows)
                                    ->map(fn ($r) => str_pad($r['label'], 20) . ': ' . $r['value'])
                                    ->implode(PHP_EOL);
                            })
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ]),

                // Section 3: Diff detail
                InfoSection::make('Chi tiết thay đổi')
                    ->schema([
                        TextEntry::make('properties_diff')
                            ->label('Diff (old → new)')
                            ->getStateUsing(function (Activity $record): string {
                                $props = $record->properties->toArray();
                                $old   = $props['old'] ?? null;
                                $new   = $props['attributes'] ?? null;

                                if (! $new && ! $old) {
                                    return json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                }

                                $lines = [];
                                foreach ((array)$new as $key => $newVal) {
                                    $oldVal  = $old[$key] ?? null;
                                    $newStr  = is_array($newVal) ? json_encode($newVal, JSON_UNESCAPED_UNICODE) : $newVal;
                                    $oldStr  = is_array($oldVal) ? json_encode($oldVal, JSON_UNESCAPED_UNICODE) : $oldVal;
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
