<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsurePlayerAcceptedRules;
use App\Http\Middleware\PlayerScopeMiddleware;
use App\Settings\AppSettings;
use BetterFuturesStudio\FilamentLocalLogins\LocalLogins;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PlayerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('player')
            ->path('player')
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('@livewire(\'player-balance-header\')')
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): string => '
                    <div style="text-align: center; width: 100%; padding: 24px 16px; border-top: 1px solid rgba(128, 128, 128, 0.2); font-family: inherit; font-size: 0.75rem; line-height: 1.5; opacity: 0.7; margin-top: 20px;">
                        <p style="margin: 0; font-weight: 600;">Game nội bộ sử dụng điểm ảo giải trí, không có giá trị quy đổi thành tiền hay hiện vật.</p>
                        <p style="margin: 4px 0 0 0; opacity: 0.8;">Mọi dữ liệu kết quả trận đấu đều được đồng bộ tự động từ API nhằm đảm bảo tính minh bạch và thời gian thực (realtime).</p>
                    </div>
                '
            )
            ->viteTheme('resources/css/filament/player/theme.css')
            ->login()
            ->profile(\App\Filament\Pages\Auth\EditProfile::class, isSimple: false)
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->brandName(config('app.brand_name', 'World Cup 2026'))
            ->brandLogo(fn () => config('app.brand_logo_url') ?: null)
            ->favicon(asset('favicon.ico'))
            ->discoverResources(in: app_path('Filament/Player/Resources'), for: 'App\\Filament\\Player\\Resources')
            ->discoverPages(in: app_path('Filament/Player/Pages'), for: 'App\\Filament\\Player\\Pages')
            ->discoverWidgets(in: app_path('Filament/Player/Widgets'), for: 'App\\Filament\\Player\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsurePlayerAcceptedRules::class,
                PlayerScopeMiddleware::class,
            ])
            ->plugins([
                ...((app()->environment('local') && rescue(fn () => app(AppSettings::class)->enable_local_logins, env('LOCAL_LOGINS_ENABLED', false)))
                    ? [new LocalLogins]
                    : []),
            ]);
    }
}
