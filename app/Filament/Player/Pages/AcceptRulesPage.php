<?php

namespace App\Filament\Player\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AcceptRulesPage extends Page
{
    protected static ?string $navigationLabel = 'Thể lệ';

    protected static ?string $title = 'Xác nhận thể lệ tham gia';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-shield-check';
    }

    protected string $view = 'filament.player.pages.accept-rules';

    public bool $agreed = false;

    public function mount(): void
    {
        // Nếu đã accept rồi thì redirect dashboard
        if (Auth::user()?->accepted_rules_at) {
            $this->redirect(url('/player/player-dashboard'));
        }
    }

    public function accept(): void
    {
        if (! $this->agreed) {
            Notification::make()
                ->title('Vui lòng đọc và tích vào ô xác nhận.')
                ->danger()
                ->send();

            return;
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->accepted_rules_at = now();
        $user->save();

        Notification::make()
            ->title('Chào mừng bạn đến với World Cup 2026!')
            ->success()
            ->send();

        $this->redirect(url('/player/player-dashboard'));
    }
}
