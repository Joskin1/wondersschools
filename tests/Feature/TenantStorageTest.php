<?php

use App\Models\Tenant;
use App\Tenancy\ConfigBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('overrides public disk URL dynamically on bootstrapping', function () {
    Bus::fake();

    $tenant = Tenant::create([
        'id'   => 'test-school',
        'name' => 'Test School',
    ]);
    $tenant->domains()->create(['domain' => 'test.wonders.test']);

    // Bootstrap tenant config
    (new ConfigBootstrapper())->bootstrap($tenant->fresh(['domains']));

    // Assert URL matches tenant structure
    $scheme = app()->environment('local') ? 'http' : 'https';
    $expectedUrl = "{$scheme}://test.wonders.test/storage/tenanttest-school";
    
    expect(config('filesystems.disks.public.url'))->toBe($expectedUrl)
        ->and(Storage::disk('public')->url('logo.png'))->toBe("{$expectedUrl}/logo.png");

    // Revert and assert it goes back to landlord configuration
    (new ConfigBootstrapper())->revert();

    expect(config('filesystems.disks.public.url'))->toBe(env('APP_URL') . '/storage')
        ->and(Storage::disk('public')->url('logo.png'))->toBe(env('APP_URL') . '/storage/logo.png');
});

it('creates the tenant storage symbolic link via command', function () {
    Bus::fake();

    $tenantId = 'symlink-school';
    $tenant = Tenant::create([
        'id'   => $tenantId,
        'name' => 'Symlink School',
    ]);

    $suffix = 'tenant' . $tenantId;
    $targetPath = storage_path("{$suffix}/app/public");
    $linkPath = storage_path("app/public/{$suffix}");

    // Clean up if they already exist
    if (is_link($linkPath) || file_exists($linkPath)) {
        unlink($linkPath);
    }
    if (is_dir($targetPath)) {
        // Remove subdirs if any
        if (is_dir($targetPath . '/logos')) {
            rmdir($targetPath . '/logos');
        }
        rmdir($targetPath);
    }

    // Run command
    Artisan::call('tenants:link');

    // Assert symlink was created correctly
    expect(is_dir($targetPath))->toBeTrue()
        ->and(is_link($linkPath))->toBeTrue()
        ->and(readlink($linkPath))->toBe($targetPath);

    // Clean up
    unlink($linkPath);
    rmdir($targetPath);
});
