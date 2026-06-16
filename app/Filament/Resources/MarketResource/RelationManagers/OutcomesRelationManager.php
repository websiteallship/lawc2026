<?php

namespace App\Filament\Resources\MarketResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OutcomesRelationManager extends RelationManager
{
    protected static string $relationship = 'outcomes';

    protected static ?string $title = 'Tỷ lệ ăn';

    protected static ?string $modelLabel = 'Outcome';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('label')
                ->label('Nhãn')
                ->required()
                ->maxLength(255)
                ->placeholder('Home -0.5, Tài 2.5, 2-1'),

            Forms\Components\Select::make('selection_side')
                ->label('Phía chọn')
                ->options([
                    'HOME' => 'Đội nhà',
                    'AWAY' => 'Đội khách',
                    'OVER' => 'Tài',
                    'UNDER' => 'Xỉu',
                    'DRAW' => 'Hòa',
                ])
                ->nullable(),

            Forms\Components\TextInput::make('line_value')
                ->label('Đường kèo')
                ->numeric()
                ->nullable()
                ->placeholder('-0.50, 2.25'),

            Forms\Components\TextInput::make('score_home')
                ->label('Bàn nhà')
                ->integer()
                ->nullable()
                ->placeholder('Chỉ dùng cho tỉ số'),

            Forms\Components\TextInput::make('score_away')
                ->label('Bàn khách')
                ->integer()
                ->nullable(),

            Forms\Components\TextInput::make('profit_rate')
                ->label('Tỷ lệ ăn (profit_rate)')
                ->numeric()
                ->required()
                ->step(0.0001)
                ->placeholder('0.90')
                ->helperText('Nhập 0.90 = ăn 0.90. Gross payout = stake × (1 + 0.90) = 1.90 × stake'),

            Forms\Components\Select::make('status')
                ->label('Trạng thái')
                ->options([
                    'ACTIVE' => 'Đang mở',
                    'INACTIVE' => 'Tắt',
                    'SUSPENDED' => 'Tạm ngưng',
                ])
                ->required()
                ->default('ACTIVE'),

            Forms\Components\TextInput::make('display_order')
                ->label('Thứ tự')
                ->integer()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')->label('Nhãn')->searchable(),
                Tables\Columns\TextColumn::make('selection_side')->label('Phía'),
                Tables\Columns\TextColumn::make('line_value')->label('Đường kèo'),
                Tables\Columns\TextColumn::make('profit_rate')
                    ->label('Tỷ lệ ăn')
                    ->formatStateUsing(fn ($state) => "ăn {$state}"),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ACTIVE' => 'success',
                        'SUSPENDED' => 'warning',
                        'INACTIVE' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('display_order')->label('#')->sortable(),
            ])
            ->reorderable('display_order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('active_selected')
                        ->label('Active selected')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['status' => 'ACTIVE']);
                            \Filament\Notifications\Notification::make()
                                ->title('Đã mở (ACTIVE) các tỷ lệ được chọn')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    \Filament\Actions\BulkAction::make('suspend_selected')
                        ->label('Suspend selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['status' => 'SUSPENDED']);
                            \Filament\Notifications\Notification::make()
                                ->title('Đã tạm ngưng (SUSPENDED) các tỷ lệ được chọn')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
