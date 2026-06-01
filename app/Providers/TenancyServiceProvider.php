<?php

declare(strict_types=1);

namespace App\Providers;

use App\Jobs\Tenancy\ProvisionTenantJob;
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
use Stancl\Tenancy\Resolvers\DomainTenantResolver;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── Native domain-resolution caching ────────────────────────────────
        // Cache resolved Tenant lookups so that the "domains" table is NOT
        // queried on every single HTTP request. TTL: 24 hours.
        // Invalidation is automatic: App\Models\Tenant uses the package's
        // InvalidatesResolverCache trait which calls invalidateCache() on
        // every saved/deleting event, covering domain changes from the Sudo panel.
        DomainTenantResolver::$shouldCache = true;
        DomainTenantResolver::$cacheTTL    = 86400; // 24 hours in seconds
        DomainTenantResolver::$cacheStore  = null;  // use default cache store

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
        // Dispatch the single orchestrator job that handles the full pipeline:
        // create DB → migrate → seed → validate → activate → warm cache.
        Event::listen(TenantCreated::class, function ($event) {
            ProvisionTenantJob::dispatch($event->tenant);
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
