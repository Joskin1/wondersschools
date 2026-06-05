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
 *  - app.url                    → https://<tenant-domain>
 *  - app.name                   → school_name setting (fallback: tenant->name)
 *  - mail.from.address          → noreply@<tenant-domain>
 *  - app.tenant_primary_color   → tenant->primary_color (from landlord JSON data)
 *  - app.tenant_secondary_color → secondary_color setting
 *  - app.tenant_accent_color    → accent_color setting
 *  - app.tenant_layout_style    → layout_style setting
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

        // Override the public disk URL to route to the tenant-specific public directory.
        $suffix = config('tenancy.filesystem.suffix_base', 'tenant') . $tenant->getTenantKey();
        config(['filesystems.disks.public.url' => config('app.url') . '/storage/' . $suffix]);
        \Illuminate\Support\Facades\Storage::forgetDisk('public');

        // The settings table may not exist yet during MigrateDatabase (the DB
        // was just created). Guard against missing table so the bootstrapper
        // never blocks the migration pipeline.
        try {
            $settings = Setting::whereIn('key', [
                'school_name', 'secondary_color', 'accent_color', 'layout_style',
            ])->pluck('value', 'key');
        } catch (\Throwable) {
            $settings = collect();
        }

        config(['app.name' => $settings->get('school_name') ?? $tenant->name ?? config('app.name')]);

        // primary_color comes from the landlord DB JSON — no extra query needed.
        config(['app.tenant_primary_color'   => $tenant->primary_color ?? '#f59e0b']);
        config(['app.tenant_secondary_color' => $settings->get('secondary_color', '#1e293b')]);
        config(['app.tenant_accent_color'    => $settings->get('accent_color', '#f59e0b')]);
        config(['app.tenant_layout_style'    => $settings->get('layout_style', 'standard')]);
    }

    public function revert(): void
    {
        config(['app.url'                    => env('APP_URL')]);
        config(['app.name'                   => env('APP_NAME', 'Laravel')]);
        config(['mail.from.address'          => env('MAIL_FROM_ADDRESS')]);
        config(['app.tenant_primary_color'   => null]);
        config(['app.tenant_secondary_color' => null]);
        config(['app.tenant_accent_color'    => null]);
        config(['app.tenant_layout_style'    => null]);

        // Revert the public disk URL back to landlord configuration.
        config(['filesystems.disks.public.url' => env('APP_URL') . '/storage']);
        \Illuminate\Support\Facades\Storage::forgetDisk('public');
    }
}
