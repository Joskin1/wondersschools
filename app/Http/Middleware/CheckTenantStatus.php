<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant() && tenant()->isSuspended()) {
            \Illuminate\Support\Facades\Log::warning('Suspended school access attempt', [
                'tenant_id' => tenant('id'),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            abort(403, 'School Account Suspended');
        }

        return $next($request);
    }
}
