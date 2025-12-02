<?php

use App\Models\Classroom;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Teacher Access', function () {
    it('allows teacher to only see assigned classrooms', function () {
        $user = User::factory()->create();
        $teacher = Staff::factory()->create(['user_id' => $user->id, 'role' => 'teacher']);
        
        $class1 = Classroom::factory()->create(['name' => 'Class 1']);
        $class2 = Classroom::factory()->create(['name' => 'Class 2']);

        // Assign teacher to Class 1
        $class1->teachers()->attach($teacher->id);

        $this->actingAs($user);

        expect(Classroom::where('id', $class1->id)->exists())->toBeTrue();
        expect(Classroom::where('id', $class2->id)->exists())->toBeFalse();
    });

    it('allows teacher to only see students in assigned classrooms', function () {
        $user = User::factory()->create();
        $teacher = Staff::factory()->create(['user_id' => $user->id, 'role' => 'teacher']);
        
        $class1 = Classroom::factory()->create(['name' => 'Class 1']);
        $class2 = Classroom::factory()->create(['name' => 'Class 2']);

        $student1 = Student::factory()->create(['classroom_id' => $class1->id]);
        $student2 = Student::factory()->create(['classroom_id' => $class2->id]);

        // Assign teacher to Class 1
        $class1->teachers()->attach($teacher->id);

        $this->actingAs($user);

        expect(Student::where('id', $student1->id)->exists())->toBeTrue();
        expect(Student::where('id', $student2->id)->exists())->toBeFalse();
    });
});
