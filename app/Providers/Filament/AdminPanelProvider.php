<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AdminBetsPerDayChart;
use App\Filament\Widgets\AdminLeaderboardWidget;
use App\Filament\Widgets\AdminMarketTypeChart;
use App\Filament\Widgets\AdminRecentAuditLogsWidget;
use App\Filament\Widgets\AdminStatsOverviewWidget;
use App\Filament\Widgets\AdminTopPlayersChart;
use App\Settings\AppSettings;
use BetterFuturesStudio\FilamentLocalLogins\LocalLogins;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(\App\Filament\Pages\Auth\EditProfile::class, isSimple: false)
            ->brandName(config('app.name', 'Dự Đoán Lá'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->databaseNotifications()
            ->renderHook(
                \Filament\View\PanelsRenderHook::FOOTER,
                fn (): string => '
                    <div style="text-align: center; width: 100%; padding: 24px 16px; border-top: 1px solid rgba(128, 128, 128, 0.2); font-family: inherit; font-size: 0.75rem; line-height: 1.5; opacity: 0.7; margin-top: 20px;">
                        <p style="margin: 0; font-weight: 600;">Game nội bộ sử dụng điểm ảo giải trí, không có giá trị quy đổi thành tiền hay hiện vật.</p>
                        <p style="margin: 4px 0 0 0; opacity: 0.8;">Mọi dữ liệu kết quả trận đấu đều được đồng bộ tự động từ API nhằm đảm bảo tính minh bạch và thời gian thực (realtime).</p>
                    </div>
                '
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->navigationGroups([
                'Quản lý trận đấu',
                'Quản lý Giao dịch',
                'Phân quyền',
                'Hệ thống',
            ])
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AdminStatsOverviewWidget::class,
                \App\Filament\Widgets\AdminHouseProfitChart::class,
                \App\Filament\Widgets\AdminMarketStatusListWidget::class,
                \App\Filament\Widgets\AdminNegativeWalletsWidget::class,
                AdminLeaderboardWidget::class,
                AdminTopPlayersChart::class,
                AdminBetsPerDayChart::class,
                AdminMarketTypeChart::class,
                AdminRecentAuditLogsWidget::class,
            ])
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
            ->plugins([
                \Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin::make(),
                FilamentShieldPlugin::make(),
                ...((app()->environment('local') && rescue(fn () => app(AppSettings::class)->enable_local_logins, env('LOCAL_LOGINS_ENABLED', false)))
                    ? [new LocalLogins]
                    : []),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
