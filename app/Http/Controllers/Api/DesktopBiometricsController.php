<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesktopBiometricsController extends Controller
{
    /**
     * Backup enrolled biometric templates to the cloud database.
     */
    public function enroll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'central_id' => ['required', 'integer'],
            'user_type' => ['required', 'string', 'in:student,staff'],
            'primary_template' => ['required', 'string'],
            'backup_template' => ['nullable', 'string'],
        ]);

        $enrolledAt = now();

        if ($validated['user_type'] === 'student') {
            $student = Student::findOrFail($validated['central_id']);
            $student->forceFill([
                'primary_biometric_template' => $validated['primary_template'],
                'backup_biometric_template' => $validated['backup_template'] ?? null,
                'biometric_enrolled_at' => $enrolledAt,
            ])->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Biometric templates enrolled successfully for student.',
                'central_id' => $student->id,
                'user_type' => 'student',
                'enrolled_at' => $enrolledAt->toDateTimeString(),
            ]);
        }

        // Staff enrollment
        $user = User::query()
            ->whereIn('role', ['admin', 'sudo', 'teacher'])
            ->findOrFail($validated['central_id']);

        $user->forceFill([
            'primary_biometric_template' => $validated['primary_template'],
            'backup_biometric_template' => $validated['backup_template'] ?? null,
            'biometric_enrolled_at' => $enrolledAt,
        ])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Biometric templates enrolled successfully for staff member.',
            'central_id' => $user->id,
            'user_type' => 'staff',
            'enrolled_at' => $enrolledAt->toDateTimeString(),
        ]);
    }

    /**
     * Verify a student's identity and return their profile details for any school checkpoint.
     */
    public function verifyStudent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'central_id' => ['nullable', 'integer'],
            'admission_number' => ['nullable', 'string'],
        ]);

        if (empty($validated['central_id']) && empty($validated['admission_number'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Either central_id or admission_number must be provided.',
            ], 422);
        }

        $student = Student::query()
            ->when(! empty($validated['central_id']), fn ($q) => $q->where('id', $validated['central_id']))
            ->when(! empty($validated['admission_number']), fn ($q) => $q->where('admission_number', $validated['admission_number']))
            ->with(['enrollments.classroom', 'enrollments.classArm', 'enrollments.department'])
            ->first();

        if (! $student) {
            return response()->json([
                'status' => 'error',
                'message' => 'Student record not found.',
            ], 404);
        }

        $enrollment = $student->enrollments->first() ?? $student->currentEnrollment();
        $classroomName = $enrollment?->classroom?->name ?? 'Unassigned';
        if ($enrollment?->classArm) {
            $classroomName .= ' ' . $enrollment->classArm->name;
        }

        return response()->json([
            'status' => 'success',
            'student' => [
                'central_id' => $student->id,
                'admission_number' => $student->admission_number,
                'full_name' => $student->full_name,
                'surname' => $student->surname,
                'first_name' => $student->first_name,
                'classroom' => $classroomName,
                'department' => $enrollment?->department?->name,
                'is_biometric_enrolled' => $student->isBiometricEnrolled(),
                'biometric_enrolled_at' => $student->biometric_enrolled_at?->toDateTimeString(),
                'status' => $student->status,
            ],
        ]);
    }
}

