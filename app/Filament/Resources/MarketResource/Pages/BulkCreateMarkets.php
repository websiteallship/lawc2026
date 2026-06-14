<?php

namespace App\Filament\Resources\MarketResource\Pages;

use App\Filament\Resources\MarketResource;
use App\Helpers\CountryFlagHelper;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulkCreateMarkets extends Page
{
    protected static string $resource = MarketResource::class;

    protected string $view = 'filament.resources.market-resource.pages.bulk-create-markets';

    protected static ?string $title = 'Tạo kèo hàng loạt';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'markets' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Forms\Components\Select::make('match_id')
                    ->label('Trận đấu')
                    ->allowHtml()
                    ->options(function () {
                        return FootballMatch::whereNotIn('status', ['FINISHED'])
                            ->get()
                            ->mapWithKeys(function ($m) {
                                $home = CountryFlagHelper::renderHtml($m->home_team);
                                $away = CountryFlagHelper::renderHtml($m->away_team);
                                $homeStyled = str_replace(
                                    ['<svg', '<span'],
                                    ['<svg style="width:20px;height:14px;display:inline-block;vertical-align:middle;margin-right:5px"', '<span style="vertical-align:middle"'],
                                    $home
                                );
                                $awayStyled = str_replace(
                                    ['<svg', '<span'],
                                    ['<svg style="width:20px;height:14px;display:inline-block;vertical-align:middle;margin-right:5px"', '<span style="vertical-align:middle"'],
                                    $away
                                );
                                $label = "<div style='display:inline-flex;align-items:center;gap:6px'>"
                                    ."<span style='font-family:monospace;font-weight:bold;color:#4b5563;background:#f3f4f6;padding:1px 5px;border-radius:3px;font-size:11px'>{$m->match_code}</span>"
                                    ."{$homeStyled}<span style='color:#9ca3af;font-size:11px;font-weight:bold'>vs</span>{$awayStyled}"
                                    .'</div>';

                                return [$m->id => $label];
                            });
                    })
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($set, ?string $state) {
                        if ($state) {
                            $match = FootballMatch::find($state);
                            if ($match?->kickoff_at) {
                                $set('default_close_at', $match->kickoff_at->format('Y-m-d H:i:s'));
                                $set('default_open_at', now()->format('Y-m-d H:i:s'));
                            }
                        }
                    })
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('default_open_at')
                    ->label('Giờ mở mặc định')
                    ->required()
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->displayFormat('d/m/Y H:i')
                    ->default(now()),

                Forms\Components\DateTimePicker::make('default_close_at')
                    ->label('Giờ đóng mặc định')
                    ->required()
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->displayFormat('d/m/Y H:i'),

                Section::make('Danh sách kèo cần tạo')
                    ->description('Thêm từng loại kèo cho trận này. Mỗi khối là 1 kèo độc lập.')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('markets')
                            ->label('')
                            ->addActionLabel('+ Thêm 1 loại kèo')
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->itemLabel(function (array $state): string {
                                $typeMap = [
                                    'ASIAN_HANDICAP' => 'Kèo Chấp (AH)',
                                    'OVER_UNDER' => 'Tài/Xỉu (O/U)',
                                    'EXACT_SCORE' => 'Tỉ số chính xác',
                                ];
                                $periodMap = [
                                    'FULL_TIME' => 'Cả trận',
                                    'FIRST_HALF' => 'Hiệp 1',
                                    'SECOND_HALF' => 'Hiệp 2',
                                    'EXTRA_TIME' => 'Hiệp phụ',
                                    'PENALTY' => 'Penalty',
                                ];
                                $type = $typeMap[$state['market_type'] ?? ''] ?? '—';
                                $period = $periodMap[$state['period_type'] ?? ''] ?? '';
                                $line = '';
                                if (! empty($state['market_line'])) {
                                    $line = ' · Line '.$state['market_line'];
                                }

                                return $type.($period ? " · {$period}" : '').$line;
                            })
                            ->schema([
                                Grid::make(2)->schema([
                                    Forms\Components\Select::make('market_type')
                                        ->label('Loại kèo')
                                        ->options([
                                            'ASIAN_HANDICAP' => 'Kèo Chấp (AH)',
                                            'OVER_UNDER' => 'Tài/Xỉu (O/U)',
                                            'EXACT_SCORE' => 'Tỉ số chính xác',
                                        ])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $periodMap = [
                                                'FULL_TIME' => 'Cả trận', 'FIRST_HALF' => 'Hiệp 1',
                                                'SECOND_HALF' => 'Hiệp 2', 'EXTRA_TIME' => 'Hiệp phụ', 'PENALTY' => 'Penalty',
                                            ];
                                            $period = $periodMap[$get('period_type')] ?? '';
                                            $typeNames = [
                                                'ASIAN_HANDICAP' => 'Kèo chấp (AH)',
                                                'OVER_UNDER' => 'Tài/Xỉu (O/U)',
                                                'EXACT_SCORE' => 'Tỉ số chính xác',
                                            ];
                                            $set('name', ($typeNames[$state] ?? '').($period ? " - {$period}" : ''));
                                            self::setDefaultOutcomes($state, $set);
                                        }),

                                    Forms\Components\Select::make('period_type')
                                        ->label('Hiệp')
                                        ->options([
                                            'FULL_TIME' => 'Cả trận',
                                            'FIRST_HALF' => 'Hiệp 1',
                                            'SECOND_HALF' => 'Hiệp 2',
                                            'EXTRA_TIME' => 'Hiệp phụ',
                                            'PENALTY' => 'Penalty',
                                        ])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $periodMap = [
                                                'FULL_TIME' => 'Cả trận', 'FIRST_HALF' => 'Hiệp 1',
                                                'SECOND_HALF' => 'Hiệp 2', 'EXTRA_TIME' => 'Hiệp phụ', 'PENALTY' => 'Penalty',
                                            ];
                                            $period = $periodMap[$state] ?? '';
                                            $typeNames = [
                                                'ASIAN_HANDICAP' => 'Kèo chấp (AH)',
                                                'OVER_UNDER' => 'Tài/Xỉu (O/U)',
                                                'EXACT_SCORE' => 'Tỉ số chính xác',
                                            ];
                                            $set('name', ($typeNames[$get('market_type')] ?? '').($period ? " - {$period}" : ''));
                                        }),
                                ]),

                                Forms\Components\TextInput::make('name')
                                    ->label('Tên hiển thị')
                                    ->required()
                                    ->columnSpanFull(),

                                // Cấu hình Line/Kèo trên — chỉ hiện với AH & OU
                                Fieldset::make('Cấu hình mốc')
                                    ->schema([
                                        Forms\Components\Select::make('favorite_side')
                                            ->label('Đội Kèo Trên (Chấp)')
                                            ->options(['HOME' => 'Đội Nhà', 'AWAY' => 'Đội Khách'])
                                            ->live()
                                            ->hidden(fn ($get) => $get('market_type') !== 'ASIAN_HANDICAP')
                                            ->afterStateUpdated(fn ($state, $set, $get) => self::applyLineToOutcomes($get('market_line'), $state, $get('market_type'), $set, $get)
                                            ),

                                        Forms\Components\TextInput::make('market_line')
                                            ->label('Mốc Line (Ví dụ: 0.5, 2.5)')
                                            ->numeric()
                                            ->live(onBlur: true)
                                            ->hidden(fn ($get) => ! in_array($get('market_type'), ['ASIAN_HANDICAP', 'OVER_UNDER']))
                                            ->afterStateUpdated(fn ($state, $set, $get) => self::applyLineToOutcomes($state, $get('favorite_side'), $get('market_type'), $set, $get)
                                            ),
                                    ])
                                    ->columns(2)
                                    ->hidden(fn ($get) => ! in_array($get('market_type'), ['ASIAN_HANDICAP', 'OVER_UNDER'])),

                                // Bảng tỷ lệ ăn
                                Forms\Components\Repeater::make('outcomes')
                                    ->label('Tỷ lệ ăn')
                                    ->addable(false)
                                    ->deletable(false)
                                    ->grid(fn ($get) => $get('market_type') === 'EXACT_SCORE' ? 3 : 2)
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label('Nhãn')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1),
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
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->defaultItems(0),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Set default outcomes when market type changes.
     */
    public static function setDefaultOutcomes(string $marketType, $set): void
    {
        if ($marketType === 'ASIAN_HANDICAP') {
            $set('outcomes', [
                ['label' => 'Đội Nhà', 'selection_side' => 'HOME', 'line_value' => null, 'profit_rate' => 0.90, 'score_home' => null, 'score_away' => null, 'display_order' => 1],
                ['label' => 'Đội Khách', 'selection_side' => 'AWAY', 'line_value' => null, 'profit_rate' => 0.90, 'score_home' => null, 'score_away' => null, 'display_order' => 2],
            ]);
        } elseif ($marketType === 'OVER_UNDER') {
            $set('outcomes', [
                ['label' => 'Tài', 'selection_side' => 'OVER', 'line_value' => null, 'profit_rate' => 0.90, 'score_home' => null, 'score_away' => null, 'display_order' => 1],
                ['label' => 'Xỉu', 'selection_side' => 'UNDER', 'line_value' => null, 'profit_rate' => 0.90, 'score_home' => null, 'score_away' => null, 'display_order' => 2],
            ]);
        } elseif ($marketType === 'EXACT_SCORE') {
            $scores = [
                [1, 0], [2, 0], [2, 1], [3, 0], [3, 1], [3, 2], [4, 0], [4, 1], [4, 2], [4, 3],
                [0, 0], [1, 1], [2, 2], [3, 3], [4, 4],
                [0, 1], [0, 2], [1, 2], [0, 3], [1, 3], [2, 3], [0, 4], [1, 4], [2, 4], [3, 4],
            ];
            $outcomes = [];
            $order = 1;
            foreach ($scores as $s) {
                $outcomes[] = ['label' => "{$s[0]}-{$s[1]}", 'selection_side' => null, 'line_value' => null, 'profit_rate' => 0, 'score_home' => $s[0], 'score_away' => $s[1], 'display_order' => $order++];
            }
            $outcomes[] = ['label' => 'Tỉ số khác', 'selection_side' => 'OTHER', 'line_value' => null, 'profit_rate' => 0, 'score_home' => null, 'score_away' => null, 'display_order' => $order++];
            $set('outcomes', $outcomes);
        }
    }

    /**
     * Apply line value to all outcomes.
     */
    public static function applyLineToOutcomes(?string $line, ?string $favSide, ?string $marketType, $set, $get): void
    {
        $outcomes = $get('outcomes') ?? [];
        $lineVal = abs((float) $line);
        foreach ($outcomes as $key => $outcome) {
            if ($marketType === 'ASIAN_HANDICAP') {
                $outcomes[$key]['line_value'] = ($outcome['selection_side'] === $favSide) ? -$lineVal : $lineVal;
            } elseif ($marketType === 'OVER_UNDER') {
                $outcomes[$key]['line_value'] = $lineVal;
            }
        }
        $set('outcomes', $outcomes);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $matchId = $data['match_id'];
        $openAt = $data['default_open_at'];
        $closeAt = $data['default_close_at'];
        $markets = $data['markets'] ?? [];

        if (empty($markets)) {
            Notification::make()
                ->title('Chưa có kèo nào!')
                ->warning()
                ->send();

            return;
        }

        DB::transaction(function () use ($matchId, $openAt, $closeAt, $markets) {
            foreach ($markets as $marketData) {
                $market = Market::create([
                    'match_id' => $matchId,
                    'period_type' => $marketData['period_type'],
                    'market_type' => $marketData['market_type'],
                    'name' => $marketData['name'],
                    'open_at' => $openAt,
                    'close_at' => $closeAt,
                    'status' => 'DRAFT',
                    'display_order' => 0,
                    'created_by' => Auth::id(),
                ]);

                foreach (($marketData['outcomes'] ?? []) as $order => $outcomeData) {
                    MarketOutcome::create([
                        'market_id' => $market->id,
                        'label' => $outcomeData['label'],
                        'selection_side' => $outcomeData['selection_side'] ?? null,
                        'line_value' => $outcomeData['line_value'] ?? null,
                        'profit_rate' => $outcomeData['profit_rate'],
                        'score_home' => $outcomeData['score_home'] ?? null,
                        'score_away' => $outcomeData['score_away'] ?? null,
                        'status' => 'ACTIVE',
                        'display_order' => $outcomeData['display_order'] ?? $order,
                    ]);
                }
            }
        });

        Notification::make()
            ->title('Đã tạo '.count($markets).' kèo thành công!')
            ->success()
            ->send();

        $this->redirect(MarketResource::getUrl('index'));
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Tạo tất cả kèo')
                ->icon('heroicon-o-check-circle')
                ->action('save')
                ->color('success'),
            Action::make('cancel')
                ->label('Hủy')
                ->url(MarketResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
