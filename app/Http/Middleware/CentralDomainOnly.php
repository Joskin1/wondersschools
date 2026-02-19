<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CentralDomainOnly
{
    /**
     * Ensure the request is coming from a central domain.
     *
     * This middleware is used to protect the Sudo/Central panel from
     * being accessed via a tenant domain.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        if (!in_array($host, $centralDomains, true)) {
            abort(404);
        }

        // Ensure we're using the central DB connection
        DB::setDefaultConnection('central');

        return $next($request);
    }
}
