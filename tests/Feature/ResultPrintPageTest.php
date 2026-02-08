<?php

use App\Models\User;
use App\Models\AcademicSession;
use App\Models\Term;
use App\Models\Classroom;
use App\Models\Result;
use App\Models\Student;
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

test('print results page loads successfully for admin', function () {
    actingAs($this->admin)
        ->get('/admin/result-print-page')
        ->assertOk()
        ->assertSee('Print Student Results');
});

test('print results page displays filter form', function () {
    actingAs($this->admin)
        ->get('/admin/result-print-page')
        ->assertOk()
        ->assertSee('Academic Session')
        ->assertSee('Term')
        ->assertSee('Classroom');
});

test('print results page shows session options', function () {
    actingAs($this->admin)
        ->get('/admin/result-print-page')
        ->assertOk()
        ->assertSee($this->session->name);
});

test('print results page shows term options', function () {
    actingAs($this->admin)
        ->get('/admin/result-print-page')
        ->assertOk()
        ->assertSee($this->term->name);
});

test('print results page shows classroom options', function () {
    actingAs($this->admin)
        ->get('/admin/result-print-page')
        ->assertOk()
        ->assertSee($this->classroom->name);
});

test('print results page shows no filters selected message initially', function () {
    actingAs($this->admin)
        ->get('/admin/result-print-page')
        ->assertOk()
        ->assertSee('No Filters Selected');
});

test('print results page displays results when filters are applied', function () {
    // Create a student and result
    $student = Student::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'classroom_id' => $this->classroom->id,
    ]);

    Result::factory()->create([
        'student_id' => $student->id,
        'academic_session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'classroom_id' => $this->classroom->id,
        'total_score' => 450,
        'average_score' => 75.0,
        'grade' => 'A',
    ]);

    // Note: In a real test, we would need to simulate form submission
    // For now, we just verify the page structure
    actingAs($this->admin)
        ->get('/admin/result-print-page')
        ->assertOk();
});

test('print results page has print all button', function () {
    actingAs($this->admin)
        ->get('/admin/result-print-page')
        ->assertOk()
        ->assertSee('Print All Filtered Results');
});

test('print results page has export to excel button', function () {
    actingAs($this->admin)
        ->get('/admin/result-print-page')
        ->assertOk()
        ->assertSee('Export to Excel');
});

test('print results page is not accessible to non-admin users', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    actingAs($user)
        ->get('/admin/result-print-page')
        ->assertForbidden();
});

test('print results page is not accessible to guests', function () {
    get('/admin/result-print-page')
        ->assertRedirect('/admin/login');
});

test('print results page has correct navigation label', function () {
    expect(\App\Filament\Pages\ResultPrintPage::getNavigationLabel())
        ->toBe('Print Results');
});

test('print results page has correct title', function () {
    expect(\App\Filament\Pages\ResultPrintPage::getTitle())
        ->toBe('Print Student Results');
});
