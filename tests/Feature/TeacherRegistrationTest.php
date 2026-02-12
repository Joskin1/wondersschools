<?php

use App\Models\User;
use App\Models\TeacherProfile;
use App\Models\TeacherRegistrationToken;
use App\Notifications\TeacherRegistrationInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('Teacher Registration System', function () {
    
    describe('Admin Creates Teacher', function () {
        it('creates teacher with password NULL and inactive status', function () {
            $teacher = User::factory()->create([
                'role' => 'teacher',
                'password' => null,
                'is_active' => false,
                'registration_completed_at' => null,
            ]);

            expect($teacher->password)->toBeNull()
                ->and($teacher->is_active)->toBeFalse()
                ->and($teacher->registration_completed_at)->toBeNull()
                ->and($teacher->role)->toBe('teacher');
        });
    });

    describe('Token Generation', function () {
        it('generates cryptographically secure token', function () {
            $token = TeacherRegistrationToken::generateToken();

            expect($token)->toBeString()
                ->and(strlen($token))->toBe(64);
        });

        it('hashes token with SHA-256', function () {
            $token = 'test-token-123';
            $hash = TeacherRegistrationToken::hashToken($token);

            expect($hash)->toBeString()
                ->and(strlen($hash))->toBe(64) // SHA-256 produces 64-char hex
                ->and($hash)->toBe(hash('sha256', $token));
        });

        it('creates token for user with 3-day expiry', function () {
            $teacher = User::factory()->create(['role' => 'teacher']);
            
            $token = TeacherRegistrationToken::createForUser($teacher);

            $tokenRecord = TeacherRegistrationToken::where('user_id', $teacher->id)->first();

            expect($tokenRecord)->not->toBeNull()
                ->and($tokenRecord->expires_at->diffInDays(now()))->toBe(3)
                ->and($tokenRecord->used_at)->toBeNull();
        });

        it('stores hashed token, not raw token', function () {
            $teacher = User::factory()->create(['role' => 'teacher']);
            
            $rawToken = TeacherRegistrationToken::createForUser($teacher);
            $tokenRecord = TeacherRegistrationToken::where('user_id', $teacher->id)->first();

            // Raw token should not be in database
            expect($tokenRecord->token_hash)->not->toBe($rawToken)
                ->and($tokenRecord->token_hash)->toBe(TeacherRegistrationToken::hashToken($rawToken));
        });
    });

    describe('Token Validation', function () {
        it('validates valid token', function () {
            $teacher = User::factory()->create(['role' => 'teacher']);
            $rawToken = TeacherRegistrationToken::createForUser($teacher);

            $validated = TeacherRegistrationToken::validate($rawToken);

            expect($validated)->not->toBeNull()
                ->and($validated->user_id)->toBe($teacher->id);
        });

        it('rejects expired token', function () {
            $teacher = User::factory()->create(['role' => 'teacher']);
            $rawToken = TeacherRegistrationToken::createForUser($teacher);

            // Manually expire the token
            $tokenRecord = TeacherRegistrationToken::where('user_id', $teacher->id)->first();
            $tokenRecord->update(['expires_at' => now()->subDay()]);

            $validated = TeacherRegistrationToken::validate($rawToken);

            expect($validated)->toBeNull();
        });

        it('rejects used token', function () {
            $teacher = User::factory()->create(['role' => 'teacher']);
            $rawToken = TeacherRegistrationToken::createForUser($teacher);

            $tokenRecord = TeacherRegistrationToken::where('user_id', $teacher->id)->first();
            $tokenRecord->markAsUsed();

            $validated = TeacherRegistrationToken::validate($rawToken);

            expect($validated)->toBeNull();
        });

        it('rejects invalid token hash', function () {
            $teacher = User::factory()->create(['role' => 'teacher']);
            TeacherRegistrationToken::createForUser($teacher);

            $validated = TeacherRegistrationToken::validate('invalid-token');

            expect($validated)->toBeNull();
        });
    });

    describe('Registration Completion', function () {
        it('completes registration successfully', function () {
            $teacher = User::factory()->create([
                'role' => 'teacher',
                'password' => null,
                'is_active' => false,
                'registration_completed_at' => null,
            ]);

            $rawToken = TeacherRegistrationToken::createForUser($teacher);

            // Simulate registration completion
            TeacherProfile::create([
                'user_id' => $teacher->id,
                'dob' => '1990-01-01',
                'address' => '123 Test St',
                'phone' => '+234 123 456 7890',
                'gender' => 'male',
            ]);

            $teacher->update([
                'password' => Hash::make('Password123'),
                'is_active' => true,
                'registration_completed_at' => now(),
            ]);

            $tokenRecord = TeacherRegistrationToken::where('user_id', $teacher->id)->first();
            $tokenRecord->markAsUsed();

            $teacher->refresh();

            expect($teacher->password)->not->toBeNull()
                ->and($teacher->is_active)->toBeTrue()
                ->and($teacher->registration_completed_at)->not->toBeNull()
                ->and($teacher->teacherProfile)->not->toBeNull()
                ->and($tokenRecord->isUsed())->toBeTrue();
        });
    });

    describe('User Model Methods', function () {
        it('checks if user is active', function () {
            $activeTeacher = User::factory()->create(['is_active' => true]);
            $inactiveTeacher = User::factory()->create(['is_active' => false]);

            expect($activeTeacher->isActive())->toBeTrue()
                ->and($inactiveTeacher->isActive())->toBeFalse();
        });

        it('checks if user has completed registration', function () {
            $completedTeacher = User::factory()->create([
                'registration_completed_at' => now(),
            ]);
            $pendingTeacher = User::factory()->create([
                'registration_completed_at' => null,
            ]);

            expect($completedTeacher->hasCompletedRegistration())->toBeTrue()
                ->and($pendingTeacher->hasCompletedRegistration())->toBeFalse();
        });

        it('checks if user can be assigned', function () {
            $assignableTeacher = User::factory()->create([
                'role' => 'teacher',
                'is_active' => true,
                'registration_completed_at' => now(),
            ]);

            $inactiveTeacher = User::factory()->create([
                'role' => 'teacher',
                'is_active' => false,
                'registration_completed_at' => now(),
            ]);

            $pendingTeacher = User::factory()->create([
                'role' => 'teacher',
                'is_active' => true,
                'registration_completed_at' => null,
            ]);

            expect($assignableTeacher->canBeAssigned())->toBeTrue()
                ->and($inactiveTeacher->canBeAssigned())->toBeFalse()
                ->and($pendingTeacher->canBeAssigned())->toBeFalse();
        });
    });

    describe('User Scopes', function () {
        it('filters active users', function () {
            User::factory()->create(['is_active' => true]);
            User::factory()->create(['is_active' => false]);

            $activeUsers = User::active()->get();

            expect($activeUsers)->toHaveCount(1)
                ->and($activeUsers->first()->is_active)->toBeTrue();
        });

        it('filters active teachers', function () {
            User::factory()->create(['role' => 'teacher', 'is_active' => true]);
            User::factory()->create(['role' => 'teacher', 'is_active' => false]);
            User::factory()->create(['role' => 'admin', 'is_active' => true]);

            $activeTeachers = User::activeTeachers()->get();

            expect($activeTeachers)->toHaveCount(1)
                ->and($activeTeachers->first()->role)->toBe('teacher')
                ->and($activeTeachers->first()->is_active)->toBeTrue();
        });

        it('filters pending registration teachers', function () {
            User::factory()->create([
                'role' => 'teacher',
                'is_active' => false,
                'registration_completed_at' => null,
            ]);

            User::factory()->create([
                'role' => 'teacher',
                'is_active' => true,
                'registration_completed_at' => now(),
            ]);

            $pendingTeachers = User::pendingRegistration()->get();

            expect($pendingTeachers)->toHaveCount(1)
                ->and($pendingTeachers->first()->is_active)->toBeFalse()
                ->and($pendingTeachers->first()->registration_completed_at)->toBeNull();
        });
    });

    describe('Panel Access', function () {
        it('prevents inactive teacher from accessing teacher panel', function () {
            $inactiveTeacher = User::factory()->create([
                'role' => 'teacher',
                'is_active' => false,
            ]);

            $panel = \Filament\Facades\Filament::getPanel('teacher');

            expect($inactiveTeacher->canAccessPanel($panel))->toBeFalse();
        });

        it('allows active teacher to access teacher panel', function () {
            $activeTeacher = User::factory()->create([
                'role' => 'teacher',
                'is_active' => true,
            ]);

            $panel = \Filament\Facades\Filament::getPanel('teacher');

            expect($activeTeacher->canAccessPanel($panel))->toBeTrue();
        });
    });

    describe('Send Registration Link', function () {
        it('sends registration email with token', function () {
            Notification::fake();

            $teacher = User::factory()->create([
                'role' => 'teacher',
                'is_active' => false,
            ]);

            $token = TeacherRegistrationToken::createForUser($teacher);
            $teacher->notify(new TeacherRegistrationInvitation($token));

            Notification::assertSentTo($teacher, TeacherRegistrationInvitation::class);
        });
    });
});
