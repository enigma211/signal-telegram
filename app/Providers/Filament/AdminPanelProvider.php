<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\ActiveVipStats;
use App\Filament\Widgets\AiWinRateStats;
use App\Filament\Widgets\MonthlyRevenueStats;
use App\Filament\Widgets\RecentOutboundErrors;
use App\Filament\Widgets\RevenueStats;
use App\Filament\Widgets\SystemHealthStats;
use App\Filament\Widgets\UserStats;
use App\Http\Middleware\SetFilamentLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use App\Filament\Pages\Dashboard;
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
            ->brandName('نوا سیگنال')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->font('Vazirmatn')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                SystemHealthStats::class,
                UserStats::class,
                ActiveVipStats::class,
                MonthlyRevenueStats::class,
                RevenueStats::class,
                AiWinRateStats::class,
                RecentOutboundErrors::class,
                Widgets\AccountWidget::class,
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
                SetFilamentLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
