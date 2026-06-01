<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Cache;

/**
 * Centralized tenant branding resolution with 24-hour caching.
 *
 * Replaces the duplicate resolveBranding()/defaultBranding() methods that
 * previously existed in AdminadminPanelProvider, TeacherPanelProvider, and
 * StudentPanelProvider. Now all three panels delegate to this service.
 *
 * Cache key: tenant_branding:{host}
 * TTL:       24 hours (86400 seconds)
 * Busting:   Tenant::booted() calls invalidate() + warm() on every save/delete.
 */
class TenantBrandingService
{
    /** Cache TTL — 24 hours. */
    private const TTL = 86400;

    /**
     * Resolve the branding payload for a given host from cache.
     *
     * On cache HIT  → zero DB queries.
     * On cache MISS → one Landlord DB query, result stored for 24 hours.
     * On any error  → safe defaults returned without surfacing exceptions.
     *
     * @return array{name: string, color: array<string,string>|string}
     */
    public function resolve(string $host): array
    {
        if ($host === '') {
            return $this->defaults();
        }

        return Cache::remember(
            "tenant_branding:{$host}",
            self::TTL,
            function () use ($host): array {
                try {
                    $domain = \Stancl\Tenancy\Database\Models\Domain::on('landlord')
                        ->where('domain', $host)
                        ->with('tenant:id,name,primary_color')
                        ->first();

                    $tenant = $domain?->tenant;

                    return [
                        'name'  => $tenant?->name ?? config('app.name'),
                        'color' => $tenant?->primary_color
                            ? Color::hex($tenant->primary_color)
                            : Color::Amber,
                    ];
                } catch (\Throwable) {
                    return $this->defaults();
                }
            }
        );
    }

    /**
     * Eagerly populate the branding cache for every domain owned by a tenant.
     * Called after provisioning and after branding updates to eliminate the
     * first-request cache-miss penalty.
     */
    public function warm(Tenant $tenant): void
    {
        $domains = $tenant->relationLoaded('domains')
            ? $tenant->domains
            : $tenant->domains()->get();

        $payload = [
            'name'  => $tenant->name ?? config('app.name'),
            'color' => $tenant->primary_color
                ? Color::hex($tenant->primary_color)
                : Color::Amber,
        ];

        foreach ($domains as $domain) {
            Cache::put("tenant_branding:{$domain->domain}", $payload, self::TTL);
        }
    }

    /**
     * Forget the branding cache for every domain owned by a tenant.
     */
    public function invalidate(Tenant $tenant): void
    {
        $domains = $tenant->relationLoaded('domains')
            ? $tenant->domains
            : $tenant->domains()->get();

        foreach ($domains as $domain) {
            Cache::forget("tenant_branding:{$domain->domain}");
        }
    }

    /**
     * Safe fallback branding when no host is available or an error occurs.
     *
     * @return array{name: string, color: array<string,string>|string}
     */
    public function defaults(): array
    {
        return [
            'name'  => config('app.name'),
            'color' => Color::Amber,
        ];
    }
}
