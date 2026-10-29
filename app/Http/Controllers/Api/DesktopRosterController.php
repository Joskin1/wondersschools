<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DesktopRosterController extends Controller
{
    /**
     * Retrieve the school roster with biometric templates and cached photos.
     */
    public function roster(Request $request): JsonResponse
    {
        $activeSession = Session::where('is_active', true)->first();

        // 1. Fetch active students
        $students = Student::query()
            ->where('status', 'active')
            ->with([
                'enrollments' => function ($query) use ($activeSession) {
                    if ($activeSession) {
                        $query->where('session_id', $activeSession->id);
                    }
                },
                'enrollments.classroom',
                'enrollments.classArm',
                'enrollments.department',
                'user',
            ])
            ->get();

        $roster = [];

        foreach ($students as $student) {
            $enrollment = $student->enrollments->first() ?? $student->currentEnrollment();
            $classroomName = $enrollment?->classroom?->name ?? 'Unassigned';
            if ($enrollment?->classArm) {
                $classroomName .= ' ' . $enrollment->classArm->name;
            }

            $photoBase64 = $this->encodePhotoBase64($student->profile_picture);

            $roster[] = [
                'central_id' => $student->id,
                'type' => 'student',
                'admission_number' => $student->admission_number ?? "STD-{$student->id}",
                'surname' => $student->surname ?? '',
                'first_name' => $student->first_name ?? '',
                'classroom' => $classroomName,
                'department' => $enrollment?->department?->name ?? null,
                'photo_base64' => $photoBase64,
                'primary_template' => $student->primary_biometric_template,
                'backup_template' => $student->backup_biometric_template,
            ];
        }

        // 2. Fetch active staff / teachers
        $staffMembers = User::query()
            ->whereIn('role', ['admin', 'sudo', 'teacher'])
            ->where('is_active', true)
            ->get();

        foreach ($staffMembers as $staff) {
            $photoBase64 = null; // Avatar if stored on disk

            $roster[] = [
                'central_id' => $staff->id,
                'type' => 'staff',
                'admission_number' => "STAFF/" . str_pad((string) $staff->id, 3, '0', STR_PAD_LEFT),
                'surname' => $staff->surname ?? '',
                'first_name' => $staff->first_name ?? $staff->name,
                'classroom' => ucfirst($staff->role),
                'department' => $staff->admin_designation ?? ($staff->role === 'teacher' ? 'Teaching Faculty' : 'Administration'),
                'photo_base64' => $photoBase64,
                'primary_template' => $staff->primary_biometric_template,
                'backup_template' => $staff->backup_biometric_template,
            ];
        }

        return response()->json([
            'status' => 'success',
            'count' => count($roster),
            'roster' => $roster,
        ]);
    }

    /**
     * Read an image from storage and convert to base64 Data URI for offline SQLite storage.
     */
    protected function encodePhotoBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                $raw = Storage::disk('public')->get($path);
                $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($raw);
            }
        } catch (\Throwable) {
            // Fallback null if image read fails
        }

        return null;
    }
}
