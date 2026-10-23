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

    public function createOrLinkUser(Student $student, string $surname, string $firstName, ?string $otherName = null, ?string $password = null, ?int $activatedBy = null): User
    {
        $this->ensureAdmissionNumber($student);
        $email = $this->internalEmailFor($student);

        $user = ($student->user_id ? User::find($student->user_id) : $student->user)
            ?: User::where('email', $email)->first()
            ?: new User;

        $userData = [
            'email' => $email,
            'role' => 'student',
            'is_active' => filled($password) ? true : ($user->is_active ?? false),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'surname')) {
            $userData['surname'] = trim($surname);
            $userData['first_name'] = trim($firstName);
            $userData['other_name'] = filled($otherName) ? trim($otherName) : null;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'name')) {
            $userData['name'] = trim(implode(' ', array_filter([$surname, $firstName, $otherName])));
        }

        $user->fill($userData);

        if (filled($password)) {
            $user->password = Hash::make($password);
            $user->email_verified_at = $user->email_verified_at ?? now();
            $user->registration_completed_at = $user->registration_completed_at ?? now();
        }

        $user->save();

        $student->forceFill([
            'user_id' => $user->id,
            'status' => filled($password) ? 'active' : ($student->status ?: 'pending'),
            'is_portal_active' => filled($password) ? true : ($student->is_portal_active ?? false),
            'registration_completed_at' => filled($password) ? ($student->registration_completed_at ?? now()) : $student->registration_completed_at,
            'activated_at' => filled($password) ? ($student->activated_at ?? now()) : $student->activated_at,
            'activated_by' => filled($password) ? ($student->activated_by ?? $activatedBy) : $student->activated_by,
        ])->saveQuietly();

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
