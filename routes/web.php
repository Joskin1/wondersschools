<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Central Domain)
|--------------------------------------------------------------------------
| Routes here are accessible on the central domain (wonders.test).
| All school-facing routes are in routes/tenant.php — they require
| a tenant domain and InitializeTenancyByDomain middleware.
|
*/
Route::get('/dump-resolved-branding', function () {
    $tenant = \App\Models\Tenant::find('chizylite');
    $error = null;
    try {
        $tenant->primary_color = '#1e40af';
        $tenant->save();
    } catch (\Throwable $e) {
        $error = $e->getMessage() . "\n" . $e->getTraceAsString();
    }

    $domain = \Stancl\Tenancy\Database\Models\Domain::on('landlord')
        ->where('domain', 'chizylite-academy.wonders.test')
        ->with('tenant')
        ->first();

    return response()->json([
        'branding' => app(\App\Services\TenantBrandingService::class)->resolve('chizylite-academy.wonders.test'),
        'tenant_primary' => $tenant?->primary_color,
        'domain_tenant' => $domain?->tenant?->toArray(),
        'cache' => Cache::get('tenant_branding:chizylite-academy.wonders.test'),
        'error' => $error,
    ]);
});

