<?php

namespace App\Http\Controllers;

use App\Models\LessonNote;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonNotePdfController extends Controller
{
    public function download(Request $request, LessonNote $lessonNote)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        // Authorization check
        if ($user->role === 'student') {
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
        } elseif ($user->role === 'teacher') {
            if ($lessonNote->teacher_id !== $user->id && !in_array($user->role, ['admin', 'sudo'])) {
                abort(403, 'You do not have permission to download this lesson note.');
            }
        } elseif (!in_array($user->role, ['admin', 'sudo'])) {
            abort(403, 'Unauthorized.');
        }

        $lessonNote->load(['teacher', 'subject', 'classroom', 'session', 'term', 'latestVersion']);
        $latestVersion = $lessonNote->latestVersion;

        $settings = Setting::all()->pluck('value', 'key')->toArray();

        $schoolName = $settings['school_name'] ?? config('app.name', 'School Portal');
        $schoolLogo = $settings['school_logo'] ?? null;
        $schoolAddress = $settings['school_address'] ?? null;
        $schoolMotto = $settings['school_motto'] ?? null;
        $brandColor = $settings['primary_color'] ?? '#1a365d';

        $data = [
            'lesson_note' => $lessonNote,
            'version' => $latestVersion,
            'school_name' => $schoolName,
            'school_logo' => $schoolLogo,
            'school_address' => $schoolAddress,
            'school_motto' => $schoolMotto,
            'brand_color' => $brandColor,
            'settings' => $settings,
            'objectives' => $latestVersion?->learning_objectives ?? $lessonNote->learning_objectives ?? [],
            'content' => $latestVersion?->content ?? '',
            'title' => $latestVersion?->title ?? ($lessonNote->subject->name . ' - Week ' . $lessonNote->week_number),
            'image_urls' => $latestVersion ? $latestVersion->getImageUrls() : [],
        ];

        $pdf = Pdf::loadView('pdf.lesson-note', $data)
            ->setPaper('a4', 'portrait');

        $filename = sprintf(
            '%s_%s_Week_%d_Lesson_Note.pdf',
            str_replace(' ', '_', $lessonNote->subject->name ?? 'Subject'),
            str_replace(' ', '_', $lessonNote->classroom->name ?? 'Class'),
            $lessonNote->week_number
        );

        return $pdf->download($filename);
    }
}
