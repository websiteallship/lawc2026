<?php

namespace App\Filament\Resources;

use App\Domain\Market\Services\MarketSyncService;
use App\Domain\Market\Services\OddsIntegrationService;
use App\Filament\Resources\FootballMatchResource\Pages;
use App\Filament\Resources\FootballMatchResource\RelationManagers\PeriodResultsRelationManager;
use App\Helpers\CountryFlagHelper;
use App\Models\FootballMatch;
use App\Models\Season;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FootballMatchResource extends Resource
{
    protected static ?string $model = FootballMatch::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-trophy';
    }

    public static function getNavigationLabel(): string
    {
        return 'Trận đấu';
    }

    public static function getModelLabel(): string
    {
        return 'Trận đấu';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Trận đấu';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Quản lý trận đấu';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('season_id')
                ->label('Mùa giải')
                ->options(Season::pluck('name', 'id'))
                ->required()
                ->searchable(),

            Forms\Components\TextInput::make('match_code')
                ->label('Mã trận')
                ->required()
                ->maxLength(50)
                ->placeholder('M001'),

            Forms\Components\TextInput::make('stage')
                ->label('Vòng đấu')
                ->required()
                ->maxLength(100)
                ->placeholder('Group A, R16, QF, SF, Final'),

            Forms\Components\TextInput::make('home_team')
                ->label('Đội nhà')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('away_team')
                ->label('Đội khách')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('home_score')
                ->label('Tỉ số đội nhà')
                ->numeric()
                ->minValue(0)
                ->nullable(),

            Forms\Components\TextInput::make('away_score')
                ->label('Tỉ số đội khách')
                ->numeric()
                ->minValue(0)
                ->nullable(),

            Forms\Components\DateTimePicker::make('kickoff_at')
                ->label('Giờ thi đấu')
                ->required()
                ->timezone('Asia/Ho_Chi_Minh')
                ->displayFormat('d/m/Y H:i'),

            Forms\Components\DateTimePicker::make('finished_at')
                ->label('Giờ kết thúc')
                ->timezone('Asia/Ho_Chi_Minh')
                ->displayFormat('d/m/Y H:i')
                ->nullable(),

            Forms\Components\TextInput::make('venue')
                ->label('Sân đấu')
                ->maxLength(255),

            Forms\Components\Select::make('status')
                ->label('Trạng thái')
                ->options([
                    'DRAFT' => 'Nháp',
                    'SCHEDULED' => 'Lịch thi đấu',
                    'LIVE' => 'Đang diễn ra',
                    'FINISHED' => 'Kết thúc',
                    'SETTLED' => 'Đã settle',
                    'POSTPONED' => 'Hoãn',
                    'CANCELLED' => 'Hủy',
                ])
                ->required()
                ->default('SCHEDULED'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('match_code')->label('Mã')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('stage')->label('Vòng')->sortable(),
                Tables\Columns\TextColumn::make('home_team')
                    ->label('Nhà')
                    ->searchable()
                    ->html()
                    ->formatStateUsing(fn ($state) => CountryFlagHelper::renderHtml($state)),
                Tables\Columns\TextColumn::make('away_team')
                    ->label('Khách')
                    ->searchable()
                    ->html()
                    ->formatStateUsing(fn ($state) => CountryFlagHelper::renderHtml($state)),
                Tables\Columns\TextColumn::make('score')
                    ->label('Tỉ số')
                    ->getStateUsing(function (FootballMatch $record) {
                        if ($record->home_score !== null && $record->away_score !== null) {
                            return "{$record->home_score} - {$record->away_score}";
                        }

                        return '-';
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('kickoff_at')
                    ->label('Giờ thi đấu')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Giờ kết thúc')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'SCHEDULED' => 'info',
                        'LIVE' => 'danger',
                        'FINISHED' => 'gray',
                        'SETTLED' => 'success',
                        'POSTPONED' => 'warning',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'DRAFT' => 'Nháp',
                        'SCHEDULED' => 'Lịch thi đấu',
                        'LIVE' => 'Đang diễn ra',
                        'FINISHED' => 'Kết thúc',
                        'SETTLED' => 'Đã settle',
                        'POSTPONED' => 'Hoãn',
                        'CANCELLED' => 'Hủy',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('markets_count')
                    ->label('Kèo')
                    ->counts('markets'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'SCHEDULED' => 'Lịch thi đấu',
                        'LIVE' => 'Đang diễn ra',
                        'FINISHED' => 'Kết thúc',
                    ]),
            ])
            ->defaultSort('kickoff_at')
            ->actions([
                Action::make('clearResult')
                    ->label('Xóa KQ')
                    ->icon('heroicon-o-backspace')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (FootballMatch $record) {
                        $record->update([
                            'home_score' => null,
                            'away_score' => null,
                            'status' => 'SCHEDULED',
                            'finished_at' => null,
                        ]);
                        $record->periodResults()->delete();
                        Notification::make()
                            ->title('Đã xóa kết quả trận đấu')
                            ->success()
                            ->send();
                    }),
                Action::make('syncOdds')
                    ->label('Đồng bộ Kèo API')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (FootballMatch $record) {
                        try {
                            $oddsService = app(OddsIntegrationService::class);
                            $syncService = app(MarketSyncService::class);

                            $date = $record->kickoff_at->timezone('UTC')->toDateString();
                            $oddsList = $oddsService->fetchDailyOdds($date);

                            $found = false;
                            foreach ($oddsList as $dto) {
                                if (
                                    (string)$record->api_id === (string)$dto->fixtureId || 
                                    str_contains($record->home_team, $dto->homeTeam) || 
                                    str_contains($record->away_team, $dto->awayTeam)
                                ) {
                                    $syncService->syncOddsForMatch($record, $dto);
                                    $found = true;
                                    break;
                                }
                            }

                            if ($found) {
                                Notification::make()->title('Đồng bộ kèo thành công')->success()->send();
                            } else {
                                Notification::make()->title('Không tìm thấy dữ liệu kèo trên RapidAPI')->warning()->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()->title('Lỗi đồng bộ: '.$e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('syncDetails')
                    ->label('Kéo Chi tiết (Sự kiện)')
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->color('success')
                    ->action(function (FootballMatch $record) {
                        try {
                            $syncService = app(\App\Domain\Match\Services\MatchSyncService::class);
                            $syncService->syncMatchById($record);
                            Notification::make()->title('Đã kéo chi tiết sự kiện trận đấu')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Lỗi đồng bộ: '.$e->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PeriodResultsRelationManager::class,
            FootballMatchResource\RelationManagers\EventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFootballMatches::route('/'),
            'create' => Pages\CreateFootballMatch::route('/create'),
            'edit' => Pages\EditFootballMatch::route('/{record}/edit'),
        ];
    }
}
