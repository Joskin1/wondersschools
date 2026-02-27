<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
        return $panel
            ->id('student')
            ->path('student')
            ->brandName(fn () => $this->resolveTenantName())
            ->login()
            ->passwordReset()
            ->profile()
            ->colors([
                'primary' => $this->resolvePrimaryColor(),
            ])
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

    private function resolveTenantName(): string
    {
        try {
            $host = request()?->getHost();
            if (! $host) {
                return config('app.name');
            }

            $domain = \Stancl\Tenancy\Database\Models\Domain::on('landlord')
                ->where('domain', $host)
                ->with('tenant')
                ->first();

            return $domain?->tenant?->name ?? config('app.name');
        } catch (\Throwable) {
            return config('app.name');
        }
    }

    private function resolvePrimaryColor(): array|string
    {
        try {
            $host = request()?->getHost();
            if (! $host) {
                return Color::Amber;
            }

            $domain = \Stancl\Tenancy\Database\Models\Domain::on('landlord')
                ->where('domain', $host)
                ->with('tenant')
                ->first();

            $hex = $domain?->tenant?->primary_color;

            return $hex ? Color::hex($hex) : Color::Amber;
        } catch (\Throwable) {
            return Color::Amber;
        }
    }
}
