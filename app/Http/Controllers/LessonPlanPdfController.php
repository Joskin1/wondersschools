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

    public function downloadPlan(Request $request, LessonPlan $plan)
    {
        $user = Auth::user();

        if (! $user) {
            abort(401);
        }

        if ($user->role === 'student') {
            if ($plan->status !== 'approved') {
                abort(403, 'Only approved lesson plans can be viewed by students.');
            }

            $student = $user->student;
            $enrollment = $student?->currentEnrollment();

            if (! $enrollment || $plan->classroom_id !== $enrollment->classroom_id) {
                abort(403, 'Access denied.');
            }
        } elseif ($user->role === 'teacher') {
            if ($plan->teacher_id !== $user->id && ! in_array($user->role, ['admin', 'sudo'], true)) {
                abort(403, 'You do not have permission to download this lesson plan.');
            }
        } elseif (! in_array($user->role, ['admin', 'sudo'], true)) {
            abort(403, 'Unauthorized.');
        }

        $plan->load([
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
            'plan' => $plan,
            'school_name' => $schoolName,
            'school_logo' => $settings['school_logo'] ?? null,
            'school_address' => $settings['school_address'] ?? null,
            'school_motto' => $settings['school_motto'] ?? null,
            'brand_color' => $settings['primary_color'] ?? '#1a365d',
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            '%s_%s_Week_%d_Lesson_Plan.pdf',
            str_replace(' ', '_', $plan->subject?->name ?? 'Subject'),
            str_replace(' ', '_', $plan->classroom?->name ?? 'Class'),
            $plan->week_number
        );

        return $pdf->download($filename);
    }
}
