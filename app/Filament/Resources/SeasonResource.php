<?php

namespace App\Filament\Resources;

use App\Domain\Wallet\Services\WalletService;
use App\Filament\Resources\SeasonResource\Pages;
use App\Models\Season;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class SeasonResource extends Resource
{
    protected static ?string $model = Season::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getModelLabel(): string
    {
        return 'Mùa giải';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Mùa giải';
    }

    public static function getNavigationGroup(): string|\BackedEnum|null
    {
        return 'Quản lý trận đấu';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('code')
                    ->label('Mã mùa giải')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')
                    ->label('Tên mùa giải')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Ngày bắt đầu'),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('Ngày kết thúc'),
                Forms\Components\Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'draft' => 'Nháp',
                        'active' => 'Đang diễn ra',
                        'closed' => 'Đã đóng',
                    ])
                    ->default('draft')
                    ->required(),
                Forms\Components\TextInput::make('default_starting_leaves')
                    ->label('Số Lá cấp ban đầu')
                    ->required()
                    ->numeric()
                    ->default(1000)
                    ->minValue(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Mã')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên')
                    ->searchable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Bắt đầu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Kết thúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_starting_leaves')
                    ->label('Lá ban đầu')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'draft' => 'Nháp',
                        'active' => 'Hoạt động',
                        'closed' => 'Đã đóng',
                    ]),
            ])
            ->actions([
                EditAction::make(),

                Action::make('activate')
                    ->label('Kích hoạt')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->hidden(fn (Season $record): bool => $record->status === 'active')
                    ->action(function (Season $record) {
                        // Deactivate all others
                        Season::where('id', '!=', $record->id)->update(['status' => 'closed']);
                        $record->update(['status' => 'active']);
                        Notification::make()->title('Thành công')->body("Mùa giải {$record->name} đã được kích hoạt.")->success()->send();
                    })
                    ->requiresConfirmation(),

                Action::make('close')
                    ->label('Đóng mùa giải')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->hidden(fn (Season $record): bool => $record->status === 'closed')
                    ->action(function (Season $record) {
                        $record->update(['status' => 'closed']);
                        Notification::make()->title('Thành công')->body("Mùa giải {$record->name} đã đóng.")->success()->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('seed_wallets')
                        ->label('Cấp Ví & Lá cho Player')
                        ->icon('heroicon-o-wallet')
                        ->color('warning')
                        ->action(function (Collection $records, WalletService $walletService) {
                            $count = 0;
                            foreach ($records as $season) {
                                // Find all players
                                $players = User::role('player')->where('status', 'ACTIVE')->get();
                                foreach ($players as $player) {
                                    // createWallet uses firstOrCreate and will grant if it was recently created
                                    $wallet = $walletService->createWallet($player, $season);
                                    if ($wallet->wasRecentlyCreated) {
                                        $count++;
                                    }
                                }
                            }

                            Notification::make()
                                ->title('Hoàn tất')
                                ->body("Đã cấp ví và Lá ban đầu thành công cho {$count} người chơi.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSeasons::route('/'),
        ];
    }
}
