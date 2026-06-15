<?php

namespace App\Filament\Resources;

use App\Domain\Market\Services\MarketLockService;
use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Services\SettlementEngine;
use App\Enums\MarketStatus;
use App\Filament\Resources\MarketResource\Pages;
use App\Filament\Resources\MarketResource\RelationManagers;
use App\Helpers\CountryFlagHelper;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MatchPeriodResult;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class MarketResource extends Resource
{
    protected static ?string $model = Market::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Kèo dự đoán';
    }

    public static function getModelLabel(): string
    {
        return 'Kèo';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kèo dự đoán';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Quản lý trận đấu';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('match_id')
                ->label('Trận đấu')
                ->allowHtml()
                ->options(
                    FootballMatch::get()
                        ->mapWithKeys(function ($m) {
                            $home = CountryFlagHelper::renderHtml($m->home_team);
                            $away = CountryFlagHelper::renderHtml($m->away_team);

                            $homeStyled = str_replace(
                                ['<svg', '<span'],
                                [
                                    '<svg style="width: 24px !important; height: 16px !important; display: inline-block !important; vertical-align: middle !important; margin-right: 6px !important; border-radius: 2px !important; box-shadow: 0 1px 2px rgba(0,0,0,0.15) !important; object-fit: cover !important; float: none !important;"',
                                    '<span style="vertical-align: middle !important; font-weight: 500 !important; font-size: 14px !important;"',
                                ],
                                $home
                            );

                            $awayStyled = str_replace(
                                ['<svg', '<span'],
                                [
                                    '<svg style="width: 24px !important; height: 16px !important; display: inline-block !important; vertical-align: middle !important; margin-right: 6px !important; border-radius: 2px !important; box-shadow: 0 1px 2px rgba(0,0,0,0.15) !important; object-fit: cover !important; float: none !important;"',
                                    '<span style="vertical-align: middle !important; font-weight: 500 !important; font-size: 14px !important;"',
                                ],
                                $away
                            );

                            $optionHtml = "<div style='display: inline-flex; align-items: center; gap: 8px; flex-wrap: nowrap; padding: 2px 0;'>"
                                ."<span style='font-family: monospace; font-weight: bold; color: #4b5563; background-color: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 11px;'>{$m->match_code}</span>"
                                ." {$homeStyled} "
                                ."<span style='color: #9ca3af; font-size: 12px; font-weight: bold; margin: 0 4px;'>vs</span>"
                                ." {$awayStyled}"
                                .'</div>';

                            return [$m->id => $optionHtml];
                        })
                )
                ->disableOptionWhen(function (string $value): bool {
                    static $finishedMatchIds = null;
                    if ($finishedMatchIds === null) {
                        $finishedMatchIds = FootballMatch::where('status', 'FINISHED')->pluck('id')->toArray();
                    }

                    return in_array($value, $finishedMatchIds);
                })
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($set, ?string $state) {
                    if ($state) {
                        $match = FootballMatch::find($state);
                        if ($match && $match->kickoff_at) {
                            $set('close_at', $match->kickoff_at->format('Y-m-d H:i:s'));
                            $set('open_at', now()->format('Y-m-d H:i:s'));
                        }
                    }
                }),

            Forms\Components\Select::make('period_type')
                ->label('Hiệp')
                ->options([
                    'FULL_TIME' => 'Cả trận',
                    'FIRST_HALF' => 'Hiệp 1',
                    'SECOND_HALF' => 'Hiệp 2',
                    'EXTRA_TIME' => 'Hiệp phụ',
                    'PENALTY' => 'Loạt penalty',
                ])
                ->default('FULL_TIME')
                ->required()
                ->live(),

            Forms\Components\Select::make('market_type')
                ->label('Loại kèo')
                ->options([
                    'EXACT_SCORE' => 'Tỉ số chính xác',
                    'ASIAN_HANDICAP' => 'Kèo chấp (AH)',
                    'OVER_UNDER' => 'Tài/Xỉu (O/U)',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(function ($get, $set, ?string $state) {
                    $periodMap = [
                        'FULL_TIME' => 'Cả trận',
                        'FIRST_HALF' => 'Hiệp 1',
                        'SECOND_HALF' => 'Hiệp 2',
                        'EXTRA_TIME' => 'Hiệp phụ',
                        'PENALTY' => 'Penalty',
                    ];
                    $period = $periodMap[$get('period_type')] ?? 'Khác';

                    if ($state === 'ASIAN_HANDICAP') {
                        $set('name', 'Kèo chấp (AH) - '.$period);
                        $set('outcomes', [
                            ['label' => 'Đội Nhà', 'selection_side' => 'HOME', 'line_value' => null, 'profit_rate' => 0.90, 'display_order' => 1],
                            ['label' => 'Đội Khách', 'selection_side' => 'AWAY', 'line_value' => null, 'profit_rate' => 0.90, 'display_order' => 2],
                        ]);
                    } elseif ($state === 'OVER_UNDER') {
                        $set('name', 'Tài/Xỉu (O/U) - '.$period);
                        $set('outcomes', [
                            ['label' => 'Tài', 'selection_side' => 'OVER', 'line_value' => null, 'profit_rate' => 0.90, 'display_order' => 1],
                            ['label' => 'Xỉu', 'selection_side' => 'UNDER', 'line_value' => null, 'profit_rate' => 0.90, 'display_order' => 2],
                        ]);
                    } elseif ($state === 'EXACT_SCORE') {
                        $set('name', 'Tỉ số chính xác - '.$period);
                        // Chỉ generate nếu chưa có (để không bị mất rate khi edit)
                        // Tuy nhiên vì Exact Score bị lock hoàn toàn cấu trúc, việc reset cũng khá ổn nếu user cố tình switch qua lại.
                        // Nhưng để an toàn khi edit, ta check if current có vẻ không phải là EXACT SCORE
                        $currentOutcomes = $get('outcomes') ?? [];
                        $isAlreadyExactScore = count($currentOutcomes) > 20; // Exact score có 26 items

                        if (! $isAlreadyExactScore) {
                            $scores = [
                                [1, 0], [2, 0], [2, 1], [3, 0], [3, 1], [3, 2], [4, 0], [4, 1], [4, 2], [4, 3],
                                [0, 0], [1, 1], [2, 2], [3, 3], [4, 4],
                                [0, 1], [0, 2], [1, 2], [0, 3], [1, 3], [2, 3], [0, 4], [1, 4], [2, 4], [3, 4],
                            ];
                            $outcomes = [];
                            $order = 1;
                            foreach ($scores as $s) {
                                $outcomes[] = [
                                    'label' => "{$s[0]}-{$s[1]}",
                                    'selection_side' => null,
                                    'line_value' => null,
                                    'profit_rate' => 0,
                                    'score_home' => $s[0],
                                    'score_away' => $s[1],
                                    'display_order' => $order++,
                                ];
                            }
                            $outcomes[] = [
                                'label' => 'Tỉ số khác',
                                'selection_side' => 'OTHER',
                                'line_value' => null,
                                'profit_rate' => 0,
                                'score_home' => null,
                                'score_away' => null,
                                'display_order' => $order++,
                            ];
                            $set('outcomes', $outcomes);
                        }
                    }
                }),

            Forms\Components\TextInput::make('name')
                ->label('Tên hiển thị')
                ->required()
                ->maxLength(255),

            Forms\Components\DateTimePicker::make('open_at')
                ->label('Giờ mở')
                ->required()
                ->timezone('Asia/Ho_Chi_Minh')
                ->displayFormat('d/m/Y H:i'),

            Forms\Components\DateTimePicker::make('close_at')
                ->label('Giờ đóng')
                ->required()
                ->timezone('Asia/Ho_Chi_Minh')
                ->displayFormat('d/m/Y H:i'),

            Forms\Components\TextInput::make('display_order')
                ->label('Thứ tự')
                ->numeric()
                ->default(0),

            Fieldset::make('Cấu hình Kèo (Chấp / Tài Xỉu)')
                ->schema([
                    Forms\Components\Select::make('favorite_side')
                        ->label('Đội Kèo Trên (Chấp)')
                        ->options([
                            'HOME' => 'Đội Nhà',
                            'AWAY' => 'Đội Khách',
                        ])
                        ->hidden(fn ($get) => $get('market_type') !== 'ASIAN_HANDICAP')
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $line = abs((float) $get('market_line'));
                            $outcomes = $get('outcomes') ?? [];
                            foreach ($outcomes as $key => $outcome) {
                                if (($outcome['selection_side'] ?? '') === $state) {
                                    $outcomes[$key]['line_value'] = -$line;
                                } else {
                                    $outcomes[$key]['line_value'] = $line;
                                }
                            }
                            $set('outcomes', $outcomes);
                        })
                        ->afterStateHydrated(function ($component, $get) {
                            if ($get('market_type') === 'ASIAN_HANDICAP') {
                                $outcomes = $get('outcomes') ?? [];
                                foreach ($outcomes as $outcome) {
                                    if (($outcome['line_value'] ?? 0) < 0) {
                                        $component->state($outcome['selection_side']);
                                        break;
                                    }
                                }
                            }
                        }),
                    Forms\Components\TextInput::make('market_line')
                        ->label('Mốc Line (Ví dụ: 0.5, 2.5)')
                        ->numeric()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $type = $get('market_type');
                            $outcomes = $get('outcomes') ?? [];
                            $line = abs((float) $state);

                            if ($type === 'ASIAN_HANDICAP') {
                                $fav = $get('favorite_side');
                                foreach ($outcomes as $key => $outcome) {
                                    if (($outcome['selection_side'] ?? '') === $fav) {
                                        $outcomes[$key]['line_value'] = -$line;
                                    } else {
                                        $outcomes[$key]['line_value'] = $line;
                                    }
                                }
                            } elseif ($type === 'OVER_UNDER') {
                                foreach ($outcomes as $key => $outcome) {
                                    $outcomes[$key]['line_value'] = $line;
                                }
                            }
                            $set('outcomes', $outcomes);
                        })
                        ->afterStateHydrated(function ($component, $get) {
                            if (in_array($get('market_type'), ['ASIAN_HANDICAP', 'OVER_UNDER'])) {
                                $outcomes = $get('outcomes') ?? [];
                                if (! empty($outcomes)) {
                                    $val = $outcomes[array_key_first($outcomes)]['line_value'] ?? null;
                                    if ($val !== null) {
                                        $component->state(abs((float) $val));
                                    }
                                }
                            }
                        }),
                ])
                ->columns(2)
                ->dehydrated(false)
                ->hidden(fn ($get) => ! in_array($get('market_type'), ['ASIAN_HANDICAP', 'OVER_UNDER'])),

            Forms\Components\Repeater::make('outcomes')
                ->relationship()
                ->label('Tỷ lệ ăn (Outcomes)')
                ->grid(fn ($get) => $get('market_type') === 'EXACT_SCORE' ? 3 : 1)
                ->addable(false)
                ->deletable(false)
                ->schema([
                    Forms\Components\TextInput::make('label')
                        ->label('Nhãn')
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(2),
                    Forms\Components\Hidden::make('selection_side'),
                    Forms\Components\Hidden::make('line_value'),
                    Forms\Components\Hidden::make('score_home'),
                    Forms\Components\Hidden::make('score_away'),
                    Forms\Components\Hidden::make('display_order'),
                    Forms\Components\TextInput::make('profit_rate')
                        ->label('Tỷ lệ ăn')
                        ->required()
                        ->numeric()
                        ->step(0.0001)
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpanFull()
                ->defaultItems(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->orderByRaw("CASE WHEN status = 'OPEN' THEN 1 WHEN status = 'LOCKED' THEN 2 ELSE 3 END"))
            ->columns([
                Tables\Columns\TextColumn::make('match.match_code')
                    ->label('Trận')
                    ->sortable()
                    ->searchable()
                    ->html()
                    ->formatStateUsing(function ($state, Market $record) {
                        $home = CountryFlagHelper::renderHtml($record->match->home_team);
                        $away = CountryFlagHelper::renderHtml($record->match->away_team);

                        return "<b>{$record->match->match_code}</b><br><span class='text-xs'>{$home} vs {$away}</span>";
                    }),
                Tables\Columns\TextColumn::make('name')->label('Tên kèo')->searchable(),
                Tables\Columns\TextColumn::make('market_type')->label('Loại'),
                Tables\Columns\TextColumn::make('period_type')->label('Hiệp'),
                Tables\Columns\TextColumn::make('close_at')
                    ->label('Đóng lúc')
                    ->dateTime('d/m H:i')
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'OPEN' => 'success',
                        'LOCKED' => 'warning',
                        'SETTLED' => 'info',
                        'VOIDED' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'DRAFT' => 'Nháp',
                        'OPEN' => 'Đang mở',
                        'LOCKED' => 'Đã khóa',
                        'SETTLED' => 'Đã settle',
                        'VOIDED' => 'Hủy',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('bets_count')
                    ->label('Số vé')
                    ->counts('bets'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime('d/m/Y H:i:s')
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m/Y H:i:s')
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'DRAFT' => 'Nháp',
                        'OPEN' => 'Đang mở',
                        'LOCKED' => 'Đã khóa',
                        'SETTLED' => 'Đã settle',
                    ]),
                Tables\Filters\SelectFilter::make('market_type')
                    ->label('Loại kèo')
                    ->options([
                        'EXACT_SCORE' => 'Tỉ số',
                        'ASIAN_HANDICAP' => 'Kèo chấp',
                        'OVER_UNDER' => 'Tài/Xỉu',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EditAction::make(),

                // ---- Action: Nhập kết quả ----
                Action::make('nhap_ket_qua')
                    ->label('Nhập kết quả')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->visible(fn (Market $record) => in_array($record->status, ['OPEN', 'LOCKED', 'SETTLING', 'SETTLED']))
                    ->fillForm(function (Market $record): array {
                        $existing = MatchPeriodResult::where('match_id', $record->match_id)
                            ->where('period_type', $record->period_type)
                            ->first();

                        return [
                            'period_type' => $record->period_type,
                            'home_score' => $existing?->home_score,
                            'away_score' => $existing?->away_score,
                        ];
                    })
                    ->form([
                        Forms\Components\Select::make('period_type')
                            ->label('Hiệp')
                            ->options([
                                'FULL_TIME' => 'Cả trận',
                                'FIRST_HALF' => 'Hiệp 1',
                                'SECOND_HALF' => 'Hiệp 2',
                                'EXTRA_TIME' => 'Hiệp phụ',
                                'PENALTY' => 'Luân lưu',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('home_score')
                            ->label('Bàn thắng đội nhà')
                            ->numeric()->required()->minValue(0),
                        Forms\Components\TextInput::make('away_score')
                            ->label('Bàn thắng đội khách')
                            ->numeric()->required()->minValue(0),
                    ])
                    ->action(function (Market $record, array $data): void {
                        MatchPeriodResult::updateOrCreate(
                            ['match_id' => $record->match_id, 'period_type' => $data['period_type']],
                            [
                                'home_score' => $data['home_score'],
                                'away_score' => $data['away_score'],
                                'status' => 'CONFIRMED',
                                'entered_by' => auth()->id(),
                            ]
                        );
                        Notification::make()->title('Đã lưu kết quả')->success()->send();
                    }),

                // ---- Action: Preview Settlement ----
                Action::make('preview_settlement')
                    ->label('Preview Settlement')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->visible(fn (Market $record) => in_array($record->status, ['LOCKED', 'SETTLING']))
                    ->action(function (Market $record) {
                        $periodResult = MatchPeriodResult::where('match_id', $record->match_id)
                            ->where('period_type', $record->period_type)
                            ->first();

                        if (! $periodResult) {
                            Notification::make()->title('Chưa có kết quả trận đấu. Hãy nhập kết quả trước.')->danger()->send();

                            return;
                        }

                        $matchResult = new MatchResult($periodResult->home_score, $periodResult->away_score);
                        $previews = app(SettlementEngine::class)->preview($record, $matchResult);

                        $summary = collect($previews)->groupBy(fn ($p) => $p['result']->status->value)->map(fn ($group) => [
                            'count' => $group->count(),
                            'stake' => $group->sum(fn ($p) => $p['bet']->stake),
                            'payout' => $group->sum(fn ($p) => $p['result']->grossPayout),
                        ]);

                        $html = "<p class='mb-2 font-bold'>Kết quả: {$periodResult->home_score} - {$periodResult->away_score} | Tổng vé: ".count($previews).'</p>';
                        $html .= "<table class='w-full text-sm'><tr class='font-bold'><td>Trạng thái</td><td>Số vé</td><td>Tổng cược</td><td>Tổng trả</td></tr>";
                        foreach ($summary as $status => $data) {
                            $html .= "<tr><td>{$status}</td><td>{$data['count']}</td><td>".number_format($data['stake']).'</td><td>'.number_format($data['payout']).'</td></tr>';
                        }
                        $html .= '</table>';

                        Notification::make()->title('Preview Settlement')->body($html)->info()->persistent()->send();
                    }),

                // ---- Action: Execute Settlement ----
                Action::make('execute_settlement')
                    ->label('Execute Settlement')
                    ->icon('heroicon-o-bolt')
                    ->color('danger')
                    ->visible(fn (Market $record) => in_array($record->status, ['LOCKED', 'SETTLING']))
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận Execute Settlement')
                    ->modalDescription('Hành động này sẽ mở thưởng TẤT CẢ phiếu dự đoán trong kèo này. Thao tác KHÔNG thể hoàn tác.')
                    ->modalSubmitActionLabel('Đồng ý, Execute Settlement')
                    ->form([
                        Forms\Components\TextInput::make('confirm_text')
                            ->label('Gõ "EXECUTE" để xác nhận')
                            ->required()
                            ->rules(['in:EXECUTE']),
                        Forms\Components\Textarea::make('reason')
                            ->label('Ghi chú (tùy chọn)')
                            ->rows(2),
                    ])
                    ->action(function (Market $record, array $data) {
                        $periodResult = MatchPeriodResult::where('match_id', $record->match_id)
                            ->where('period_type', $record->period_type)
                            ->first();

                        if (! $periodResult) {
                            Notification::make()->title('Chưa có kết quả trận đấu. Hãy nhập kết quả trước.')->danger()->send();

                            return;
                        }

                        try {
                            $matchResult = new MatchResult($periodResult->home_score, $periodResult->away_score);
                            $settlement = app(SettlementEngine::class)->execute(
                                $record,
                                $matchResult,
                                auth()->user(),
                                $data['reason'] ?? ''
                            );
                            Notification::make()
                                ->title("Settlement #{$settlement->id} hoàn tất!")
                                ->body("Đã mở thưởng {$settlement->total_bets} vé. Tổng trả: ".number_format($settlement->total_payout).' lá.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Lỗi: '.$e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('toggle_status')
                    ->label(fn (Market $record) => $record->status === 'OPEN' ? 'Đóng kèo' : 'Mở kèo (Publish)')
                    ->icon(fn (Market $record) => $record->status === 'OPEN' ? 'heroicon-o-lock-closed' : 'heroicon-o-arrow-up-circle')
                    ->color(fn (Market $record) => $record->status === 'OPEN' ? 'warning' : 'success')
                    ->visible(fn (Market $record) => in_array($record->status, ['DRAFT', 'OPEN', 'LOCKED']))
                    ->requiresConfirmation()
                    ->action(function (Market $record) {
                        try {
                            $newStatus = $record->status === 'OPEN' ? MarketStatus::LOCKED : MarketStatus::OPEN;
                            app(MarketLockService::class)->transition($record, $newStatus);
                            Notification::make()->title('Cập nhật trạng thái thành công')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Market $record) => in_array($record->status, ['OPEN', 'LOCKED']))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Lý do void')
                            ->required(),
                    ])
                    ->action(function (Market $record, array $data) {
                        try {
                            app(MarketLockService::class)->transition($record, MarketStatus::VOIDED, $data['reason']);
                            Notification::make()->title('Market đã void')->warning()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_open')
                        ->label('Mở kèo (Publish)')
                        ->icon('heroicon-o-arrow-up-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (in_array($record->status, ['DRAFT', 'LOCKED'])) {
                                    app(MarketLockService::class)->transition($record, MarketStatus::OPEN);
                                    $count++;
                                }
                            }
                            Notification::make()->title("Đã mở {$count} kèo")->success()->send();
                        }),
                    BulkAction::make('bulk_lock')
                        ->label('Đóng kèo (Lock)')
                        ->icon('heroicon-o-lock-closed')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'OPEN') {
                                    app(MarketLockService::class)->transition($record, MarketStatus::LOCKED);
                                    $count++;
                                }
                            }
                            Notification::make()->title("Đã đóng {$count} kèo")->success()->send();
                        }),
                    BulkAction::make('export_csv')
                        ->label('Export CSV')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $filename = 'markets_export_'.now()->format('Ymd_His').'.csv';
                            $headers = [
                                'Content-type' => 'text/csv',
                                'Content-Disposition' => "attachment; filename=$filename",
                                'Pragma' => 'no-cache',
                                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                                'Expires' => '0',
                            ];

                            $callback = function () use ($records) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, ['Trận đấu', 'Tên kèo', 'Loại kèo', 'Hiệp', 'Trạng thái', 'Giờ mở', 'Giờ đóng']);

                                foreach ($records as $market) {
                                    $matchName = $market->match ? "{$market->match->match_code} | {$market->match->home_team} vs {$market->match->away_team}" : '';
                                    fputcsv($file, [
                                        $matchName,
                                        $market->name,
                                        $market->market_type,
                                        $market->period_type,
                                        $market->status,
                                        $market->open_at?->format('Y-m-d H:i:s'),
                                        $market->close_at?->format('Y-m-d H:i:s'),
                                    ]);
                                }
                                fclose($file);
                            };

                            return response()->stream($callback, 200, $headers);
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OutcomesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarkets::route('/'),
            'create' => Pages\CreateMarket::route('/create'),
            'bulk-create' => Pages\BulkCreateMarkets::route('/bulk-create'),
            'edit' => Pages\EditMarket::route('/{record}/edit'),
        ];
    }
}
