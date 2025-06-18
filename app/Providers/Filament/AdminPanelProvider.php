<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AnalisisData;
use App\Filament\Widgets\RingkasanStatistikUtama;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Filament\Pages\Page;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->topNavigation()
            ->brandLogo(asset('images/LOGOIM.png'))
            ->brandLogoHeight('4rem')
            ->colors([
                'primary' => Color::hex('#800020'),
                'secondary' => Color::hex('#FFD700'),
                'danger' => Color::Rose,
                'gray' => Color::Slate,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info' => Color::Blue,
            ])
            ->sidebarWidth('13rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                AnalisisData::class
            ])
            ->databaseNotifications()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\ZakatStatsOverview::class,
                \App\Filament\Widgets\TopDonaturRingkasWidget::class,
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
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        userMenuLabel: 'Profil Saya',
                        shouldRegisterNavigation: false,
                        navigationGroup: 'Pengaturan',
                        hasAvatars: false, // Disable avatar upload
                        slug: 'my-profile'
                    )
                    ->enableTwoFactorAuthentication(
                        force: false, // Set to true if you want to force all users to enable 2FA
                    ),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])                ->navigationGroups([
                    __('app.navigation.groups.program'),
                    __('app.navigation.groups.reports_finance'),
                    __('app.navigation.groups.administrator'),
                ]);
    }
}





