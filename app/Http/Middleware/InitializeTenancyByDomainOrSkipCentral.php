<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

/**
 * Wraps InitializeTenancyByDomain so that requests arriving on a central
 * domain (e.g. the sudo panel or the marketing site root) are simply passed
 * through without any tenant resolution attempt.
 *
 * Without this guard the DomainTenantResolver throws
 * TenantCouldNotBeIdentifiedOnDomainException for every central-domain
 * request, breaking the sudo panel and any other central-only routes.
 */
class InitializeTenancyByDomainOrSkipCentral
{
    public function __construct(protected InitializeTenancyByDomain $inner) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array($request->getHost(), config('tenancy.central_domains', []))) {
            return $next($request);
        }

        return $this->inner->handle($request, $next);
    }
}
