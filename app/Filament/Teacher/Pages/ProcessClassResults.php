<?php

namespace App\Filament\Teacher\Pages;

use App\Models\Session;
use App\Models\Classroom;
use App\Models\ClassTeacherAssignment;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Services\ResultCalculationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ProcessClassResults extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-check-badge';

    protected static string | \UnitEnum | null $navigationGroup = 'Results';

    protected static ?string $navigationLabel = 'Process Class Results';

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.teacher.pages.process-class-results';

    public static function canAccess(): bool
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

    public ?int $session_id = null;
    public ?int $term_id = null;
    public ?int $classroom_id = null;

    public function mount(): void
    {
        $activeSession = Session::where('is_active', true)->first();
        if ($activeSession) {
            $this->session_id = $activeSession->id;
            $activeTerm = Term::where('session_id', $activeSession->id)
                ->where('is_active', true)
                ->first();
            if ($activeTerm) {
                $this->term_id = $activeTerm->id;
            }
        }

        $user = Auth::user();
        if ($user && $user->isTeacher()) {
            $assignedClassId = ClassTeacherAssignment::where('teacher_id', $user->id)
                ->where('session_id', $this->session_id)
                ->value('class_id');
            if ($assignedClassId) {
                $this->classroom_id = $assignedClassId;
            }
        }
    }

    public function getSessionsProperty()
    {
        return Session::orderByDesc('start_year')->get();
    }

    public function getTermsProperty()
    {
        if (! $this->session_id) {
            return collect();
        }

        return Term::where('session_id', $this->session_id)
            ->orderBy('order')
            ->get();
    }

    public function getClassroomsProperty()
    {
        $user = Auth::user();
        if ($user->canManageAcademics()) {
            return Classroom::orderBy('name')->get();
        }

        $assignedIds = ClassTeacherAssignment::where('teacher_id', $user->id)
            ->where('session_id', $this->session_id)
            ->pluck('class_id');

        return Classroom::whereIn('id', $assignedIds)->orderBy('name')->get();
    }

    public function getSubjectStatusListProperty(): array
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return [];
        }

        $classroom = Classroom::with('subjects')->find($this->classroom_id);
        if (! $classroom) {
            return [];
        }

        $publishedSubjectIds = SubjectResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('is_published', true)
            ->distinct()
            ->pluck('subject_id')
            ->toArray();

        $list = [];
        foreach ($classroom->subjects as $subject) {
            $isPublished = in_array($subject->id, $publishedSubjectIds);
            $list[] = [
                'id'           => $subject->id,
                'name'         => $subject->name,
                'code'         => $subject->code ?? '',
                'is_published' => $isPublished,
            ];
        }

        return $list;
    }

    public function getIsClassFinalizedProperty(): bool
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return false;
        }

        return TermResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('is_finalized', true)
            ->exists();
    }

    public function processClassResults(): void
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and classroom.')->warning()->send();
            return;
        }

        try {
            app(ResultCalculationService::class)->calculateForClass(
                $this->classroom_id,
                $this->session_id,
                $this->term_id
            );

            Notification::make()
                ->title('Class results processed and published successfully!')
                ->body('Students can now view their term results.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Processing Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
