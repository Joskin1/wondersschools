<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Remove `full_name` from the students table.
 *
 * Before dropping the column, ensure every student has a linked User record.
 * Orphan students (no user_id) get a new User created from their full_name,
 * with is_active=false and password=null.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Create User records for orphan students (those without a user_id)
        DB::table('students')
            ->whereNull('user_id')
            ->orderBy('id')
            ->each(function ($student) {
                $parts = array_values(array_filter(preg_split('/\s+/', trim(str_replace(',', ' ', (string) $student->full_name)))));

                $surname = $parts[0] ?? '';
                $firstName = $parts[1] ?? '';
                $otherName = count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : null;

                if (count($parts) === 1) {
                    $firstName = '';
                }

                // Generate a unique placeholder email
                $admissionNumber = $student->admission_number ?? ('student-' . $student->id);
                $cleanAdmission = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $admissionNumber));
                $email = "{$cleanAdmission}@student.local";

                // Ensure email uniqueness
                $suffix = 2;
                $baseEmail = $email;
                while (DB::table('users')->where('email', $email)->exists()) {
                    $email = str_replace('@student.local', ".{$suffix}@student.local", $baseEmail);
                    $suffix++;
                }

                $userId = DB::table('users')->insertGetId([
                    'surname' => $surname,
                    'first_name' => $firstName,
                    'other_name' => $otherName,
                    'email' => $email,
                    'password' => null,
                    'role' => 'student',
                    'is_active' => false,
                    'created_at' => $student->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                DB::table('students')->where('id', $student->id)->update([
                    'user_id' => $userId,
                ]);
            });

        // 2. For students that already have a user_id, ensure the user's name fields
        //    are populated from full_name (in case the user.name was different or empty)
        DB::table('students')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->each(function ($student) {
                $user = DB::table('users')->where('id', $student->user_id)->first();
                if (!$user) {
                    return;
                }

                // Only update if the user's surname is still the default empty string
                // (i.e., the previous migration already ran but may have had different data)
                if ($user->surname !== '' || $user->first_name !== '') {
                    return; // Already populated by the users migration
                }

                $parts = array_values(array_filter(preg_split('/\s+/', trim(str_replace(',', ' ', (string) $student->full_name)))));

                $surname = $parts[0] ?? '';
                $firstName = $parts[1] ?? '';
                $otherName = count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : null;

                if (count($parts) === 1) {
                    $firstName = '';
                }

                DB::table('users')->where('id', $student->user_id)->update([
                    'surname' => $surname,
                    'first_name' => $firstName,
                    'other_name' => $otherName,
                ]);
            });

        // 3. Make user_id required (not nullable) now that all students have one
        // Skip this if user_id is already not nullable
        if (Schema::hasColumn('students', 'full_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('full_name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('full_name')->after('admission_number')->default('');
        });

        // Restore full_name from the linked user
        DB::table('students')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->each(function ($student) {
                $user = DB::table('users')->where('id', $student->user_id)->first();
                if (!$user) {
                    return;
                }

                $fullName = trim(implode(' ', array_filter([$user->surname, $user->first_name, $user->other_name])));

                DB::table('students')->where('id', $student->id)->update([
                    'full_name' => $fullName,
                ]);
            });
    }
};
