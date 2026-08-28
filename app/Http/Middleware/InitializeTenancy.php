<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class InitializeTenancy extends InitializeTenancyByDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $singleTenantId = env('SINGLE_TENANT_ID');

        if ($singleTenantId && !$request->is('sudo', 'sudo/*')) {
            $host = $request->getHost();
            $localCentralDomains = ['wonders.test', 'localhost', '127.0.0.1'];

            // If the host is not one of our local development central domains,
            // and it does not end with '.test' (e.g. for local subdomains like chizylite.wonders.test),
            // we initialize the single tenant directly.
            if (!in_array($host, $localCentralDomains, true) && !str_ends_with($host, '.test')) {
                try {
                    $tenant = Tenant::find($singleTenantId);
                    if ($tenant) {
                        $this->tenancy->initialize($tenant);
                        return $next($request);
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return parent::handle($request, $next);
    }
}
