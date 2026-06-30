<?php

namespace App\Filament\Resources\MarketResource\Pages;

use App\Filament\Resources\MarketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarket extends EditRecord
{
    protected static string $resource = MarketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_market')
                ->label('Mở kèo')
                ->color('success')
                ->icon('heroicon-o-lock-open')
                ->visible(fn () => in_array($this->record->status, ['DRAFT', 'LOCKED']))
                ->action(function () {
                    app(\App\Domain\Market\Services\MarketLockService::class)->transition($this->record, \App\Enums\MarketStatus::OPEN);
                    \Filament\Notifications\Notification::make()->title('Đã mở kèo')->success()->send();
                }),

            Actions\Action::make('close_market')
                ->label('Đóng kèo')
                ->color('warning')
                ->icon('heroicon-o-lock-closed')
                ->visible(fn () => $this->record->status === 'OPEN')
                ->action(function () {
                    app(\App\Domain\Market\Services\MarketLockService::class)->transition($this->record, \App\Enums\MarketStatus::LOCKED);
                    \Filament\Notifications\Notification::make()->title('Đã đóng kèo')->success()->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
