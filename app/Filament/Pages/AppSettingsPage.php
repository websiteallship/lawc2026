<?php

namespace App\Filament\Pages;

use App\Settings\AppSettings;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class AppSettingsPage extends Page
{
    use HasPageShield;
    protected string $view = 'filament.pages.app-settings-page';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationLabel(): string
    {
        return 'Cài đặt hệ thống';
    }

    public static function getNavigationGroup(): string|\BackedEnum|null
    {
        return 'Hệ thống';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public ?array $data = [];

    public function mount(AppSettings $settings): void
    {
        $this->form->fill([
            'app_name' => $settings->app_name,
            'app_logo_url' => $settings->app_logo_url,
            'min_stake' => $settings->min_stake,
            'max_stake_per_bet' => $settings->max_stake_per_bet,
            'max_stake_per_match' => $settings->max_stake_per_match,
            'default_starting_leaves' => $settings->default_starting_leaves,
            'enable_local_logins' => $settings->enable_local_logins,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Thông tin ứng dụng')
                    ->schema([
                        Forms\Components\TextInput::make('app_name')
                            ->label('Tên ứng dụng')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('app_logo_url')
                            ->label('URL Logo')
                            ->url()
                            ->placeholder('https://...')
                            ->nullable(),
                    ])->columns(2),

                Section::make('Giới hạn đặt cược')
                    ->schema([
                        Forms\Components\TextInput::make('min_stake')
                            ->label('Cược tối thiểu (Lá)')
                            ->numeric()->required()->minValue(1),
                        Forms\Components\TextInput::make('max_stake_per_bet')
                            ->label('Cược tối đa mỗi vé (Lá)')
                            ->numeric()->required()->minValue(1),
                        Forms\Components\TextInput::make('max_stake_per_match')
                            ->label('Cược tối đa mỗi trận (Lá)')
                            ->numeric()->required()->minValue(1),
                        Forms\Components\TextInput::make('default_starting_leaves')
                            ->label('Lá ban đầu mỗi mùa giải')
                            ->numeric()->required()->minValue(0),
                    ])->columns(2),

                Section::make('Tính năng')
                    ->schema([
                        Forms\Components\Toggle::make('enable_local_logins')
                            ->label('Bật chuyển tài khoản nhanh (Local Logins)')
                            ->helperText('Chỉ bật trong môi trường development/testing nội bộ.'),
                    ]),
            ]);
    }

    public function save(AppSettings $settings): void
    {
        $data = $this->form->getState();

        $settings->app_name = $data['app_name'];
        $settings->app_logo_url = $data['app_logo_url'] ?: null;
        $settings->min_stake = (int) $data['min_stake'];
        $settings->max_stake_per_bet = (int) $data['max_stake_per_bet'];
        $settings->max_stake_per_match = (int) $data['max_stake_per_match'];
        $settings->default_starting_leaves = (int) $data['default_starting_leaves'];
        $settings->enable_local_logins = (bool) $data['enable_local_logins'];
        $settings->save();

        Notification::make()->title('Đã lưu cài đặt')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Lưu cài đặt')
                ->submit('save'),
        ];
    }
}
