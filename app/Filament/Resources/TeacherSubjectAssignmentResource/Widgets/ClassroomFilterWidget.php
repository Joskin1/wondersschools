<?php

namespace App\Filament\Resources\TeacherSubjectAssignmentResource\Widgets;

use App\Models\Classroom;
use App\Models\Session;
use App\Models\TeacherSubjectAssignment;
use Filament\Widgets\Widget;
use Livewire\Attributes\Url;

class ClassroomFilterWidget extends Widget
{
    protected string $view = 'filament.widgets.classroom-filter';

    protected int | string | array $columnSpan = 'full';

    #[Url]
    public ?int $selectedClassroom = null;

    public function getClassrooms()
    {
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        if (!$activeSession || !$activeTerm) {
            return Classroom::active()->ordered()->get();
        }

        // Get classrooms with assignment counts
        return Classroom::active()->ordered()
            ->withCount([
                'assignments' => function ($query) use ($activeSession, $activeTerm) {
                    $query->where('session_id', $activeSession->id)
                          ->where('term_id', $activeTerm->id);
                }
            ])
            ->get();
    }

    public function selectClassroom(?int $classroomId)
    {
        $this->selectedClassroom = $classroomId;
        $this->dispatch('classroom-filter-updated', classroomId: $classroomId);
    }

    public function clearFilter()
    {
        $this->selectedClassroom = null;
        $this->dispatch('classroom-filter-updated', classroomId: null);
    }
}
