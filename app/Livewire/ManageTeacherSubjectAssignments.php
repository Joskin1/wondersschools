<?php

namespace App\Livewire;

use App\Models\Classroom;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class ManageTeacherSubjectAssignments extends Component
{
    use WithPagination;

    public ?int $selectedClassroom = null;
    public $showCreateModal = false;
    
    // Form fields
    public $teacher_id;
    public $subject_id;
    public $classroom_id;
    public $session_id;
    public $term_id;

    protected $queryString = ['selectedClassroom'];

    public function mount()
    {
        $activeSession = Session::active()->first();
        $this->session_id = $activeSession?->id;
        $this->term_id = $activeSession?->activeTerm?->id;
    }

    public function selectClassroom(?int $classroomId)
    {
        $this->selectedClassroom = $classroomId;
        $this->resetPage();
    }

    public function clearFilter()
    {
        $this->selectedClassroom = null;
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->showCreateModal = true;
        $this->classroom_id = $this->selectedClassroom;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->reset(['teacher_id', 'subject_id', 'classroom_id']);
    }

    public function save()
    {
        $this->validate([
            'teacher_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'session_id' => 'required|exists:academic_sessions,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher_id,
            'subject_id' => $this->subject_id,
            'classroom_id' => $this->classroom_id,
            'session_id' => $this->session_id,
            'term_id' => $this->term_id,
        ]);

        $this->closeCreateModal();
        session()->flash('success', 'Teacher assigned successfully!');
    }

    public function delete($id)
    {
        TeacherSubjectAssignment::findOrFail($id)->delete();
        session()->flash('success', 'Assignment deleted successfully!');
    }

    public function render()
    {
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        $classrooms = Classroom::active()->ordered()
            ->withCount([
                'assignments' => function ($query) use ($activeSession, $activeTerm) {
                    if ($activeSession && $activeTerm) {
                        $query->where('session_id', $activeSession->id)
                              ->where('term_id', $activeTerm->id);
                    }
                }
            ])
            ->get();

        $assignmentsQuery = TeacherSubjectAssignment::with(['teacher', 'subject', 'classroom', 'session', 'term']);

        if ($this->selectedClassroom) {
            $assignmentsQuery->where('classroom_id', $this->selectedClassroom);
        }

        if ($activeSession && $activeTerm) {
            $assignmentsQuery->where('session_id', $activeSession->id)
                             ->where('term_id', $activeTerm->id);
        }

        $assignments = $assignmentsQuery->latest()->paginate(15);

        return view('livewire.manage-teacher-subject-assignments', [
            'classrooms' => $classrooms,
            'assignments' => $assignments,
            'teachers' => User::activeTeachers()->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'sessions' => Session::orderBy('start_year', 'desc')->get(),
            'terms' => Term::where('session_id', $this->session_id)->orderBy('order')->get(),
        ]);
    }
}
