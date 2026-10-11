<?php

use App\Models\Classroom;
use App\Models\ClassTeacherAssignment;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = Session::factory()->create([
        'is_active' => true,
        'start_year' => 2026,
        'end_year' => 2027,
    ]);

    $this->term = Term::factory()->create([
        'session_id' => $this->session->id,
        'is_active' => true,
        'name' => 'First Term',
        'order' => 1,
    ]);

    $this->classroom = Classroom::factory()->create(['name' => 'Grade 10']);
    $this->subject = Subject::factory()->create(['name' => 'Physics']);
});

describe('User Scopes and Assignability for Dual Role', function () {

    it('includes active admins and active teachers in assignableTeachers scope', function () {
        $activeAdmin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $inactiveAdmin = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $activeTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $inactiveTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => false]);
        $activeStudent = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $assignable = User::assignableTeachers()->get();

        expect($assignable->contains($activeAdmin))->toBeTrue()
            ->and($assignable->contains($activeTeacher))->toBeTrue()
            ->and($assignable->contains($inactiveAdmin))->toBeFalse()
            ->and($assignable->contains($inactiveTeacher))->toBeFalse()
            ->and($assignable->contains($activeStudent))->toBeFalse();
    });

    it('permits active admins to be assigned via canBeAssigned', function () {
        $activeAdmin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $inactiveAdmin = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $activeTeacher = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
            'registration_completed_at' => now(),
        ]);
        $unregisteredTeacher = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
            'registration_completed_at' => null,
        ]);

        expect($activeAdmin->canBeAssigned())->toBeTrue()
            ->and($inactiveAdmin->canBeAssigned())->toBeFalse()
            ->and($activeTeacher->canBeAssigned())->toBeTrue()
            ->and($unregisteredTeacher->canBeAssigned())->toBeFalse();
    });

});

describe('Assigning Admin to Teaching Roles', function () {

    it('can assign an active admin as a class teacher and detects teaching status', function () {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        expect($admin->isAssignedTeacher())->toBeFalse()
            ->and($admin->isTeachingStaff())->toBeFalse();

        $assignment = ClassTeacherAssignment::create([
            'teacher_id' => $admin->id,
            'class_id' => $this->classroom->id,
            'session_id' => $this->session->id,
        ]);

        expect($assignment)->not->toBeNull()
            ->and($admin->fresh()->isAssignedTeacher())->toBeTrue()
            ->and($admin->fresh()->isTeachingStaff())->toBeTrue();
    });

    it('can assign an active admin as a subject teacher and detects teaching status', function () {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        expect($admin->isAssignedTeacher())->toBeFalse()
            ->and($admin->isTeachingStaff())->toBeFalse();

        $assignment = TeacherSubjectAssignment::create([
            'teacher_id' => $admin->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'status' => 'approved',
        ]);

        expect($assignment)->not->toBeNull()
            ->and($admin->fresh()->isAssignedTeacher())->toBeTrue()
            ->and($admin->fresh()->isTeachingStaff())->toBeTrue();
    });

});

describe('Portal Switcher Visibility Rules', function () {

    it('hides teacher portal switch from pure non-teaching admins', function () {
        $pureAdmin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // Pure admin has no teaching assignments
        expect($pureAdmin->isTeachingStaff())->toBeFalse();
    });

    it('shows teacher portal switch to admins with teaching assignments', function () {
        $teachingAdmin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        ClassTeacherAssignment::create([
            'teacher_id' => $teachingAdmin->id,
            'class_id' => $this->classroom->id,
            'session_id' => $this->session->id,
        ]);

        expect($teachingAdmin->fresh()->isTeachingStaff())->toBeTrue();
    });

    it('hides admin panel switch from pure teachers', function () {
        $pureTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

        // Guard on Teacher portal: auth()->user()?->isAdmin() || auth()->user()?->isSudo()
        expect($pureTeacher->isAdmin())->toBeFalse()
            ->and($pureTeacher->isSudo())->toBeFalse();
    });

    it('shows admin panel switch to teaching admins on the teacher portal', function () {
        $teachingAdmin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // Guard on Teacher portal: auth()->user()?->isAdmin() || auth()->user()?->isSudo()
        expect($teachingAdmin->isAdmin())->toBeTrue();
    });

});
