<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Services\TenantBrandingService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class AdminadminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $branding = app(TenantBrandingService::class)->resolve(request()?->getHost() ?? '');

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(fn () => $branding['name'])
            ->favicon(function () {
                $logo = \App\Services\FrontendLibrary::getSetting('school_logo');
                return $logo 
                    ? \Illuminate\Support\Facades\Storage::disk(config('filesystems.upload_disk', 'public'))->url($logo) 
                    : asset('favicon.ico');
            })
            ->login()
            ->passwordReset()
            ->profile(\App\Filament\Pages\CustomProfile::class)
            ->databaseNotifications()
            ->colors([
                'primary' => $branding['color'],
            ])
            ->navigationGroups([
                NavigationGroup::make('Academic Management')
                    ->collapsed(false),
                NavigationGroup::make('Lessons')
                    ->collapsed(false),
                NavigationGroup::make('Results')
                    ->collapsed(false),
                NavigationGroup::make('User Management')
                    ->collapsed(false),
                NavigationGroup::make('Website')
                    ->collapsed(true),
                NavigationGroup::make('System')
                    ->collapsed(true),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                \App\Filament\Teacher\Pages\EnterScores::class,
                \App\Filament\Teacher\Pages\ProcessClassResults::class,
            ])
            ->widgets([
                \App\Filament\Widgets\SchoolOverviewWidget::class,
                \App\Filament\Widgets\StudentsByClassWidget::class,
                AccountWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => view('filament.components.switch-to-teacher-button')
            )
            ->userMenuItems([
                MenuItem::make()
                    ->label('Switch to Teacher Portal')
                    ->url(fn () => url('/teacher'))
                    ->icon('heroicon-o-academic-cap')
                    ->visible(fn () => auth()->user()?->isTeachingStaff() ?? false),
            ])
            ->navigationItems([
                NavigationItem::make('Teacher Portal')
                    ->url(fn () => url('/teacher'))
                    ->icon('heroicon-o-academic-cap')
                    ->visible(fn () => auth()->user()?->isTeachingStaff() ?? false)
                    ->sort(PHP_INT_MAX - 10),
                NavigationItem::make('Visit School Website')
                    ->url(fn () => request()->getSchemeAndHttpHost(), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->sort(PHP_INT_MAX),
            ])
            ->middleware([
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\CheckUnreadReleaseNotesMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
