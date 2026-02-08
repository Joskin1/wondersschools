<?php

use App\Models\User;
use App\Models\AcademicSession;
use App\Models\Term;
use App\Models\Classroom;
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

    // Create test data
    $this->session = AcademicSession::factory()->create(['name' => '2023/2024']);
    $this->term = Term::factory()->create(['name' => 'First Term']);
    $this->classroom = Classroom::factory()->create(['name' => 'JSS 1A']);
});

test('generate results page loads successfully for admin', function () {
    actingAs($this->admin)
        ->get('/admin/generate-results-page')
        ->assertOk()
        ->assertSee('Generate Student Results');
});

test('generate results page displays form fields', function () {
    actingAs($this->admin)
        ->get('/admin/generate-results-page')
        ->assertOk()
        ->assertSee('Academic Session')
        ->assertSee('Term')
        ->assertSee('Classroom')
        ->assertSee('Regenerate Existing Results');
});

test('generate results page shows session options', function () {
    actingAs($this->admin)
        ->get('/admin/generate-results-page')
        ->assertOk()
        ->assertSee($this->session->name);
});

test('generate results page shows term options', function () {
    actingAs($this->admin)
        ->get('/admin/generate-results-page')
        ->assertOk()
        ->assertSee($this->term->name);
});

test('generate results page shows classroom options', function () {
    actingAs($this->admin)
        ->get('/admin/generate-results-page')
        ->assertOk()
        ->assertSee($this->classroom->name);
});

test('generate results page has generate button', function () {
    actingAs($this->admin)
        ->get('/admin/generate-results-page')
        ->assertOk()
        ->assertSee('Generate Results');
});

test('generate results page is not accessible to non-admin users', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    actingAs($user)
        ->get('/admin/generate-results-page')
        ->assertForbidden();
});

test('generate results page is not accessible to guests', function () {
    get('/admin/generate-results-page')
        ->assertRedirect('/admin/login');
});

test('generate results page has correct navigation label', function () {
    expect(\App\Filament\Pages\GenerateResultsPage::getNavigationLabel())
        ->toBe('Generate Results');
});

test('generate results page has correct title', function () {
    expect(\App\Filament\Pages\GenerateResultsPage::getTitle())
        ->toBe('Generate Student Results');
});
