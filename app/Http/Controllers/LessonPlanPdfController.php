<?php

namespace App\Http\Controllers;

use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonPlanPdfController extends Controller
{
    public function download(Request $request, LessonNote $lessonNote)
    {
        $plan = $lessonNote->getPairedLessonPlan();
        if (! $plan) {
            abort(404, 'No paired lesson plan found for this lesson note.');
        }

        return $this->downloadPlan($request, $plan);
    }

    public function downloadPlan(Request $request, LessonPlan $lessonPlan)
    {
        $user = Auth::user();

        if (! $user) {
            abort(401);
        }

        // Resilient fallback in case route model binding didn't resolve due to parameter name variations
        if (! $lessonPlan->exists) {
            $routeParam = $request->route('lessonPlan') ?? $request->route('plan') ?? $request->route('id');
            if ($routeParam) {
                $lessonPlan = $routeParam instanceof LessonPlan ? $routeParam : LessonPlan::findOrFail($routeParam);
            }
        }

        if ($user->role === 'student') {
            if ($lessonPlan->status !== 'approved') {
                abort(403, 'Only approved lesson plans can be viewed by students.');
            }

            $student = $user->student;
            $enrollment = $student?->currentEnrollment();

            if (! $enrollment || $lessonPlan->classroom_id !== $enrollment->classroom_id) {
                abort(403, 'Access denied.');
            }
        } elseif ($user->role === 'teacher') {
            if ($lessonPlan->teacher_id !== $user->id && ! in_array($user->role, ['admin', 'sudo'], true)) {
                abort(403, 'You do not have permission to download this lesson plan.');
            }
        } elseif (! in_array($user->role, ['admin', 'sudo'], true)) {
            abort(403, 'Unauthorized.');
        }

        $lessonPlan->load([
            'teacher',
            'subject',
            'classroom',
            'session',
            'term',
            'referenceMaterials',
            'instructionalMaterials',
            'teachingMethods',
        ]);

        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $schoolName = $settings['school_name'] ?? config('app.name', 'School Portal');

        $pdf = Pdf::loadView('pdf.lesson-plan', [
            'plan' => $lessonPlan,
            'school_name' => $schoolName,
            'school_logo' => $settings['school_logo'] ?? null,
            'school_address' => $settings['school_address'] ?? null,
            'school_motto' => $settings['school_motto'] ?? null,
            'brand_color' => $settings['primary_color'] ?? '#1a365d',
        ])->setPaper('a4', 'portrait');

        $subjectName = str_replace([' ', '/', '\\', '&', '+'], '_', $lessonPlan->subject?->name ?? 'Subject');
        $className = str_replace([' ', '/', '\\', '&', '+'], '_', $lessonPlan->classroom?->name ?? 'Class');

        $filename = sprintf(
            '%s_Week_%d_%s_Lesson_Plan.pdf',
            $subjectName,
            $lessonPlan->week_number,
            $className
        );

        return $pdf->download($filename);
    }
}
