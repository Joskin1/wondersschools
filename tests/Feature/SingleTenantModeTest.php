<?php

use App\Models\Tenant;
use App\Services\TenantBrandingService;
use App\Http\Middleware\InitializeTenancy;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('initializes single tenant mode on non-local, non-central domains', function () {
    // Create a tenant in the landlord database
    $tenant = Tenant::create([
        'id' => 'chizylite',
        'name' => 'Chizylite Academy',
        'primary_color' => '#123456',
        'status' => 'active',
    ]);

    // Set the environment variable for single tenant
    putenv('SINGLE_TENANT_ID=chizylite');

    // Create a request to a simulated Cloudflare tunnel domain
    $request = Request::create('http://chizylite.trycloudflare.com/some-page', 'GET');

    // Instantiate the middleware
    $middleware = app(InitializeTenancy::class);

    $called = false;
    $middleware->handle($request, function ($req) use (&$called) {
        $called = true;
        // Verify that tenancy is initialized in the callback
        expect(tenancy()->initialized)->toBeTrue();
        expect(tenant('id'))->toBe('chizylite');
    });

    expect($called)->toBeTrue();

    // Clean up env
    putenv('SINGLE_TENANT_ID');
});

it('does not initialize single tenant mode on local central domains', function () {
    $tenant = Tenant::create([
        'id' => 'chizylite',
        'name' => 'Chizylite Academy',
        'status' => 'active',
    ]);

    putenv('SINGLE_TENANT_ID=chizylite');

    // Host is local central domain
    $request = Request::create('http://wonders.test/some-page', 'GET');

    $middleware = app(InitializeTenancy::class);

    $called = false;
    $middleware->handle($request, function ($req) use (&$called) {
        $called = true;
        // Verify tenancy is NOT initialized
        expect(tenancy()->initialized)->toBeFalse();
    });

    expect($called)->toBeTrue();

    putenv('SINGLE_TENANT_ID');
});

it('resolves branding directly in single tenant mode', function () {
    $tenant = Tenant::create([
        'id' => 'chizylite',
        'name' => 'Chizylite Academy',
        'primary_color' => '#123456',
        'status' => 'active',
    ]);

    putenv('SINGLE_TENANT_ID=chizylite');

    $brandingService = app(TenantBrandingService::class);
    
    // Resolve for a Cloudflare domain
    $branding = $brandingService->resolve('chizylite.trycloudflare.com');

    expect($branding['name'])->toBe('Chizylite Academy');
    expect($branding['color']->toHex())->toBe('#123456');

    putenv('SINGLE_TENANT_ID');
});
