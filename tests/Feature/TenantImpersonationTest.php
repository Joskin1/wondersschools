<?php

use App\Filament\Sudo\Resources\SchoolResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Stancl\Tenancy\Features\UserImpersonation;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 1. Create a sudo user on the landlord connection
    $this->sudoUser = User::factory()->create([
        'email' => 'joskinjoseph1@gmail.com',
        'role' => 'sudo',
        'is_active' => true,
    ]);

    // 2. Mock Bus to avoid real DB provisioning during tenant creation
    Bus::fake();

    // 3. Create a tenant
    $this->tenant = Tenant::create([
        'id' => 'test-school',
        'name' => 'Test School',
    ]);

    // Create a domain for the tenant
    $this->tenant->domains()->create([
        'domain' => 'test-school.wonders.test',
    ]);
});

test('sudo user can view schools in Sudo panel', function () {
    $this->actingAs($this->sudoUser);

    Livewire::test(SchoolResource\Pages\ManageSchools::class)
        ->assertSuccessful();
});

test('sudo user can trigger impersonation action from SchoolResource table', function () {
    $this->actingAs($this->sudoUser);

    // 1. Create an admin user inside the tenant's DB
    // Since we're using in-memory SQLite and tenancy is mocked or initialized,
    // we initialize tenancy context, create the user, and revert.
    tenancy()->initialize($this->tenant);
    
    $tenantAdmin = User::factory()->create([
        'email' => 'admin@test-school.com',
        'role' => 'admin',
        'is_active' => true,
    ]);
    
    tenancy()->end();

    // 2. Call the impersonate action
    Livewire::test(SchoolResource\Pages\ManageSchools::class)
        ->callTableAction('impersonate', $this->tenant)
        ->assertRedirect();
});

test('impersonation route handles valid token and authenticates tenant admin', function () {
    // 1. Create the tenant admin inside tenant DB
    tenancy()->initialize($this->tenant);
    $tenantAdmin = User::factory()->create([
        'email' => 'admin@test-school.com',
        'role' => 'admin',
        'is_active' => true,
    ]);
    tenancy()->end();

    // 2. Generate the token
    $token = tenancy()->impersonate($this->tenant, $tenantAdmin->id, '/admin', 'web');

    // 3. Visit the impersonation route in the tenant context
    tenancy()->initialize($this->tenant);

    $response = $this->get(route('tenant.impersonate', ['token' => $token->token]));

    // Should redirect to target redirect URL
    $response->assertRedirect('/admin');

    // User should be logged in
    $this->assertAuthenticatedAs($tenantAdmin, 'web');

    // Token should be deleted (cannot be reused)
    expect(DB::connection('landlord')->table('tenant_user_impersonation_tokens')->count())->toBe(0);

    tenancy()->end();
});
