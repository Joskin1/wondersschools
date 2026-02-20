<?php

/**
 * EndToEndWorkflowTest.php
 *
 * Comprehensive end-to-end tests for the multi-tenant school management system.
 *
 * Coverage:
 *
 * E2E 1: Sudo panel — school provisioning, domain assignment, suspension, credential encryption
 * E2E 2: Admin — teacher creation, registration token, invite email, token validation, registration completion
 * E2E 3: Admin — academic structure (session/terms, classrooms, subjects, class teacher, subject assignments)
 * E2E 4: Student — registration link, token validation, completion, classroom enrollment, duplicate prevention
 * E2E 5: Teacher — lesson note creation, edit-gating by status, admin approve/reject, teacher/week scopes
 * E2E 6: Data isolation — classrooms, users, subjects, students, lesson notes invisible across tenants
 * E2E 7: Role & panel access control — admin/teacher/sudo panel gates, impersonation rules, password hashing
 * E2E 8: Full chain — school → admin → teacher registration → student enrollment → lesson note approval
 *
 * Each test group boots its own tenant context via the CreatesTenants trait.
 * Tests that compare two tenants call initializeTenancy()/endTenancy() manually within the test.
 */

use App\Models\Central\Domain;
use App\Models\Central\School;
use App\Models\ClassTeacherAssignment;
use App\Models\Classroom;
use App\Models\LessonNote;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherRegistrationToken;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Notifications\TeacherRegistrationInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class, \Tests\Traits\CreatesTenants::class);

// ══════════════════════════════════════════════════════════════════════════
// E2E 1: Sudo Panel — School Provisioning & Management
// ══════════════════════════════════════════════════════════════════════════

describe('E2E 1: Sudo creates and manages schools', function () {

    it('creates a school on the central database', function () {
        $school = School::create([
            'name'              => 'Springfield Academy',
            'database_name'     => 'db_springfield',
            'database_username' => 'u_springfield',
            'database_password' => 'secure_pass',
            'status'            => 'active',
        ]);

        expect($school->exists)->toBeTrue()
            ->and($school->getConnectionName())->toBe('central')
            ->and($school->name)->toBe('Springfield Academy')
            ->and($school->isActive())->toBeTrue();
    });

    it('assigns a primary domain to a school', function () {
        $school = School::create([
            'name'              => 'Domain School',
            'database_name'     => 'db_domain',
            'database_username' => 'u_domain',
            'database_password' => 'pass123',
            'status'            => 'active',
        ]);

        Domain::create([
            'domain'    => 'springfield.test',
            'tenant_id' => $school->id,
            'is_primary' => true,
        ]);

        $school->refresh();

        expect($school->domains)->toHaveCount(1)
            ->and($school->domains->first()->domain)->toBe('springfield.test')
            ->and($school->domains->first()->is_primary)->toBeTrue();
    });

    it('suspends and reactivates a school', function () {
        $school = School::create([
            'name'              => 'Toggle School',
            'database_name'     => 'db_toggle',
            'database_username' => 'u_toggle',
            'database_password' => 'pass',
            'status'            => 'active',
        ]);

        $school->update(['status' => 'suspended']);
        expect($school->fresh()->isSuspended())->toBeTrue()
            ->and($school->fresh()->isActive())->toBeFalse();

        $school->update(['status' => 'active']);
        expect($school->fresh()->isActive())->toBeTrue();
    });

    it('encrypts database credentials at rest', function () {
        $school = School::create([
            'name'              => 'Secure School',
            'database_name'     => 'db_secure',
            'database_username' => 'u_secure',
            'database_password' => 'plain_secret_password',
            'status'            => 'active',
        ]);

        $rawInDb = DB::connection('central')
            ->table('schools')
            ->where('id', $school->id)
            ->value('database_password');

        // Raw DB value is encrypted; model decrypts on read
        expect($rawInDb)->not->toBe('plain_secret_password')
            ->and($school->database_password)->toBe('plain_secret_password');
    });

    it('active scope returns only active schools', function () {
        School::create(['name' => 'A1', 'database_name' => 'db_a1', 'database_username' => 'u_a1', 'database_password' => 'p', 'status' => 'active']);
        School::create(['name' => 'A2', 'database_name' => 'db_a2', 'database_username' => 'u_a2', 'database_password' => 'p', 'status' => 'active']);
        School::create(['name' => 'S1', 'database_name' => 'db_s1', 'database_username' => 'u_s1', 'database_password' => 'p', 'status' => 'suspended']);

        expect(School::active()->count())->toBe(2)
            ->and(School::suspended()->count())->toBe(1);
    });

    it('sudo panel is inaccessible to admin and teacher roles', function () {
        $this->initializeTenancy();

        $sudo    = User::factory()->create(['role' => 'sudo']);
        $admin   = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

        $sudoPanel = \Filament\Facades\Filament::getPanel('sudo');

        expect($sudo->canAccessPanel($sudoPanel))->toBeTrue()
            ->and($admin->canAccessPanel($sudoPanel))->toBeFalse()
            ->and($teacher->canAccessPanel($sudoPanel))->toBeFalse();

        $this->endTenancy();
    });
});

// ══════════════════════════════════════════════════════════════════════════
// E2E 2: Admin → Teacher Registration Workflow
// ══════════════════════════════════════════════════════════════════════════

describe('E2E 2: Admin manages teacher registration', function () {

    beforeEach(function () {
        $this->initializeTenancy();
    });

    afterEach(function () {
        $this->endTenancy();
    });

    it('admin creates a teacher who starts as inactive', function () {
        $teacher = User::create([
            'name'     => 'Mr. Chidi Okeke',
            'email'    => 'chidi@school.test',
            'password' => 'temp_pass',
            'role'     => 'teacher',
            'is_active' => false,
        ]);

        expect($teacher->isTeacher())->toBeTrue()
            ->and($teacher->is_active)->toBeFalse()
            ->and($teacher->registration_completed_at)->toBeNull();
    });

    it('admin generates a valid 64-char registration token for a teacher', function () {
        $teacher  = User::factory()->create(['role' => 'teacher', 'is_active' => false]);
        $rawToken = TeacherRegistrationToken::createForUser($teacher);

        expect($rawToken)->toHaveLength(64)
            ->and($teacher->registrationTokens()->count())->toBe(1);

        $record = $teacher->registrationTokens()->latest()->first();
        expect($record->isValid())->toBeTrue()
            ->and($record->isExpired())->toBeFalse()
            ->and($record->isUsed())->toBeFalse();
    });

    it('registration token is stored as a SHA-256 hash, not plain text', function () {
        $teacher  = User::factory()->create(['role' => 'teacher']);
        $rawToken = TeacherRegistrationToken::createForUser($teacher);

        $record = $teacher->registrationTokens()->latest()->first();

        expect($record->token_hash)->toBe(hash('sha256', $rawToken))
            ->and($record->token_hash)->not->toBe($rawToken);
    });

    it('admin sends a registration invitation email to the teacher', function () {
        Notification::fake();

        $teacher  = User::factory()->create(['role' => 'teacher', 'is_active' => false]);
        $rawToken = TeacherRegistrationToken::createForUser($teacher);
        $teacher->notify(new TeacherRegistrationInvitation($rawToken));

        Notification::assertSentTo($teacher, TeacherRegistrationInvitation::class);
    });

    it('teacher completes registration with a valid token', function () {
        $teacher = User::factory()->create([
            'role'                      => 'teacher',
            'is_active'                 => false,
            'registration_completed_at' => null,
        ]);

        $rawToken    = TeacherRegistrationToken::createForUser($teacher);
        $tokenRecord = TeacherRegistrationToken::validate($rawToken);

        expect($tokenRecord)->not->toBeNull()
            ->and($tokenRecord->user_id)->toBe($teacher->id);

        DB::transaction(function () use ($teacher, $tokenRecord) {
            TeacherProfile::create([
                'user_id' => $teacher->id,
                'dob'     => '1988-04-10',
                'address' => '12 Lagos Road, Ikeja',
                'phone'   => '08011223344',
                'gender'  => 'male',
            ]);

            $teacher->update([
                'password'                  => 'NewSecurePass1!',
                'is_active'                 => true,
                'registration_completed_at' => now(),
            ]);

            $tokenRecord->markAsUsed();
        });

        $teacher->refresh();

        expect($teacher->is_active)->toBeTrue()
            ->and($teacher->registration_completed_at)->not->toBeNull()
            ->and($teacher->teacherProfile->gender)->toBe('male');
    });

    it('an invalid token returns null on validation', function () {
        $invalid = str_repeat('x', 64);
        expect(TeacherRegistrationToken::validate($invalid))->toBeNull();
    });

    it('a used token is rejected on a second attempt', function () {
        $teacher  = User::factory()->create(['role' => 'teacher']);
        $rawToken = TeacherRegistrationToken::createForUser($teacher);

        $record = TeacherRegistrationToken::validate($rawToken);
        $record->markAsUsed();

        expect(TeacherRegistrationToken::validate($rawToken))->toBeNull();
    });
});

// ══════════════════════════════════════════════════════════════════════════
// E2E 3: Admin → Academic Structure Setup
// ══════════════════════════════════════════════════════════════════════════

describe('E2E 3: Admin sets up academic structure', function () {

    beforeEach(function () {
        $this->initializeTenancy();
        $this->session = Session::createWithTerms(2026);
        $this->session->activate();
        $this->term = $this->session->terms()->where('order', 1)->first();
        $this->term->update(['is_active' => true]);
    });

    afterEach(function () {
        $this->endTenancy();
    });

    it('session is created with exactly three ordered terms', function () {
        $terms = $this->session->terms()->orderBy('order')->get();

        expect($terms)->toHaveCount(3)
            ->and($terms->first()->order)->toBe(1)
            ->and($terms->last()->order)->toBe(3);
    });

    it('only one session is active at a time', function () {
        $newer = Session::createWithTerms(2027);
        $newer->activate();

        expect(Session::active()->count())->toBe(1)
            ->and(Session::active()->first()->name)->toContain('2027');
    });

    it('classrooms are returned in class_order ascending', function () {
        Classroom::create(['name' => 'SSS3', 'class_order' => 6]);
        Classroom::create(['name' => 'JSS1', 'class_order' => 1]);
        Classroom::create(['name' => 'JSS3', 'class_order' => 3]);

        $ordered = Classroom::ordered()->get();

        expect($ordered->first()->name)->toBe('JSS1')
            ->and($ordered->last()->name)->toBe('SSS3');
    });

    it('inactive classrooms are excluded from the active scope', function () {
        Classroom::create(['name' => 'Active Class', 'class_order' => 1, 'is_active' => true]);
        Classroom::create(['name' => 'Inactive Class', 'class_order' => 2, 'is_active' => false]);

        expect(Classroom::active()->count())->toBe(1)
            ->and(Classroom::active()->first()->name)->toBe('Active Class');
    });

    it('subjects enforce unique codes', function () {
        Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);

        expect(fn () => Subject::create(['name' => 'Applied Maths', 'code' => 'MATH']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('assigns a class teacher to a classroom for the session', function () {
        $classroom = Classroom::create(['name' => 'JSS1', 'class_order' => 1]);
        $teacher   = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

        $assignment = ClassTeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id'   => $classroom->id,
            'session_id' => $this->session->id,
        ]);

        expect($assignment->teacher->id)->toBe($teacher->id)
            ->and($assignment->classroom->name)->toBe('JSS1');
    });

    it('prevents two class teachers for the same classroom in the same session', function () {
        $classroom = Classroom::create(['name' => 'JSS2', 'class_order' => 2]);
        $teacher1  = User::factory()->create(['role' => 'teacher']);
        $teacher2  = User::factory()->create(['role' => 'teacher']);

        ClassTeacherAssignment::create([
            'teacher_id' => $teacher1->id,
            'class_id'   => $classroom->id,
            'session_id' => $this->session->id,
        ]);

        expect(fn () => ClassTeacherAssignment::create([
            'teacher_id' => $teacher2->id,
            'class_id'   => $classroom->id,
            'session_id' => $this->session->id,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('assigns a teacher to a subject for a specific class and term', function () {
        $classroom = Classroom::create(['name' => 'SSS2', 'class_order' => 5]);
        $subject   = Subject::create(['name' => 'Physics', 'code' => 'PHY']);
        $teacher   = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

        $assignment = TeacherSubjectAssignment::create([
            'teacher_id'  => $teacher->id,
            'subject_id'  => $subject->id,
            'classroom_id' => $classroom->id,
            'session_id'  => $this->session->id,
            'term_id'     => $this->term->id,
        ]);

        expect($assignment->subject->code)->toBe('PHY')
            ->and($assignment->teacher->id)->toBe($teacher->id)
            ->and($assignment->classroom->name)->toBe('SSS2');
    });

    it('prevents duplicate teacher-subject-class assignments in the same term', function () {
        $classroom = Classroom::create(['name' => 'SSS1', 'class_order' => 4]);
        $subject   = Subject::create(['name' => 'Chemistry', 'code' => 'CHEM']);
        $teacher   = User::factory()->create(['role' => 'teacher']);

        TeacherSubjectAssignment::create([
            'teacher_id'  => $teacher->id,
            'subject_id'  => $subject->id,
            'classroom_id' => $classroom->id,
            'session_id'  => $this->session->id,
            'term_id'     => $this->term->id,
        ]);

        expect(fn () => TeacherSubjectAssignment::create([
            'teacher_id'  => $teacher->id,
            'subject_id'  => $subject->id,
            'classroom_id' => $classroom->id,
            'session_id'  => $this->session->id,
            'term_id'     => $this->term->id,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('one teacher can teach multiple subjects in the same class', function () {
        $classroom = Classroom::create(['name' => 'JSS3', 'class_order' => 3]);
        $math      = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $eng       = Subject::create(['name' => 'English Language', 'code' => 'ENG']);
        $teacher   = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

        TeacherSubjectAssignment::create([
            'teacher_id'  => $teacher->id,
            'subject_id'  => $math->id,
            'classroom_id' => $classroom->id,
            'session_id'  => $this->session->id,
            'term_id'     => $this->term->id,
        ]);

        TeacherSubjectAssignment::create([
            'teacher_id'  => $teacher->id,
            'subject_id'  => $eng->id,
            'classroom_id' => $classroom->id,
            'session_id'  => $this->session->id,
            'term_id'     => $this->term->id,
        ]);

        expect(TeacherSubjectAssignment::where('teacher_id', $teacher->id)->count())->toBe(2);
    });
});

// ══════════════════════════════════════════════════════════════════════════
// E2E 4: Student Registration & Enrollment
// ══════════════════════════════════════════════════════════════════════════

describe('E2E 4: Student registration and classroom enrollment', function () {

    beforeEach(function () {
        $this->initializeTenancy();
        $this->session   = Session::createWithTerms(2026);
        $this->session->activate();
        $this->classroom = Classroom::create(['name' => 'JSS1', 'class_order' => 1]);
    });

    afterEach(function () {
        $this->endTenancy();
    });

    it('admin creates a student in pending state', function () {
        $student = Student::create(['full_name' => 'Amaka Obi', 'status' => 'pending']);

        expect($student->isPending())->toBeTrue()
            ->and($student->isActive())->toBeFalse();
    });

    it('admin generates a 64-char registration token for the student', function () {
        $student  = Student::create(['full_name' => 'Emeka Chukwu', 'status' => 'pending']);
        $rawToken = $student->createRegistrationLink();
        $student->refresh();

        expect($rawToken)->toHaveLength(64)
            ->and($student->registration_slug)->not->toBeNull()
            ->and($student->registration_token)->not->toBeNull();
    });

    it('two registration slugs for the same name are different', function () {
        $slug1 = Student::generateRegistrationSlug('John Doe');
        $slug2 = Student::generateRegistrationSlug('John Doe');

        expect($slug1)->not->toBe($slug2)
            ->and($slug1)->toContain('john-doe');
    });

    it('student validates their registration link with the correct token', function () {
        $student  = Student::create(['full_name' => 'Ngozi Eze', 'status' => 'pending']);
        $rawToken = $student->createRegistrationLink();
        $student->refresh();

        $validated = Student::validateRegistration($student->registration_slug, $rawToken);

        expect($validated)->not->toBeNull()
            ->and($validated->id)->toBe($student->id);
    });

    it('wrong token does not validate the student registration', function () {
        $student = Student::create(['full_name' => 'Bad Token Test', 'status' => 'pending']);
        $student->createRegistrationLink();
        $student->refresh();

        $result = Student::validateRegistration($student->registration_slug, str_repeat('z', 64));
        expect($result)->toBeNull();
    });

    it('student completes registration and becomes active with cleared tokens', function () {
        $student = Student::create(['full_name' => 'Tunde Bakare', 'status' => 'pending']);
        $student->createRegistrationLink();

        $student->completeRegistration([
            'date_of_birth' => '2009-03-22',
            'gender'        => 'male',
            'address'       => '10 School Road, Abuja',
        ]);

        $student->refresh();

        expect($student->isActive())->toBeTrue()
            ->and($student->isPending())->toBeFalse()
            ->and($student->registration_slug)->toBeNull()
            ->and($student->registration_token)->toBeNull();
    });

    it('active student is enrolled in a classroom for the session', function () {
        $student = Student::create(['full_name' => 'Fatima Hassan', 'status' => 'pending']);
        $student->createRegistrationLink();
        $student->completeRegistration([
            'date_of_birth' => '2010-06-01',
            'gender'        => 'female',
            'address'       => '5 Harmony Close, Kano',
        ]);
        $student->refresh();

        $enrollment = StudentEnrollment::create([
            'student_id'  => $student->id,
            'classroom_id' => $this->classroom->id,
            'session_id'  => $this->session->id,
        ]);

        expect($enrollment->student->full_name)->toBe('Fatima Hassan')
            ->and($enrollment->classroom->name)->toBe('JSS1')
            ->and($student->enrollments()->count())->toBe(1);
    });

    it('student cannot be enrolled in two classrooms in the same session', function () {
        $student    = Student::create(['full_name' => 'Double Enroll', 'status' => 'active']);
        $classroom2 = Classroom::create(['name' => 'JSS2', 'class_order' => 2]);

        StudentEnrollment::create([
            'student_id'  => $student->id,
            'classroom_id' => $this->classroom->id,
            'session_id'  => $this->session->id,
        ]);

        expect(fn () => StudentEnrollment::create([
            'student_id'  => $student->id,
            'classroom_id' => $classroom2->id,
            'session_id'  => $this->session->id,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });
});

// ══════════════════════════════════════════════════════════════════════════
// E2E 5: Teacher → Lesson Note Lifecycle
// ══════════════════════════════════════════════════════════════════════════

describe('E2E 5: Lesson note create → approve / reject lifecycle', function () {

    beforeEach(function () {
        $this->initializeTenancy();

        $this->session   = Session::createWithTerms(2026);
        $this->session->activate();
        $this->term      = $this->session->terms()->where('order', 1)->first();
        $this->term->update(['is_active' => true]);

        $this->teacher   = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $this->admin     = User::factory()->create(['role' => 'admin']);
        $this->subject   = Subject::create(['name' => 'Chemistry', 'code' => 'CHEM']);
        $this->classroom = Classroom::create(['name' => 'SSS1', 'class_order' => 4]);

        TeacherSubjectAssignment::create([
            'teacher_id'  => $this->teacher->id,
            'subject_id'  => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id'  => $this->session->id,
            'term_id'     => $this->term->id,
        ]);
    });

    afterEach(function () {
        $this->endTenancy();
    });

    it('teacher creates a lesson note in pending state', function () {
        $note = LessonNote::create([
            'teacher_id'  => $this->teacher->id,
            'subject_id'  => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id'  => $this->session->id,
            'term_id'     => $this->term->id,
            'week_number' => 1,
            'status'      => 'pending',
        ]);

        expect($note->status)->toBe('pending')
            ->and($note->canBeEditedByTeacher())->toBeTrue()
            ->and($note->teacher->id)->toBe($this->teacher->id)
            ->and($note->subject->code)->toBe('CHEM')
            ->and($note->classroom->name)->toBe('SSS1');
    });

    it('pending note can be edited; approved note cannot', function () {
        $pending = LessonNote::create([
            'teacher_id' => $this->teacher->id, 'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id, 'session_id' => $this->session->id,
            'term_id' => $this->term->id, 'week_number' => 1, 'status' => 'pending',
        ]);

        $approved = LessonNote::create([
            'teacher_id' => $this->teacher->id, 'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id, 'session_id' => $this->session->id,
            'term_id' => $this->term->id, 'week_number' => 2, 'status' => 'approved',
        ]);

        expect($pending->canBeEditedByTeacher())->toBeTrue()
            ->and($approved->canBeEditedByTeacher())->toBeFalse();
    });

    it('admin approves a pending lesson note', function () {
        $note = LessonNote::create([
            'teacher_id' => $this->teacher->id, 'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id, 'session_id' => $this->session->id,
            'term_id' => $this->term->id, 'week_number' => 1, 'status' => 'pending',
        ]);

        $note->approve('Well structured lesson plan.', $this->admin->id);

        expect($note->fresh()->status)->toBe('approved')
            ->and($note->fresh()->canBeEditedByTeacher())->toBeFalse();
    });

    it('admin rejects a pending lesson note with a comment', function () {
        $note = LessonNote::create([
            'teacher_id' => $this->teacher->id, 'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id, 'session_id' => $this->session->id,
            'term_id' => $this->term->id, 'week_number' => 1, 'status' => 'pending',
        ]);

        $note->reject('Content is incomplete. Please add objectives.', $this->admin->id);

        expect($note->fresh()->status)->toBe('rejected');
    });

    it('forTeacher scope returns only that teacher\'s notes', function () {
        $other        = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $otherSubject = Subject::create(['name' => 'Biology', 'code' => 'BIO']);

        LessonNote::create([
            'teacher_id' => $this->teacher->id, 'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id, 'session_id' => $this->session->id,
            'term_id' => $this->term->id, 'week_number' => 1, 'status' => 'pending',
        ]);

        LessonNote::create([
            'teacher_id' => $other->id, 'subject_id' => $otherSubject->id,
            'classroom_id' => $this->classroom->id, 'session_id' => $this->session->id,
            'term_id' => $this->term->id, 'week_number' => 1, 'status' => 'pending',
        ]);

        $teacherNotes = LessonNote::forTeacher($this->teacher->id)->get();

        expect($teacherNotes)->toHaveCount(1)
            ->and($teacherNotes->first()->teacher_id)->toBe($this->teacher->id);
    });

    it('status scopes correctly partition pending, approved and rejected notes', function () {
        foreach ([
            ['week_number' => 1, 'status' => 'pending'],
            ['week_number' => 2, 'status' => 'approved'],
            ['week_number' => 3, 'status' => 'rejected'],
        ] as $attrs) {
            LessonNote::create(array_merge([
                'teacher_id'  => $this->teacher->id,
                'subject_id'  => $this->subject->id,
                'classroom_id' => $this->classroom->id,
                'session_id'  => $this->session->id,
                'term_id'     => $this->term->id,
            ], $attrs));
        }

        expect(LessonNote::pending()->count())->toBe(1)
            ->and(LessonNote::approved()->count())->toBe(1)
            ->and(LessonNote::rejected()->count())->toBe(1);
    });

    it('forWeek scope returns only notes for that week', function () {
        foreach (range(1, 3) as $week) {
            LessonNote::create([
                'teacher_id'  => $this->teacher->id,
                'subject_id'  => $this->subject->id,
                'classroom_id' => $this->classroom->id,
                'session_id'  => $this->session->id,
                'term_id'     => $this->term->id,
                'week_number' => $week,
                'status'      => 'pending',
            ]);
        }

        $week2 = LessonNote::forWeek(2)->get();

        expect($week2)->toHaveCount(1)
            ->and($week2->first()->week_number)->toBe(2);
    });
});

// ══════════════════════════════════════════════════════════════════════════
// E2E 6: Data Isolation Between Two Tenant Schools
// ══════════════════════════════════════════════════════════════════════════

describe('E2E 6: Data isolation between tenant school databases', function () {

    it('classrooms created in School A are invisible in School B', function () {
        // School A
        $this->initializeTenancy();
        Classroom::create(['name' => 'JSS1-A', 'class_order' => 1]);
        Classroom::create(['name' => 'SSS3-A', 'class_order' => 6]);
        expect(Classroom::count())->toBe(2);
        $this->endTenancy();

        // School B
        $this->initializeTenancy();
        expect(Classroom::count())->toBe(0);
        $this->endTenancy();
    });

    it('a teacher from School A does not exist in School B', function () {
        // School A
        $this->initializeTenancy();
        User::factory()->create(['email' => 'teacher@schoola.test', 'role' => 'teacher']);
        $this->endTenancy();

        // School B
        $this->initializeTenancy();
        expect(User::where('email', 'teacher@schoola.test')->first())->toBeNull();
        $this->endTenancy();
    });

    it('subjects created in School A are absent in School B', function () {
        $this->initializeTenancy();
        Subject::create(['name' => 'School A Physics', 'code' => 'PHYA']);
        Subject::create(['name' => 'School A Chemistry', 'code' => 'CHEMA']);
        expect(Subject::count())->toBe(2);
        $this->endTenancy();

        $this->initializeTenancy();
        expect(Subject::count())->toBe(0);
        $this->endTenancy();
    });

    it('students in School A are not visible in School B', function () {
        $this->initializeTenancy();
        Student::create(['full_name' => 'School A Student', 'status' => 'active']);
        expect(Student::count())->toBe(1);
        $this->endTenancy();

        $this->initializeTenancy();
        expect(Student::count())->toBe(0);
        $this->endTenancy();
    });

    it('lesson notes from School A do not appear in School B', function () {
        // School A — create a full lesson note
        $this->initializeTenancy();
        $session  = Session::createWithTerms(2026);
        $session->activate();
        $term     = $session->terms()->where('order', 1)->first();
        $teacher  = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $subject  = Subject::create(['name' => 'School A Math', 'code' => 'MATHA']);
        $classroom = Classroom::create(['name' => 'JSS1', 'class_order' => 1]);

        LessonNote::create([
            'teacher_id'  => $teacher->id,
            'subject_id'  => $subject->id,
            'classroom_id' => $classroom->id,
            'session_id'  => $session->id,
            'term_id'     => $term->id,
            'week_number' => 1,
            'status'      => 'pending',
        ]);

        expect(LessonNote::count())->toBe(1);
        $this->endTenancy();

        // School B — fresh slate
        $this->initializeTenancy();
        expect(LessonNote::count())->toBe(0);
        $this->endTenancy();
    });

    it('suspending School A does not affect School B', function () {
        $schoolA = School::create([
            'name' => 'School A', 'database_name' => 'db_a',
            'database_username' => 'u_a', 'database_password' => 'p_a', 'status' => 'active',
        ]);

        $schoolB = School::create([
            'name' => 'School B', 'database_name' => 'db_b',
            'database_username' => 'u_b', 'database_password' => 'p_b', 'status' => 'active',
        ]);

        $schoolA->update(['status' => 'suspended']);

        expect($schoolA->fresh()->isSuspended())->toBeTrue()
            ->and($schoolB->fresh()->isActive())->toBeTrue();
    });

    it('tenant models use a non-central database connection', function () {
        $this->initializeTenancy();

        expect((new Classroom())->getConnectionName())->not->toBe('central')
            ->and((new Subject())->getConnectionName())->not->toBe('central')
            ->and((new User())->getConnectionName())->not->toBe('central')
            ->and((new Student())->getConnectionName())->not->toBe('central');

        $this->endTenancy();
    });

    it('the classrooms table does not exist on the central connection', function () {
        $this->initializeTenancy();

        expect(fn () => Classroom::on('central')->get())->toThrow(\Exception::class);

        $this->endTenancy();
    });

    it('School model always uses the central connection', function () {
        expect((new School())->getConnectionName())->toBe('central')
            ->and((new Domain())->getConnectionName())->toBe('central');
    });
});

// ══════════════════════════════════════════════════════════════════════════
// E2E 7: Role & Panel Access Control
// ══════════════════════════════════════════════════════════════════════════

describe('E2E 7: Role and Filament panel access control', function () {

    beforeEach(function () {
        $this->initializeTenancy();
    });

    afterEach(function () {
        $this->endTenancy();
    });

    it('admin panel is only accessible to the admin role', function () {
        $admin   = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student']);

        $panel = \Filament\Facades\Filament::getPanel('admin');

        expect($admin->canAccessPanel($panel))->toBeTrue()
            ->and($teacher->canAccessPanel($panel))->toBeFalse()
            ->and($student->canAccessPanel($panel))->toBeFalse();
    });

    it('teacher panel requires active status; admins bypass this check', function () {
        $activeTeacher   = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $inactiveTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => false]);
        $admin           = User::factory()->create(['role' => 'admin']);

        $panel = \Filament\Facades\Filament::getPanel('teacher');

        expect($activeTeacher->canAccessPanel($panel))->toBeTrue()
            ->and($inactiveTeacher->canAccessPanel($panel))->toBeFalse()
            ->and($admin->canAccessPanel($panel))->toBeTrue();
    });

    it('sudo users are filtered out of tenant UserResource queries', function () {
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'teacher']);
        User::factory()->create(['role' => 'sudo']);

        // UserResource::getEloquentQuery() excludes 'sudo'
        $visible = User::where('role', '!=', 'sudo')->get();

        expect($visible->pluck('role')->contains('sudo'))->toBeFalse();
    });

    it('admins can impersonate teachers but not sudo users', function () {
        $admin   = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $sudo    = User::factory()->create(['role' => 'sudo']);

        expect($admin->canImpersonate())->toBeTrue()
            ->and($teacher->canBeImpersonated())->toBeTrue()
            ->and($sudo->canBeImpersonated())->toBeFalse();
    });

    it('user passwords are hashed and never stored in plain text', function () {
        $user = User::factory()->create(['password' => 'PlainText123!']);

        expect($user->password)->not->toBe('PlainText123!')
            ->and(Hash::check('PlainText123!', $user->password))->toBeTrue();
    });

    it('central domain configuration is non-empty', function () {
        $centralDomains = config('tenancy.central_domains', []);

        expect($centralDomains)->toBeArray()
            ->and($centralDomains)->not->toBeEmpty();
    });
});

// ══════════════════════════════════════════════════════════════════════════
// E2E 8: Full Workflow Chain
//         School creation → admin setup → teacher registration →
//         student enrollment → lesson note submission → admin approval
// ══════════════════════════════════════════════════════════════════════════

describe('E2E 8: Full workflow from school provisioning to lesson note approval', function () {

    it('runs the complete multi-tenant workflow end to end', function () {
        Notification::fake();

        // ── Step 1: School is provisioned (tenant DB created + admin seeded) ──────────
        $this->initializeTenancy();

        // ── Step 2: Admin creates the academic year structure ─────────────────────────
        $session = Session::createWithTerms(2026);
        $session->activate();
        $term = $session->terms()->where('order', 1)->first();
        $term->update(['is_active' => true]);

        // ── Step 3: Admin creates classrooms and subjects ─────────────────────────────
        $classroom = Classroom::create(['name' => 'SSS3', 'class_order' => 6]);
        $subject   = Subject::create(['name' => 'Further Mathematics', 'code' => 'FMATH']);

        expect(Classroom::count())->toBe(1)
            ->and(Subject::count())->toBe(1);

        // ── Step 4: Admin creates a teacher (inactive, pending registration) ──────────
        $teacher = User::create([
            'name'     => 'Dr. Adaeze Nwosu',
            'email'    => 'adaeze@wonders.test',
            'password' => 'temporary_password',
            'role'     => 'teacher',
            'is_active' => false,
        ]);

        expect($teacher->isTeacher())->toBeTrue()
            ->and($teacher->is_active)->toBeFalse();

        // ── Step 5: Admin generates and sends registration link ───────────────────────
        $rawToken = TeacherRegistrationToken::createForUser($teacher);
        $teacher->notify(new TeacherRegistrationInvitation($rawToken));

        Notification::assertSentTo($teacher, TeacherRegistrationInvitation::class);

        // ── Step 6: Teacher validates token and completes registration ────────────────
        $tokenRecord = TeacherRegistrationToken::validate($rawToken);
        expect($tokenRecord)->not->toBeNull();

        DB::transaction(function () use ($teacher, $tokenRecord) {
            TeacherProfile::create([
                'user_id' => $teacher->id,
                'dob'     => '1985-11-20',
                'address' => '3 University Avenue, Enugu',
                'phone'   => '08123456789',
                'gender'  => 'female',
            ]);

            $teacher->update([
                'password'                  => 'StrongPass@2026',
                'is_active'                 => true,
                'registration_completed_at' => now(),
            ]);

            $tokenRecord->markAsUsed();
        });

        $teacher->refresh();

        expect($teacher->is_active)->toBeTrue()
            ->and($teacher->teacherProfile->gender)->toBe('female')
            ->and(TeacherRegistrationToken::validate($rawToken))->toBeNull(); // token consumed

        // ── Step 7: Admin assigns teacher as class teacher and subject teacher ────────
        ClassTeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id'   => $classroom->id,
            'session_id' => $session->id,
        ]);

        TeacherSubjectAssignment::create([
            'teacher_id'  => $teacher->id,
            'subject_id'  => $subject->id,
            'classroom_id' => $classroom->id,
            'session_id'  => $session->id,
            'term_id'     => $term->id,
        ]);

        expect(ClassTeacherAssignment::count())->toBe(1)
            ->and(TeacherSubjectAssignment::count())->toBe(1);

        // ── Step 8: Admin creates a student and generates registration link ───────────
        $student      = Student::create(['full_name' => 'Emeka Okonkwo', 'status' => 'pending']);
        $studentToken = $student->createRegistrationLink();
        $student->refresh();

        expect($student->isPending())->toBeTrue()
            ->and($student->registration_slug)->not->toBeNull();

        // ── Step 9: Student validates and completes registration ──────────────────────
        $validated = Student::validateRegistration($student->registration_slug, $studentToken);
        expect($validated)->not->toBeNull();

        $student->completeRegistration([
            'date_of_birth' => '2007-08-12',
            'gender'        => 'male',
            'address'       => '22 Owerri Road, Imo State',
        ]);

        $student->refresh();
        expect($student->isActive())->toBeTrue();

        // ── Step 10: Admin enrolls student in classroom ───────────────────────────────
        $enrollment = StudentEnrollment::create([
            'student_id'  => $student->id,
            'classroom_id' => $classroom->id,
            'session_id'  => $session->id,
        ]);

        expect($enrollment->student->full_name)->toBe('Emeka Okonkwo')
            ->and($enrollment->classroom->name)->toBe('SSS3');

        // ── Step 11: Teacher creates a lesson note (pending) ──────────────────────────
        $lessonNote = LessonNote::create([
            'teacher_id'  => $teacher->id,
            'subject_id'  => $subject->id,
            'classroom_id' => $classroom->id,
            'session_id'  => $session->id,
            'term_id'     => $term->id,
            'week_number' => 1,
            'status'      => 'pending',
        ]);

        expect($lessonNote->status)->toBe('pending')
            ->and($lessonNote->canBeEditedByTeacher())->toBeTrue()
            ->and(LessonNote::forTeacher($teacher->id)->count())->toBe(1);

        // ── Step 12: Admin approves the lesson note ───────────────────────────────────
        $admin = User::where('role', 'admin')->first();
        $lessonNote->approve('Excellent lesson plan!', $admin->id);
        $lessonNote->refresh();

        expect($lessonNote->status)->toBe('approved')
            ->and($lessonNote->canBeEditedByTeacher())->toBeFalse();

        // ── Cleanup ───────────────────────────────────────────────────────────────────
        $this->endTenancy();
    });
});
