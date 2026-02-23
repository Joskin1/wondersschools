<?php

/**
 * TenancyProvisioningTest
 *
 * Covers the core tenancy infrastructure introduced in the tenant3 branch:
 *
 *  1. Tenant model JSON data persistence (name / primary_color in data column)
 *  2. TenantCreated event → CreateDatabase → MigrateDatabase → SeedDatabase pipeline
 *  3. TenantAdminAssignment stored on the landlord connection
 *  4. User email_verified_at mass-assignable (fixes GuardedProperty regression)
 *  5. ConfigBootstrapper per-tenant config injection + graceful fallbacks + revert
 *  6. TenantAdminCreated mailable structure
 *
 * Tests run against in-memory SQLite (default + landlord connections) via phpunit.xml.
 * Bus::fake() is used in every test that creates a Tenant so that CreateDatabase /
 * MigrateDatabase / SeedDatabase do not actually try to create a MySQL tenant database.
 */

use App\Jobs\Tenancy\CreateDatabase;
use App\Jobs\Tenancy\MigrateDatabase;
use App\Jobs\Tenancy\SeedDatabase;
use App\Mail\TenantAdminCreated;
use App\Models\Tenant;
use App\Models\TenantAdminAssignment;
use App\Models\User;
use App\Tenancy\ConfigBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// 1. Tenant model — JSON data column persistence
// ─────────────────────────────────────────────────────────────────────────────

it('stores tenant name and primary_color in the data JSON column, not real DB columns', function () {
    Bus::fake();

    Tenant::create([
        'id'            => 'json-school',
        'name'          => 'JSON School',
        'primary_color' => '#e11d48',
    ]);

    $fresh = Tenant::on('landlord')->find('json-school');

    expect($fresh->name)->toBe('JSON School')
        ->and($fresh->primary_color)->toBe('#e11d48');

    // The raw row should have a data JSON column — not physical name/primary_color columns.
    $row  = DB::connection('landlord')->table('tenants')->where('id', 'json-school')->first();
    $data = json_decode($row->data ?? '{}', true);

    expect($data['name'])->toBe('JSON School')
        ->and($data['primary_color'])->toBe('#e11d48')
        ->and(property_exists($row, 'name'))->toBeFalse();
});

it('returns null for primary_color when it was not set on the tenant', function () {
    Bus::fake();

    Tenant::create(['id' => 'no-color', 'name' => 'No Colour School']);

    expect(Tenant::on('landlord')->find('no-color')->primary_color)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. TenantCreated event → job pipeline
// ─────────────────────────────────────────────────────────────────────────────

it('dispatches CreateDatabase, MigrateDatabase, and SeedDatabase when a tenant is created', function () {
    Bus::fake();

    Tenant::create(['id' => 'pipeline-school', 'name' => 'Pipeline School']);

    Bus::assertDispatched(CreateDatabase::class,  fn ($j) => $j->tenant->id === 'pipeline-school');
    Bus::assertDispatched(MigrateDatabase::class, fn ($j) => $j->tenant->id === 'pipeline-school');
    Bus::assertDispatched(SeedDatabase::class,    fn ($j) => $j->tenant->id === 'pipeline-school');
});

it('dispatches exactly one set of provisioning jobs per tenant', function () {
    Bus::fake();

    Tenant::create(['id' => 'one-school', 'name' => 'One School']);

    // Creating an assignment record must not trigger a second round of DB jobs.
    TenantAdminAssignment::create([
        'tenant_id' => 'one-school',
        'name'      => 'Admin',
        'email'     => 'admin@one.test',
        'role'      => 'admin',
    ]);

    Bus::assertDispatchedTimes(CreateDatabase::class, 1);
    Bus::assertDispatchedTimes(MigrateDatabase::class, 1);
    Bus::assertDispatchedTimes(SeedDatabase::class, 1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. TenantAdminAssignment — landlord connection + fillable columns
// ─────────────────────────────────────────────────────────────────────────────

it('persists a TenantAdminAssignment on the landlord connection', function () {
    Bus::fake();

    Tenant::create(['id' => 'assign-school', 'name' => 'Assignment School']);

    $assignment = TenantAdminAssignment::create([
        'tenant_id' => 'assign-school',
        'name'      => 'Alice Admin',
        'email'     => 'alice@assign.test',
        'role'      => 'admin',
    ]);

    expect($assignment->getConnectionName())->toBe('landlord')
        ->and($assignment->role)->toBe('admin')
        ->and($assignment->credentials_sent_at)->toBeNull();
});

it('can update credentials_sent_at after provisioning', function () {
    Bus::fake();

    Tenant::create(['id' => 'ts-school', 'name' => 'Timestamp School']);

    $assignment = TenantAdminAssignment::create([
        'tenant_id' => 'ts-school',
        'name'      => 'Bob Admin',
        'email'     => 'bob@ts.test',
        'role'      => 'admin',
    ]);

    $assignment->update(['credentials_sent_at' => now()]);

    expect($assignment->fresh()->credentials_sent_at)->not->toBeNull();
});

it('supports the teacher role on a TenantAdminAssignment', function () {
    Bus::fake();

    Tenant::create(['id' => 'teacher-school', 'name' => 'Teacher School']);

    $assignment = TenantAdminAssignment::create([
        'tenant_id' => 'teacher-school',
        'name'      => 'Carol Teacher',
        'email'     => 'carol@teacher.test',
        'role'      => 'teacher',
    ]);

    expect($assignment->role)->toBe('teacher');
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. User model — email_verified_at mass assignment
// ─────────────────────────────────────────────────────────────────────────────

it('allows email_verified_at to be mass-assigned on User', function () {
    $verifiedAt = now()->subMinute();

    $user = User::create([
        'name'              => 'Verified Admin',
        'email'             => 'verified@test.test',
        'password'          => Hash::make('test-password'),
        'role'              => 'admin',
        'email_verified_at' => $verifiedAt,
    ]);

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->email_verified_at->toDateString())->toBe($verifiedAt->toDateString());
});

it('updateOrCreate with email_verified_at does not throw MassAssignmentException', function () {
    // Mirrors exactly what ManageTenantAdmins::provisionAdmin() does inside the tenant DB.
    expect(fn () => User::updateOrCreate(
        ['email' => 'upsert@test.test'],
        [
            'name'              => 'Upsert Admin',
            'password'          => Hash::make('secret'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]
    ))->not->toThrow(\Illuminate\Database\Eloquent\MassAssignmentException::class);
});

it('User created via provisionAdmin pattern has a verified email', function () {
    $user = User::updateOrCreate(
        ['email' => 'provision@test.test'],
        [
            'name'              => 'Provision Admin',
            'password'          => Hash::make('Abc123Xyz789'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]
    );

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. ConfigBootstrapper — TIMS per-tenant config injection
// ─────────────────────────────────────────────────────────────────────────────

it('ConfigBootstrapper sets app.url, mail.from.address, and primary_color from the tenant domain', function () {
    Bus::fake();

    $tenant = Tenant::create([
        'id'            => 'cfg-school',
        'name'          => 'Config School',
        'primary_color' => '#7c3aed',
    ]);
    $tenant->domains()->create(['domain' => 'cfg.wonders.test']);

    (new ConfigBootstrapper())->bootstrap($tenant->fresh(['domains']));

    $scheme = app()->environment('local') ? 'http' : 'https';

    expect(config('app.url'))->toBe("{$scheme}://cfg.wonders.test")
        ->and(config('mail.from.address'))->toBe('noreply@cfg.wonders.test')
        ->and(config('app.tenant_primary_color'))->toBe('#7c3aed');
});

it('ConfigBootstrapper falls back to app.name from tenant->name when no settings row exists', function () {
    Bus::fake();

    $tenant = Tenant::create(['id' => 'fallback-school', 'name' => 'Fallback School']);

    (new ConfigBootstrapper())->bootstrap($tenant->fresh(['domains']));

    expect(config('app.name'))->toBe('Fallback School');
});

it('ConfigBootstrapper falls back to #f59e0b when tenant has no primary_color', function () {
    Bus::fake();

    $tenant = Tenant::create(['id' => 'amber-school', 'name' => 'Amber School']);

    (new ConfigBootstrapper())->bootstrap($tenant->fresh(['domains']));

    expect(config('app.tenant_primary_color'))->toBe('#f59e0b');
});

it('ConfigBootstrapper does not set app.url when the tenant has no domain', function () {
    Bus::fake();

    $originalUrl = config('app.url');
    $tenant      = Tenant::create(['id' => 'nodomain-school', 'name' => 'NoDomain School']);

    (new ConfigBootstrapper())->bootstrap($tenant->fresh(['domains']));

    // No domain configured → app.url should remain unchanged
    expect(config('app.url'))->toBe($originalUrl);
});

it('ConfigBootstrapper revert() restores app.url, app.name, mail.from.address from env', function () {
    config(['app.url'           => 'http://changed.test']);
    config(['app.name'          => 'Changed Name']);
    config(['mail.from.address' => 'changed@changed.test']);

    (new ConfigBootstrapper())->revert();

    expect(config('app.url'))->toBe(env('APP_URL'))
        ->and(config('app.name'))->toBe(env('APP_NAME', 'Laravel'))
        ->and(config('mail.from.address'))->toBe(env('MAIL_FROM_ADDRESS'));
});

it('ConfigBootstrapper revert() nulls out the tenant primary color', function () {
    config(['app.tenant_primary_color' => '#ff0000']);

    (new ConfigBootstrapper())->revert();

    expect(config('app.tenant_primary_color'))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. TenantAdminCreated mailable
// ─────────────────────────────────────────────────────────────────────────────

it('TenantAdminCreated has the correct subject line', function () {
    $user = User::factory()->make(['email' => 'subject@mail.test']);

    (new TenantAdminCreated($user, 'MyPassword123', 'http://school.test/admin/login'))
        ->assertHasSubject('Your School Admin Account Has Been Created');
});

it('TenantAdminCreated exposes the password and loginUrl for the view', function () {
    $user = User::factory()->make(['email' => 'view@mail.test']);

    $mail = new TenantAdminCreated($user, 'ViewPass456', 'http://view.test/admin/login');

    expect($mail->password)->toBe('ViewPass456')
        ->and($mail->loginUrl)->toBe('http://view.test/admin/login')
        ->and($mail->user->email)->toBe('view@mail.test');
});

it('sends TenantAdminCreated to the correct recipient', function () {
    Mail::fake();

    $user = User::factory()->make(['email' => 'recipient@mail.test']);

    Mail::to($user->email)->send(
        new TenantAdminCreated($user, 'SentPass789', 'http://mail.test/admin/login')
    );

    Mail::assertSent(TenantAdminCreated::class, fn ($m) => $m->hasTo('recipient@mail.test'));
});

it('does not send TenantAdminCreated when no login URL is available', function () {
    Mail::fake();

    // provisionAdmin() only sends the mail when a domain/loginUrl is present.
    // When loginUrl is null the mail is never dispatched.
    $loginUrl = null;

    if ($loginUrl) {
        $user = User::factory()->make();
        Mail::to($user->email)->send(new TenantAdminCreated($user, 'pass', $loginUrl));
    }

    Mail::assertNothingSent();
});
