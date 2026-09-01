<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentAccountService
{
    public function createOrUpdateLogin(Student $student, string $password, ?int $activatedBy = null): User
    {
        $this->ensureAdmissionNumber($student);

        $user = $student->user ?: new User;

        $user->fill([
            'name' => $student->full_name,
            'email' => $this->internalEmailFor($student),
            'password' => Hash::make($password),
            'role' => 'student',
            'is_active' => true,
            'email_verified_at' => now(),
            'registration_completed_at' => now(),
        ]);

        $user->save();

        $student->update([
            'user_id' => $user->id,
            'status' => 'active',
            'registration_completed_at' => $student->registration_completed_at ?? now(),
            'is_portal_active' => true,
            'activated_at' => $student->activated_at ?? now(),
            'activated_by' => $activatedBy,
            'registration_slug' => null,
            'registration_token' => null,
            'registration_expires_at' => null,
        ]);

        return $user;
    }

    public function ensureAdmissionNumber(Student $student): string
    {
        return $student->ensureAdmissionNumber();
    }

    public function internalEmailFor(Student $student): string
    {
        $admissionNumber = Str::of($this->ensureAdmissionNumber($student))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->toString();

        return "{$admissionNumber}@student.local";
    }
}
