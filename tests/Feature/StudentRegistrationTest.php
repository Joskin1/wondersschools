<?php

use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\StudentEnrollment;
use App\Models\Classroom;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Student Registration System', function () {
    
    describe('Admin Creates Student', function () {
        it('creates student with pending status', function () {
            $student = Student::factory()->create([
                'status' => 'pending',
            ]);

            expect($student->status)->toBe('pending')
                ->and($student->isPending())->toBeTrue()
                ->and($student->isActive())->toBeFalse();
        });

        it('creates student with enrollment', function () {
            $session = Session::factory()->create();
            $classroom = Classroom::factory()->create();
            $student = Student::factory()->create();

            $enrollment = StudentEnrollment::create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'session_id' => $session->id,
            ]);

            expect($enrollment->student_id)->toBe($student->id)
                ->and($enrollment->classroom_id)->toBe($classroom->id)
                ->and($enrollment->session_id)->toBe($session->id);
        });

        it('prevents duplicate enrollment in same session', function () {
            $session = Session::factory()->create();
            $classroom1 = Classroom::factory()->create();
            $classroom2 = Classroom::factory()->create();
            $student = Student::factory()->create();

            StudentEnrollment::create([
                'student_id' => $student->id,
                'classroom_id' => $classroom1->id,
                'session_id' => $session->id,
            ]);

            // Attempt to create duplicate enrollment
            expect(fn () => StudentEnrollment::create([
                'student_id' => $student->id,
                'classroom_id' => $classroom2->id,
                'session_id' => $session->id,
            ]))->toThrow(\Illuminate\Database\QueryException::class);
        });
    });

    describe('Slug Generation', function () {
        it('generates unique slug with correct format', function () {
            $fullName = 'John Doe';
            $slug = Student::generateRegistrationSlug($fullName);

            expect($slug)->toBeString()
                ->and($slug)->toContain('john-doe')
                ->and(strlen($slug))->toBeGreaterThan(strlen('john-doe'));
        });

        it('generates different slugs for same name', function () {
            $fullName = 'John Doe';
            $slug1 = Student::generateRegistrationSlug($fullName);
            $slug2 = Student::generateRegistrationSlug($fullName);

            expect($slug1)->not->toBe($slug2);
        });
    });

    describe('Token Generation', function () {
        it('generates cryptographically secure token', function () {
            $token = Student::generateRegistrationToken();

            expect($token)->toBeString()
                ->and(strlen($token))->toBe(64);
        });

        it('hashes token with SHA-256', function () {
            $token = 'test-token-123';
            $hash = Student::hashToken($token);

            expect($hash)->toBeString()
                ->and(strlen($hash))->toBe(64) // SHA-256 produces 64-char hex
                ->and($hash)->toBe(hash('sha256', $token));
        });

        it('creates registration link with 3-day expiry', function () {
            $student = Student::factory()->create();
            
            $rawToken = $student->createRegistrationLink();

            $student->refresh();

            expect($student->registration_slug)->not->toBeNull()
                ->and($student->registration_token)->not->toBeNull()
                ->and($student->registration_expires_at)->not->toBeNull()
                ->and((int) round(abs($student->registration_expires_at->diffInDays(now()))))->toBe(3);
        });

        it('stores hashed token, not raw token', function () {
            $student = Student::factory()->create();
            
            $rawToken = $student->createRegistrationLink();
            $student->refresh();

            // Raw token should not be in database
            expect($student->registration_token)->not->toBe($rawToken)
                ->and($student->registration_token)->toBe(Student::hashToken($rawToken));
        });
    });

    describe('Token Validation', function () {
        it('validates valid token', function () {
            $student = Student::factory()->create();
            $rawToken = $student->createRegistrationLink();
            $student->refresh();

            $validated = Student::validateRegistration($student->registration_slug, $rawToken);

            expect($validated)->not->toBeNull()
                ->and($validated->id)->toBe($student->id);
        });

        it('rejects expired token', function () {
            $student = Student::factory()->create();
            $rawToken = $student->createRegistrationLink();
            
            // Manually expire the token
            $student->update(['registration_expires_at' => now()->subDay()]);

            $validated = Student::validateRegistration($student->registration_slug, $rawToken);

            expect($validated)->toBeNull();
        });

        it('rejects invalid token', function () {
            $student = Student::factory()->create();
            $student->createRegistrationLink();
            $student->refresh();

            $validated = Student::validateRegistration($student->registration_slug, 'invalid-token');

            expect($validated)->toBeNull();
        });

        it('rejects token for active student', function () {
            $student = Student::factory()->active()->create();
            $rawToken = $student->createRegistrationLink();
            $student->refresh();

            $validated = Student::validateRegistration($student->registration_slug, $rawToken);

            expect($validated)->toBeNull();
        });
    });

    describe('Registration Completion', function () {
        it('completes registration successfully', function () {
            $student = Student::factory()->create(['status' => 'pending']);
            $rawToken = $student->createRegistrationLink();
            $student->refresh();

            $profileData = [
                'date_of_birth' => '2010-01-01',
                'gender' => 'male',
                'address' => '123 Test St',
                'previous_school' => 'Test School',
                'parent_name' => 'John Parent',
                'parent_phone' => '+234 123 456 7890',
                'parent_email' => 'parent@example.com',
            ];

            $student->completeRegistration($profileData);
            $student->refresh();

            expect($student->status)->toBe('active')
                ->and($student->isActive())->toBeTrue()
                ->and($student->profile)->not->toBeNull()
                ->and($student->profile->date_of_birth->format('Y-m-d'))->toBe('2010-01-01')
                ->and($student->profile->parent_name)->toBe('John Parent')
                ->and($student->registration_slug)->toBeNull()
                ->and($student->registration_token)->toBeNull()
                ->and($student->registration_expires_at)->toBeNull();
        });

        it('clears registration link after completion', function () {
            $student = Student::factory()->withRegistrationLink()->create();

            expect($student->registration_slug)->not->toBeNull();

            $student->clearRegistrationLink();
            $student->refresh();

            expect($student->registration_slug)->toBeNull()
                ->and($student->registration_token)->toBeNull()
                ->and($student->registration_expires_at)->toBeNull();
        });
    });

    describe('Model Methods', function () {
        it('checks if student is active', function () {
            $activeStudent = Student::factory()->active()->create();
            $pendingStudent = Student::factory()->pending()->create();

            expect($activeStudent->isActive())->toBeTrue()
                ->and($pendingStudent->isActive())->toBeFalse();
        });

        it('checks if student is pending', function () {
            $activeStudent = Student::factory()->active()->create();
            $pendingStudent = Student::factory()->pending()->create();

            expect($activeStudent->isPending())->toBeFalse()
                ->and($pendingStudent->isPending())->toBeTrue();
        });

        it('checks if registration has expired', function () {
            $student = Student::factory()->withExpiredRegistrationLink()->create();

            expect($student->hasExpiredRegistration())->toBeTrue();
        });
    });

    describe('Model Scopes', function () {
        it('filters active students', function () {
            Student::factory()->active()->create();
            Student::factory()->pending()->create();

            $activeStudents = Student::active()->get();

            expect($activeStudents)->toHaveCount(1)
                ->and($activeStudents->first()->status)->toBe('active');
        });

        it('filters pending students', function () {
            Student::factory()->active()->create();
            Student::factory()->pending()->create();

            $pendingStudents = Student::pending()->get();

            expect($pendingStudents)->toHaveCount(1)
                ->and($pendingStudents->first()->status)->toBe('pending');
        });
    });

    describe('Model Relationships', function () {
        it('has profile relationship', function () {
            $student = Student::factory()->create();
            $profile = StudentProfile::factory()->create(['student_id' => $student->id]);

            expect($student->profile)->not->toBeNull()
                ->and($student->profile->id)->toBe($profile->id);
        });

        it('has enrollments relationship', function () {
            $student = Student::factory()->create();
            $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id]);

            expect($student->enrollments)->toHaveCount(1)
                ->and($student->enrollments->first()->id)->toBe($enrollment->id);
        });

        it('cascades delete to profile and enrollments', function () {
            $student = Student::factory()->create();
            StudentProfile::factory()->create(['student_id' => $student->id]);
            StudentEnrollment::factory()->create(['student_id' => $student->id]);

            $studentId = $student->id;
            $student->delete();

            expect(StudentProfile::where('student_id', $studentId)->count())->toBe(0)
                ->and(StudentEnrollment::where('student_id', $studentId)->count())->toBe(0);
        });
    });

    describe('Enrollment Scopes', function () {
        it('filters enrollments by session', function () {
            $session1 = Session::factory()->create();
            $session2 = Session::factory()->create();
            
            StudentEnrollment::factory()->create(['session_id' => $session1->id]);
            StudentEnrollment::factory()->create(['session_id' => $session2->id]);

            $enrollments = StudentEnrollment::forSession($session1->id)->get();

            expect($enrollments)->toHaveCount(1)
                ->and($enrollments->first()->session_id)->toBe($session1->id);
        });
    });
});
