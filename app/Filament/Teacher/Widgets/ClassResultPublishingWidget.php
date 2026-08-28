<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\Session;
use App\Models\Classroom;
use App\Models\ClassTeacherAssignment;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ClassResultPublishingWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->canManageAcademics()) {
            return true;
        }

        return ClassTeacherAssignment::where('teacher_id', $user->id)->exists();
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $activeSession = Session::where('is_active', true)->first();
        if (! $activeSession) {
            return [];
        }

        $activeTerm = Term::where('session_id', $activeSession->id)
            ->where('is_active', true)
            ->first();

        if (! $activeTerm) {
            return [];
        }

        // Get classrooms where this user is Class Teacher
        $assignedClassroomIds = ClassTeacherAssignment::where('teacher_id', $user->id)
            ->where('session_id', $activeSession->id)
            ->pluck('class_id');

        if ($assignedClassroomIds->isEmpty() && $user->canManageAcademics()) {
            // Fallback for admins: pick all classrooms
            $assignedClassroomIds = Classroom::pluck('id');
        }

        if ($assignedClassroomIds->isEmpty()) {
            return [
                Stat::make('Class Results Status', 'N/A')
                    ->description('No assigned classroom as Class Teacher for current session')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('secondary'),
            ];
        }

        $stats = [];

        foreach ($assignedClassroomIds as $classId) {
            $classroom = Classroom::with('subjects')->find($classId);
            if (! $classroom) {
                continue;
            }

            $totalSubjects = $classroom->subjects->count();

            $publishedSubjectsCount = SubjectResult::where('classroom_id', $classId)
                ->where('session_id', $activeSession->id)
                ->where('term_id', $activeTerm->id)
                ->where('is_published', true)
                ->distinct('subject_id')
                ->count('subject_id');

            $isClassFinalized = TermResult::where('classroom_id', $classId)
                ->where('session_id', $activeSession->id)
                ->where('term_id', $activeTerm->id)
                ->where('is_finalized', true)
                ->exists();

            $isComplete = ($totalSubjects > 0) && ($publishedSubjectsCount >= $totalSubjects);

            $stats[] = Stat::make(
                "{$classroom->name} Subject Scores",
                "{$publishedSubjectsCount} / {$totalSubjects}"
            )
                ->description("{$publishedSubjectsCount} of {$totalSubjects} subject scores published by subject teachers")
                ->icon('heroicon-o-document-chart-bar')
                ->color($isComplete ? 'success' : 'warning');

            $stats[] = Stat::make(
                "{$classroom->name} Class Finalization",
                $isClassFinalized ? 'Published' : 'Pending Finalization'
            )
                ->description($isClassFinalized ? 'Results are published to students' : 'Awaiting Class Teacher finalization')
                ->icon($isClassFinalized ? 'heroicon-o-check-badge' : 'heroicon-o-clock')
                ->color($isClassFinalized ? 'success' : 'warning')
                ->url(route('filament.teacher.pages.process-class-results', ['classroom_id' => $classId]));
        }

        return $stats;
    }
}
