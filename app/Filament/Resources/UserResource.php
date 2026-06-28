<?php

namespace App\Filament\Resources;

use App\Domain\Wallet\Services\WalletService;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Season;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-users';
    }

    public static function getModelLabel(): string
    {
        return 'Người dùng';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Người dùng';
    }

    public static function getNavigationGroup(): string|\BackedEnum|null
    {
        return 'Hệ thống';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Họ tên')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('role')
                    ->label('Vai trò')
                    ->options(function () {
                        $options = [
                            'player' => 'Người chơi',
                        ];
                        if (auth()->user()?->hasRole('super_admin')) {
                            $options['operator'] = 'Operator';
                            $options['super_admin'] = 'Quản trị viên';
                        } elseif (auth()->user()?->hasRole('operator')) {
                            $options['operator'] = 'Operator';
                        }
                        return $options;
                    })
                    ->default('player')
                    ->required()
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?User $record) {
                        if ($record) {
                            $component->state($record->roles->pluck('name')->first() ?? 'player');
                        }
                    })
                    ->dehydrated(false)
                    ->saveRelationshipsUsing(function (User $record, $state) {
                        if ($state) {
                            $record->syncRoles([$state]);
                        } else {
                            $record->syncRoles([]);
                        }
                    }),
                Forms\Components\Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'ACTIVE' => 'Hoạt động',
                        'BLOCKED' => 'Bị khóa',
                    ])
                    ->default('ACTIVE')
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->label('Mật khẩu')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->helperText(fn (string $context): string => $context === 'edit' ? 'Để trống nếu không muốn thay đổi mật khẩu.' : '')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_test_user')
                    ->label('Tài khoản Testing')
                    ->helperText('Bật nếu đây là account test, sẽ bị loại khỏi báo cáo dashboard admin.')
                    ->default(false)
                    ->columnSpanFull()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'operator'])),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Họ tên')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Vai trò')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'Quản trị viên',
                        'operator' => 'Operator',
                        'player' => 'Người chơi',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'operator' => 'warning',
                        'player' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ACTIVE' => 'success',
                        'BLOCKED' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('wallet_balance')
                    ->label('Lá khả dụng')
                    ->state(function (User $record): string {
                        $activeSeason = Season::where('status', 'active')->first();
                        if (! $activeSeason) {
                            return '0';
                        }
                        $wallet = $record->wallets()->where('season_id', $activeSeason->id)->first();

                        return $wallet ? number_format($wallet->available_balance) : '0';
                    })
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_granted_leaves')
                    ->label('Tổng lá nạp')
                    ->state(function (User $record): string {
                        $activeSeason = Season::where('status', 'active')->first();
                        if (! $activeSeason) {
                            return '0';
                        }
                        $total = \App\Models\WalletLedger::where('user_id', $record->id)
                            ->where('season_id', $activeSeason->id)
                            ->where('type', \App\Enums\LedgerType::ADMIN_GRANT)
                            ->sum('amount_available');

                        return number_format($total);
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('accepted_rules_at')
                    ->label('Đồng ý Rules')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => ! is_null($record->accepted_rules_at)),
                Tables\Columns\IconColumn::make('is_test_user')
                    ->label('Test')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->trueIcon('heroicon-s-beaker')
                    ->falseIcon('heroicon-o-minus')
                    ->tooltip(fn (User $record) => $record->is_test_user ? 'Tài khoản test (đang bị loại khỏi báo cáo)' : 'Người chơi thực'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Vai trò')
                    ->options([
                        'super_admin' => 'Quản trị viên',
                        'operator' => 'Operator',
                        'player' => 'Người chơi',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('roles', fn ($q) => $q->where('name', $data['value']));
                        }
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'ACTIVE' => 'Hoạt động',
                        'BLOCKED' => 'Khóa',
                    ]),
                Tables\Filters\TernaryFilter::make('is_test_user')
                    ->label('Loại tài khoản')
                    ->trueLabel('Chỉ hiện account test')
                    ->falseLabel('Chỉ hiện người chơi thực'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('grant_leaves')
                    ->visible(fn (User $record): bool => !auth()->user()?->hasRole('operator') || $record->id === auth()->id() || $record->hasRole('player'))
                    ->label('Cấp Lá')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Số lượng Lá')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('reason')
                            ->label('Lý do')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data, WalletService $walletService): void {
                        $activeSeason = Season::where('status', 'active')->first();
                        if (! $activeSeason) {
                            Notification::make()->title('Lỗi')->body('Không có mùa giải nào đang diễn ra.')->danger()->send();

                            return;
                        }

                        $wallet = $record->wallets()->firstOrCreate(
                            ['season_id' => $activeSeason->id],
                            ['available_balance' => 0, 'locked_balance' => 0, 'status' => 'ACTIVE']
                        );

                        $walletService->grant($wallet, (int) $data['amount'], auth()->user(), $data['reason']);
                        Notification::make()->title('Thành công')->body('Đã cấp '.$data['amount'].' Lá cho user '.$record->name)->success()->send();
                    })
                    ->requiresConfirmation(),

                Action::make('deduct_leaves')
                    ->visible(fn (User $record): bool => !auth()->user()?->hasRole('operator') || $record->id === auth()->id() || $record->hasRole('player'))
                    ->label('Trừ Lá')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Số lượng Lá')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('reason')
                            ->label('Lý do')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data, WalletService $walletService): void {
                        $activeSeason = Season::where('status', 'active')->first();
                        if (! $activeSeason) {
                            Notification::make()->title('Lỗi')->body('Không có mùa giải nào đang diễn ra.')->danger()->send();

                            return;
                        }

                        $wallet = $record->wallets()->where('season_id', $activeSeason->id)->first();
                        if (! $wallet || $wallet->available_balance < (int) $data['amount']) {
                            Notification::make()->title('Lỗi')->body('Số dư khả dụng không đủ để trừ.')->danger()->send();

                            return;
                        }

                        $walletService->deduct($wallet, (int) $data['amount'], auth()->user(), $data['reason']);
                        Notification::make()->title('Thành công')->body('Đã trừ '.$data['amount'].' Lá của user '.$record->name)->success()->send();
                    })
                    ->requiresConfirmation(),

                Action::make('toggle_status')
                    ->visible(fn (User $record): bool => !auth()->user()?->hasRole('operator') || $record->id === auth()->id() || $record->hasRole('player'))
                    ->label(fn (User $record): string => $record->status === 'ACTIVE' ? 'Khóa tài khoản' : 'Mở khóa')
                    ->icon(fn (User $record): string => $record->status === 'ACTIVE' ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $record): string => $record->status === 'ACTIVE' ? 'danger' : 'success')
                    ->action(function (User $record): void {
                        $newStatus = $record->status === 'ACTIVE' ? 'BLOCKED' : 'ACTIVE';
                        $record->update(['status' => $newStatus]);
                        Notification::make()->title('Thành công')->body('Đã thay đổi trạng thái thành '.$newStatus)->success()->send();
                    })
                    ->requiresConfirmation(),

                Action::make('reset_password')
                    ->visible(fn (User $record): bool => !auth()->user()?->hasRole('operator') || $record->id === auth()->id() || $record->hasRole('player'))
                    ->label('Reset Mật khẩu')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Mật khẩu mới')
                            ->password()
                            ->required()
                            ->minLength(6)
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'password' => Hash::make($data['password']),
                        ]);
                        Notification::make()
                            ->title('Thành công')
                            ->body("Đã đổi mật khẩu cho người dùng {$record->name}")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Action::make('toggle_test_user')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'operator']))
                    ->label(fn (User $record): string => $record->is_test_user ? 'Bỏ tất đánh Test' : 'Đánh dấu Test')
                    ->icon(fn (User $record): string => $record->is_test_user ? 'heroicon-o-check-circle' : 'heroicon-o-beaker')
                    ->color(fn (User $record): string => $record->is_test_user ? 'success' : 'warning')
                    ->action(function (User $record): void {
                        $record->update(['is_test_user' => ! $record->is_test_user]);
                        $label = $record->is_test_user ? 'đã được đánh dấu là TEST' : 'đã bỏ dấu test, trở lại người chơi thực';
                        Notification::make()->title('Thành công')->body("{$record->name} {$label}.")->success()->send();
                    })
                    ->requiresConfirmation(),

                Action::make('require_reaccept_rules')
                    ->visible(fn (User $record): bool => (!auth()->user()?->hasRole('operator') || $record->id === auth()->id() || $record->hasRole('player')) && ! is_null($record->accepted_rules_at))
                    ->label('Yêu cầu đồng ý lại Rules')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->action(function (User $record): void {
                        $record->update(['accepted_rules_at' => null]);
                        Notification::make()
                            ->title('Thành công')
                            ->body("Người dùng {$record->name} sẽ phải đồng ý lại thể lệ ở lần đăng nhập tới.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('require_reaccept_rules_bulk')
                        ->label('Yêu cầu đồng ý lại Rules')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->action(function (Collection $records): void {
                            $allowedRecords = $records->filter(function ($record) {
                                return !auth()->user()?->hasRole('operator') || $record->id === auth()->id() || $record->hasRole('player');
                            });
                            
                            foreach ($allowedRecords as $record) {
                                $record->update(['accepted_rules_at' => null]);
                            }
                            Notification::make()
                                ->title('Thành công')
                                ->body('Đã yêu cầu '.$allowedRecords->count().' người dùng phải đồng ý lại thể lệ.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (auth()->check() && auth()->user()->hasRole('operator')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            });
        }
        
        return $query;
    }

    public static function getRelations(): array
    {
        return [
            UserResource\RelationManagers\WalletLedgersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
