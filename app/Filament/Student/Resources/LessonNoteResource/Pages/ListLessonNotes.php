<?php

namespace App\Filament\Student\Resources\LessonNoteResource\Pages;

use App\Filament\Student\Resources\LessonNoteResource;
use App\Models\LessonNote;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class ListLessonNotes extends Page
{
    protected static string $resource = LessonNoteResource::class;

    protected string $view = 'filament.student.pages.list-lesson-notes';

    protected static ?string $title = 'My Lesson Notes';

    public ?int $selectedSubjectId = null;

    public ?int $viewingNoteId = null;

    public bool $showViewModal = false;

    public function mount(): void
    {
        $subjects = $this->getSubjects();

        $paramSubjectId = (int) request()->query('subject');
        if ($paramSubjectId && $subjects->contains('id', $paramSubjectId)) {
            $this->selectedSubjectId = $paramSubjectId;
        } elseif ($subjects->isNotEmpty()) {
            $firstWithNotes = $subjects->firstWhere('approved_notes_count', '>', 0);
            $this->selectedSubjectId = $firstWithNotes ? $firstWithNotes->id : $subjects->first()->id;
        }

        $paramNoteId = (int) request()->query('note');
        if ($paramNoteId) {
            $this->viewNote($paramNoteId);
        }
    }

    public function selectSubject(int $subjectId): void
    {
        $this->selectedSubjectId = $subjectId;
        $this->showViewModal = false;
        $this->viewingNoteId = null;
    }

    public function getSubjects(): Collection
    {
        $student = auth()->user()?->student;
        if (! $student) {
            return new Collection();
        }

        $enrollment = $student->currentEnrollment();
        if (! $enrollment) {
            return new Collection();
        }

        $classroomId = $enrollment->classroom_id;

        // Get subjects from teacher assignments for this classroom
        $assignedSubjectIds = TeacherSubjectAssignment::where('classroom_id', $classroomId)
            ->active()
            ->pluck('subject_id')
            ->unique();

        // Also merge any subject that has approved lesson notes for this classroom
        $noteSubjectIds = LessonNote::where('classroom_id', $classroomId)
            ->approved()
            ->active()
            ->pluck('subject_id')
            ->unique();

        $allSubjectIds = $assignedSubjectIds->merge($noteSubjectIds)->unique()->filter();

        return Subject::whereIn('id', $allSubjectIds)
            ->withCount(['lessonNotes as approved_notes_count' => function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId)
                  ->approved()
                  ->active();
            }])
            ->orderBy('name')
            ->get();
    }

    public function getWeeklyNotes(): Collection
    {
        if (! $this->selectedSubjectId) {
            return new Collection();
        }

        $student = auth()->user()?->student;
        if (! $student) {
            return new Collection();
        }

        $enrollment = $student->currentEnrollment();
        if (! $enrollment) {
            return new Collection();
        }

        return LessonNote::query()
            ->where('classroom_id', $enrollment->classroom_id)
            ->where('subject_id', $this->selectedSubjectId)
            ->approved()
            ->active()
            ->with(['teacher', 'latestVersion'])
            ->orderBy('week_number', 'asc')
            ->get();
    }

    public function getSelectedSubject(): ?Subject
    {
        return $this->selectedSubjectId ? Subject::find($this->selectedSubjectId) : null;
    }

    public function viewNote(int $noteId): void
    {
        $student = auth()->user()?->student;
        $enrollment = $student?->currentEnrollment();

        if (! $enrollment) {
            return;
        }

        // STRICT: Only approved notes for this student's classroom are viewable
        $note = LessonNote::with(['subject', 'teacher', 'latestVersion'])
            ->where('id', $noteId)
            ->where('classroom_id', $enrollment->classroom_id)
            ->where('status', 'approved')
            ->first();

        if (! $note) {
            return;
        }

        $this->selectedSubjectId = $note->subject_id;
        $this->viewingNoteId = $note->id;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewingNoteId = null;
    }

    public function getViewingNote(): ?LessonNote
    {
        if (! $this->viewingNoteId) {
            return null;
        }

        $student = auth()->user()?->student;
        $enrollment = $student?->currentEnrollment();

        if (! $enrollment) {
            return null;
        }

        return LessonNote::with(['subject', 'teacher', 'latestVersion'])
            ->where('id', $this->viewingNoteId)
            ->where('classroom_id', $enrollment->classroom_id)
            ->where('status', 'approved')
            ->first();
    }
}
