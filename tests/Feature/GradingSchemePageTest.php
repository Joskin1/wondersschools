<?php

use App\Models\User;
use App\Models\Grading;
use App\Models\Subject;
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

test('grading scheme page loads successfully for admin', function () {
    actingAs($this->admin)
        ->get('/admin/grading-scheme-page')
        ->assertOk()
        ->assertSee('Grading Scheme Management');
});

test('grading scheme page shows empty state when no gradings exist', function () {
    actingAs($this->admin)
        ->get('/admin/grading-scheme-page')
        ->assertOk()
        ->assertSee('No gradings');
});

test('grading scheme page displays gradings when they exist', function () {
    // Create test gradings
    Grading::factory()->create([
        'letter' => 'A',
        'lower_bound' => 70,
        'upper_bound' => 100,
        'remark' => 'Excellent',
    ]);

    Grading::factory()->create([
        'letter' => 'B',
        'lower_bound' => 60,
        'upper_bound' => 69,
        'remark' => 'Very Good',
    ]);

    actingAs($this->admin)
        ->get('/admin/grading-scheme-page')
        ->assertOk()
        ->assertSee('A')
        ->assertSee('Excellent')
        ->assertSee('B')
        ->assertSee('Very Good')
        ->assertSee('70')
        ->assertSee('60');
});

test('grading scheme page displays subject-specific gradings', function () {
    $subject = Subject::factory()->create(['name' => 'Mathematics']);
    
    Grading::factory()->create([
        'letter' => 'A',
        'lower_bound' => 75,
        'upper_bound' => 100,
        'remark' => 'Excellent',
        'subject_id' => $subject->id,
    ]);

    actingAs($this->admin)
        ->get('/admin/grading-scheme-page')
        ->assertOk()
        ->assertSee('Mathematics');
});

test('grading scheme page shows global gradings without subject', function () {
    Grading::factory()->create([
        'letter' => 'A',
        'lower_bound' => 70,
        'upper_bound' => 100,
        'remark' => 'Excellent',
        'subject_id' => null,
    ]);

    actingAs($this->admin)
        ->get('/admin/grading-scheme-page')
        ->assertOk()
        ->assertSee('Global');
});

test('grading scheme page has table columns', function () {
    actingAs($this->admin)
        ->get('/admin/grading-scheme-page')
        ->assertOk()
        ->assertSee('Grade')
        ->assertSee('Min Score (%)')
        ->assertSee('Max Score (%)')
        ->assertSee('Remark')
        ->assertSee('Subject');
});

test('grading scheme page is not accessible to non-admin users', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    actingAs($user)
        ->get('/admin/grading-scheme-page')
        ->assertForbidden();
});

test('grading scheme page is not accessible to guests', function () {
    get('/admin/grading-scheme-page')
        ->assertRedirect('/admin/login');
});

test('grading scheme page has correct navigation label', function () {
    expect(\App\Filament\Pages\GradingSchemePage::getNavigationLabel())
        ->toBe('Grading Scheme');
});

test('grading scheme page has correct title', function () {
    expect(\App\Filament\Pages\GradingSchemePage::getTitle())
        ->toBe('Grading Scheme Management');
});
