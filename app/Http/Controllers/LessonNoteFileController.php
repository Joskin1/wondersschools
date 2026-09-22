<?php

namespace App\Http\Controllers;

use App\Models\LessonNoteVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class LessonNoteFileController extends Controller
{
    /**
     * Stream or download an uploaded lesson note file with tenant and auth verification.
     */
    public function show(Request $request, LessonNoteVersion $version): Response
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $lessonNote = $version->lessonNote;
        if (!$lessonNote) {
            abort(404, 'Lesson note not found.');
        }

        // Authorization checks
        if (in_array($user->role, ['admin', 'sudo'], true)) {
            // Admins & superadmins have access to view and review
        } elseif ($user->role === 'teacher') {
            if ($lessonNote->teacher_id !== $user->id) {
                abort(403, 'You do not have permission to view this lesson note file.');
            }
        } elseif ($user->role === 'student') {
            $student = $user->student;
            if (!$student) {
                abort(403, 'No student profile linked.');
            }

            $enrollment = $student->currentEnrollment();
            if (!$enrollment) {
                abort(403, 'Student is not currently enrolled.');
            }

            if ($lessonNote->classroom_id !== $enrollment->classroom_id || $lessonNote->status !== 'approved') {
                abort(403, 'Access denied. You can only view approved lesson notes for your class.');
            }
        } else {
            abort(403, 'Unauthorized.');
        }

        $filePath = $version->file_path;
        if (empty($filePath)) {
            abort(404, 'No file associated with this lesson note version.');
        }

        $absolutePath = $this->resolveFilePath($filePath);

        if (!$absolutePath || !file_exists($absolutePath)) {
            abort(404, 'File not found on storage.');
        }

        $mimeType = $version->mime_type ?: (File::mimeType($absolutePath) ?: 'application/octet-stream');
        $fileName = $version->file_name ?: basename($absolutePath);

        // If explicitly requested as a download attachment
        if ($request->boolean('download')) {
            return response()->download($absolutePath, $fileName, [
                'Content-Type' => $mimeType,
            ]);
        }

        // Stream inline (e.g. for PDF preview iframe or image display)
        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
        ]);
    }

    /**
     * Resolve physical file path across potential tenant disk configurations.
     */
    protected function resolveFilePath(string $filePath): ?string
    {
        // 1. Check lesson_notes disk directly
        try {
            if (Storage::disk('lesson_notes')->exists($filePath)) {
                return Storage::disk('lesson_notes')->path($filePath);
            }
        } catch (\Throwable $e) {
            // Continue fallback
        }

        // 2. Check lesson_notes disk stripping leading 'lesson-notes/'
        if (str_starts_with($filePath, 'lesson-notes/')) {
            $stripped = substr($filePath, strlen('lesson-notes/'));
            try {
                if (Storage::disk('lesson_notes')->exists($stripped)) {
                    return Storage::disk('lesson_notes')->path($stripped);
                }
            } catch (\Throwable $e) {
                // Continue fallback
            }
        }

        // 3. Check local private disk
        try {
            if (Storage::disk('local')->exists($filePath)) {
                return Storage::disk('local')->path($filePath);
            }
        } catch (\Throwable $e) {
            // Continue fallback
        }

        // 4. Check public disk
        try {
            if (Storage::disk('public')->exists($filePath)) {
                return Storage::disk('public')->path($filePath);
            }
        } catch (\Throwable $e) {
            // Continue fallback
        }

        // 5. Direct filesystem candidate paths within tenant storage
        $candidates = [
            storage_path('app/private/lesson-notes/' . $filePath),
            storage_path('app/private/' . $filePath),
            storage_path('app/public/' . $filePath),
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
