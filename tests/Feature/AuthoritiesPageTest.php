<?php

use App\Models\User;
use App\Models\SchoolAuthority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create an admin user
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'role' => 'admin',
    ]);
});

test('authorities page loads successfully for admin', function () {
    actingAs($this->admin)
        ->get('/admin/authorities-page')
        ->assertOk()
        ->assertSee('School Authorities');
});

test('authorities page shows empty state when no authorities exist', function () {
    actingAs($this->admin)
        ->get('/admin/authorities-page')
        ->assertOk()
        ->assertSee('No school authorities');
});

test('authorities page displays authorities when they exist', function () {
    // Create test authorities
    SchoolAuthority::factory()->create([
        'name' => 'John Principal',
        'title' => 'Principal',
        'display_order' => 1,
    ]);

    SchoolAuthority::factory()->create([
        'name' => 'Jane Vice Principal',
        'title' => 'Vice Principal',
        'display_order' => 2,
    ]);

    actingAs($this->admin)
        ->get('/admin/authorities-page')
        ->assertOk()
        ->assertSee('John Principal')
        ->assertSee('Principal')
        ->assertSee('Jane Vice Principal')
        ->assertSee('Vice Principal');
});

test('authorities page is not accessible to non-admin users', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    actingAs($user)
        ->get('/admin/authorities-page')
        ->assertForbidden();
});

test('authorities page is not accessible to guests', function () {
    get('/admin/authorities-page')
        ->assertRedirect('/admin/login');
});

test('authorities page has correct navigation icon', function () {
    actingAs($this->admin)
        ->get('/admin/authorities-page')
        ->assertOk();
    
    expect(\App\Filament\Pages\AuthoritiesPage::getNavigationIcon())
        ->toBe('heroicon-o-identification');
});

test('authorities page has correct navigation label', function () {
    expect(\App\Filament\Pages\AuthoritiesPage::getNavigationLabel())
        ->toBe('Authorities');
});
