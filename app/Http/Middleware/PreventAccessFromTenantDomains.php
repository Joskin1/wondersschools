<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Prevent access to central-only routes (e.g. the sudo panel) from tenant domains.
 *
 * If tenancy has been initialised for this request it means the request arrived
 * via a tenant domain.  Central-only panels should never be reachable from
 * a tenant domain, so we abort with 404.
 */
class PreventAccessFromTenantDomains
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (tenancy()->initialized) {
            abort(404);
        }

        return $next($request);
    }
}
