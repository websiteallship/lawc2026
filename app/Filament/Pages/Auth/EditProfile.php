<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Họ tên')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->live(debounce: 500);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('currentPassword')
            ->label('Mật khẩu cũ')
            ->password()
            ->autocomplete('current-password')
            ->currentPassword(guard: \Filament\Facades\Filament::getAuthGuard())
            ->revealable(filament()->arePasswordsRevealable())
            ->required(fn (Get $get): bool => filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email')))
            ->visible(true) // Luôn hiển thị
            ->dehydrated(false);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Mật khẩu mới')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->rule(Password::default())
            ->autocomplete('new-password')
            ->dehydrated(fn ($state): bool => filled($state))
            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
            ->live(debounce: 500)
            ->same('passwordConfirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Xác nhận mật khẩu mới')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required(fn (Get $get): bool => filled($get('password')))
            ->visible(true) // Luôn hiển thị
            ->dehydrated(false);
    }
}
