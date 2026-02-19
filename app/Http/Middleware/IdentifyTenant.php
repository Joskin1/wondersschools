<?php

namespace App\Http\Middleware;

use App\Models\Central\Domain;
use App\Models\Central\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * Resolves the tenant from the request domain, configures the
     * database connection, and sets the default connection to 'tenant'.
     *
     * This middleware MUST run before authentication and Filament panel loading.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Skip tenant resolution for central domains
        $centralDomains = config('tenancy.central_domains', []);
        if (in_array($host, $centralDomains, true)) {
            // Central domain: use central connection
            DB::setDefaultConnection('central');
            return $next($request);
        }

        // Resolve school from domain
        $school = $this->resolveSchool($host, $request);

        if (!$school) {
            Log::warning('Tenant resolution failed: domain not found', [
                'domain' => $host,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            abort(404, 'School Not Found');
        }

        // Check if school is suspended
        if ($school->isSuspended()) {
            Log::warning('Suspended tenant access attempt', [
                'tenant_id' => $school->id,
                'domain' => $host,
                'ip' => $request->ip(),
            ]);
            abort(403, 'School Account Suspended');
        }

        // Configure and switch to tenant database
        try {
            $school->configureTenantConnection();
            DB::setDefaultConnection('tenant');

            // Verify the connection actually works
            DB::connection('tenant')->getPdo();
        } catch (\Exception $e) {
            Log::critical('Tenant DB connection failed', [
                'tenant_id' => $school->id,
                'domain' => $host,
                'error' => $e->getMessage(),
                // NEVER log decrypted credentials
            ]);
            abort(503, 'Service Temporarily Unavailable');
        }

        // Store resolved tenant in the application container for downstream access
        app()->instance('tenant', $school);
        app()->instance('tenant.id', $school->id);

        // Log successful tenant resolution
        Log::channel('daily')->info('Tenant resolved', [
            'tenant_id' => $school->id,
            'domain' => $host,
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Resolve the school from the domain.
     * Uses per-request caching via the Domain model.
     */
    private function resolveSchool(string $host, Request $request): ?School
    {
        try {
            return Domain::findSchoolByDomain($host);
        } catch (\Exception $e) {
            Log::error('Failed to query central DB for domain resolution', [
                'domain' => $host,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
