<?php

use App\Models\Classroom;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Student Policy', function () {
    it('allows teacher to view students in assigned classroom', function () {
        // Create teacher user
        $teacherUser = User::factory()->create();
        $staff = Staff::factory()->create(['user_id' => $teacherUser->id]);
        
        // Create classroom assigned to teacher
        $classroom = Classroom::factory()->create(['staff_id' => $staff->id]);
        
        // Create student in that classroom
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Assert teacher can view student
        expect($teacherUser->can('view', $student))->toBeTrue();
    });

    it('prevents teacher from viewing students in other classrooms', function () {
        // Create teacher user
        $teacherUser = User::factory()->create();
        $staff = Staff::factory()->create(['user_id' => $teacherUser->id]);
        
        // Create classroom assigned to teacher
        $teacherClassroom = Classroom::factory()->create(['staff_id' => $staff->id]);
        
        // Create another classroom NOT assigned to teacher
        $otherClassroom = Classroom::factory()->create();
        
        // Create student in other classroom
        $student = Student::factory()->create(['classroom_id' => $otherClassroom->id]);

        // Assert teacher cannot view student
        expect($teacherUser->can('view', $student))->toBeFalse();
    });

    it('allows admin to view all students', function () {
        // Create admin user (no staff record)
        $adminUser = User::factory()->create();
        
        // Create classroom and student
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Assert admin can view student
        expect($adminUser->can('view', $student))->toBeTrue();
    });

    it('allows teacher to update students in assigned classroom', function () {
        // Create teacher user
        $teacherUser = User::factory()->create();
        $staff = Staff::factory()->create(['user_id' => $teacherUser->id]);
        
        // Create classroom assigned to teacher
        $classroom = Classroom::factory()->create(['staff_id' => $staff->id]);
        
        // Create student in that classroom
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Assert teacher can update student
        expect($teacherUser->can('update', $student))->toBeTrue();
    });

    it('prevents teacher from updating students in other classrooms', function () {
        // Create teacher user
        $teacherUser = User::factory()->create();
        $staff = Staff::factory()->create(['user_id' => $teacherUser->id]);
        
        // Create classroom assigned to teacher
        $teacherClassroom = Classroom::factory()->create(['staff_id' => $staff->id]);
        
        // Create another classroom NOT assigned to teacher
        $otherClassroom = Classroom::factory()->create();
        
        // Create student in other classroom
        $student = Student::factory()->create(['classroom_id' => $otherClassroom->id]);

        // Assert teacher cannot update student
        expect($teacherUser->can('update', $student))->toBeFalse();
    });

    it('prevents teacher from deleting students', function () {
        // Create teacher user
        $teacherUser = User::factory()->create();
        $staff = Staff::factory()->create(['user_id' => $teacherUser->id]);
        
        // Create classroom assigned to teacher
        $classroom = Classroom::factory()->create(['staff_id' => $staff->id]);
        
        // Create student in that classroom
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Assert teacher cannot delete student
        expect($teacherUser->can('delete', $student))->toBeFalse();
    });

    it('allows admin to delete students', function () {
        // Create admin user (no staff record)
        $adminUser = User::factory()->create();
        
        // Create classroom and student
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Assert admin can delete student
        expect($adminUser->can('delete', $student))->toBeTrue();
    });
});
