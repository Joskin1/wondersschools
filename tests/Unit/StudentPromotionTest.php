<?php

use App\Models\Student;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('student can be promoted from Reception to Year 1', function () {
    $reception = Classroom::factory()->create(['name' => 'Reception']);
    $year1 = Classroom::factory()->create(['name' => 'Year 1']);
    
    $student = Student::factory()->create([
        'classroom_id' => $reception->id,
        'is_graduated' => false,
    ]);

    $result = $student->promoteToNextClassroom();

    expect($result)->toBeTrue();
    expect($student->fresh()->classroom_id)->toBe($year1->id);
});

test('student can be promoted through all years', function () {
    $classrooms = [
        'Reception' => Classroom::factory()->create(['name' => 'Reception']),
        'Year 1' => Classroom::factory()->create(['name' => 'Year 1']),
        'Year 2' => Classroom::factory()->create(['name' => 'Year 2']),
        'Year 3' => Classroom::factory()->create(['name' => 'Year 3']),
        'Year 4' => Classroom::factory()->create(['name' => 'Year 4']),
        'Year 5' => Classroom::factory()->create(['name' => 'Year 5']),
        'Year 6' => Classroom::factory()->create(['name' => 'Year 6']),
    ];
    
    $student = Student::factory()->create([
        'classroom_id' => $classrooms['Reception']->id,
        'is_graduated' => false,
    ]);

    // Promote through each year
    $student->promoteToNextClassroom();
    $student = $student->fresh();
    expect($student->classroom->name)->toBe('Year 1');
    
    $student->promoteToNextClassroom();
    $student = $student->fresh();
    expect($student->classroom->name)->toBe('Year 2');
    
    $student->promoteToNextClassroom();
    $student = $student->fresh();
    expect($student->classroom->name)->toBe('Year 3');
    
    $student->promoteToNextClassroom();
    $student = $student->fresh();
    expect($student->classroom->name)->toBe('Year 4');
    
    $student->promoteToNextClassroom();
    $student = $student->fresh();
    expect($student->classroom->name)->toBe('Year 5');
    
    $student->promoteToNextClassroom();
    $student = $student->fresh();
    expect($student->classroom->name)->toBe('Year 6');
});

test('student in Year 6 is marked as graduated when promoted', function () {
    $year6 = Classroom::factory()->create(['name' => 'Year 6']);
    
    $student = Student::factory()->create([
        'classroom_id' => $year6->id,
        'is_graduated' => false,
    ]);

    $result = $student->promoteToNextClassroom();

    expect($result)->toBeTrue();
    expect($student->fresh()->is_graduated)->toBe(1); // SQLite stores boolean as integer
});

test('graduated student cannot be promoted', function () {
    $year6 = Classroom::factory()->create(['name' => 'Year 6']);
    
    $student = Student::factory()->create([
        'classroom_id' => $year6->id,
        'is_graduated' => true,
    ]);

    $result = $student->promoteToNextClassroom();

    expect($result)->toBeFalse();
});

test('student can be manually marked as graduated', function () {
    $student = Student::factory()->create(['is_graduated' => false]);

    $student->markAsGraduated();

    expect($student->fresh()->is_graduated)->toBe(1); // SQLite stores boolean as integer
});

test('student without classroom cannot be promoted', function () {
    $student = Student::factory()->create([
        'classroom_id' => null,
        'is_graduated' => false,
    ]);

    $result = $student->promoteToNextClassroom();

    expect($result)->toBeFalse();
});

test('student promotion fails if next classroom does not exist', function () {
    $reception = Classroom::factory()->create(['name' => 'Reception']);
    // Don't create Year 1 classroom
    
    $student = Student::factory()->create([
        'classroom_id' => $reception->id,
        'is_graduated' => false,
    ]);

    $result = $student->promoteToNextClassroom();

    expect($result)->toBeFalse();
    expect($student->fresh()->classroom_id)->toBe($reception->id);
});
