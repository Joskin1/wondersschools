<?php

declare(strict_types=1);

namespace App\Providers;

use App\Jobs\Tenancy\CreateDatabase;
use App\Jobs\Tenancy\MigrateDatabase;
use App\Jobs\Tenancy\SeedDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Events\TenancyInitialized;
use Stancl\Tenancy\Events\TenancyEnded;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Listeners\BootstrapTenancy;
use Stancl\Tenancy\Listeners\RevertToCentralContext;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->bootEvents();
        $this->mapRoutes();
        $this->makeTenancyMiddlewareHighestPriority();

        // InitializeTenancyByDomain is now in the global web middleware so it
        // runs for every request (including Livewire's /livewire/update).
        // On central-domain requests (wonders.test) no tenant exists, so we
        // must pass through instead of throwing TenantCouldNotBeIdentifiedException.
        InitializeTenancyByDomain::$onFail = function ($exception, $request, $next) {
            return $next($request);
        };
    }

    protected function bootEvents(): void
    {
        // On every tenant request — switch DB, cache, filesystem, config.
        Event::listen(TenancyInitialized::class, BootstrapTenancy::class);
        Event::listen(TenancyEnded::class, RevertToCentralContext::class);

        // When a new school (tenant) is created via the Sudo panel:
        // 1. Create its database  2. Run tenant migrations  3. Seed defaults
        Event::listen(TenantCreated::class, function ($event) {
            CreateDatabase::dispatch($event->tenant);
        });

        Event::listen(TenantCreated::class, function ($event) {
            MigrateDatabase::dispatch($event->tenant);
        });

        Event::listen(TenantCreated::class, function ($event) {
            SeedDatabase::dispatch($event->tenant);
        });
    }

    protected function mapRoutes(): void
    {
        if (file_exists(base_path('routes/tenant.php'))) {
            Route::middleware([
                'web',
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ])->group(base_path('routes/tenant.php'));
        }
    }

    protected function makeTenancyMiddlewareHighestPriority(): void
    {
        $tenancyMiddleware = [
            PreventAccessFromCentralDomains::class,
            InitializeTenancyByDomain::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]
                ->prependToMiddlewarePriority($middleware);
        }
    }
}
