<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\CustomProfile;
use App\Filament\Student\Auth\Login;
use App\Services\TenantBrandingService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class StudentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $branding = app(TenantBrandingService::class)->resolve(request()?->getHost() ?? '');

        return $panel
            ->id('student')
            ->path('student')
            ->brandName(fn () => $branding['name'])
            ->favicon(function () {
                $logo = \App\Services\FrontendLibrary::getSetting('school_logo');
                return $logo 
                    ? \Illuminate\Support\Facades\Storage::disk(config('filesystems.upload_disk', 'public'))->url($logo) 
                    : asset('favicon.ico');
            })
            ->login(Login::class)
            ->profile(CustomProfile::class)
            ->colors([
                'primary' => $branding['color'],
            ])
            ->discoverResources(in: app_path('Filament/Student/Resources'), for: 'App\\Filament\\Student\\Resources')
            ->discoverPages(in: app_path('Filament/Student/Pages'), for: 'App\\Filament\\Student\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
