<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantAwareLogging
{
    /**
     * Add tenant context to all log entries for the current request.
     *
     * Logs: tenant_id, domain, user_id, IP, action (method + path).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = app()->bound('tenant.id') ? app('tenant.id') : null;

        Log::shareContext([
            'tenant_id' => $tenantId,
            'domain' => $request->getHost(),
            'ip' => $request->ip(),
            'request_method' => $request->method(),
            'request_path' => $request->path(),
        ]);

        $response = $next($request);

        // Add user_id after auth has run
        if ($request->user()) {
            Log::shareContext([
                'user_id' => $request->user()->id,
            ]);
        }

        return $response;
    }
}
