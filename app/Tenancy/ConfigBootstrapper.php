<?php

namespace App\Tenancy;

use App\Models\Setting;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * TIMS-inspired ConfigBootstrapper.
 *
 * Runs on every TenancyInitialized event (AFTER DatabaseTenancyBootstrapper,
 * so the tenant DB is already switched when we read the settings table).
 *
 * Sets per-tenant:
 *  - app.url          → https://<tenant-domain>
 *  - app.name         → school_name setting (fallback: tenant->name)
 *  - mail.from.address → noreply@<tenant-domain>
 *  - app.tenant_primary_color → tenant->primary_color (from landlord JSON data)
 */
class ConfigBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        $domain = $tenant->domains->first()?->domain;

        if ($domain) {
            $scheme = app()->environment('local') ? 'http' : 'https';
            config(['app.url'              => "{$scheme}://{$domain}"]);
            config(['mail.from.address'    => "noreply@{$domain}"]);
            \Illuminate\Support\Facades\URL::forceRootUrl("{$scheme}://{$domain}");
        }

        // The settings table may not exist yet during MigrateDatabase (the DB
        // was just created). Guard against missing table so the bootstrapper
        // never blocks the migration pipeline.
        try {
            $schoolName = Setting::where('key', 'school_name')->value('value');
        } catch (\Throwable) {
            $schoolName = null;
        }
        config(['app.name' => $schoolName ?? $tenant->name ?? config('app.name')]);

        // primary_color comes from the landlord DB JSON — no extra query needed.
        config(['app.tenant_primary_color' => $tenant->primary_color ?? '#f59e0b']);
    }

    public function revert(): void
    {
        config(['app.url'                  => env('APP_URL')]);
        config(['app.name'                 => env('APP_NAME', 'Laravel')]);
        config(['mail.from.address'        => env('MAIL_FROM_ADDRESS')]);
        config(['app.tenant_primary_color' => null]);
    }
}
