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

    public ?int $selectedNoteId = null;

    public function mount(): void
    {
        $subjects = $this->getSubjects();

        $paramSubjectId = (int) request()->query('subject');
        $paramNoteId = (int) request()->query('note');

        if ($paramSubjectId && $subjects->contains('id', $paramSubjectId)) {
            $this->selectedSubjectId = $paramSubjectId;
        } elseif ($subjects->isNotEmpty()) {
            $firstWithNotes = $subjects->firstWhere('approved_notes_count', '>', 0);
            $this->selectedSubjectId = $firstWithNotes ? $firstWithNotes->id : $subjects->first()->id;
        }

        if ($paramNoteId) {
            $this->selectNote($paramNoteId);
        } else {
            // Auto-select the first available weekly note for the selected subject
            $notes = $this->getWeeklyNotes();
            if ($notes->isNotEmpty()) {
                $this->selectedNoteId = $notes->first()->id;
            }
        }
    }

    public function selectSubject(int $subjectId): void
    {
        $this->selectedSubjectId = $subjectId;
        
        // Auto-select the first available week note for this subject
        $notes = $this->getWeeklyNotes();
        $this->selectedNoteId = $notes->isNotEmpty() ? $notes->first()->id : null;
    }

    public function selectNote(int $noteId): void
    {
        $student = auth()->user()?->student;
        $enrollment = $student?->currentEnrollment();

        if (! $enrollment) {
            return;
        }

        // STRICT: Verify note belongs to student's classroom and is approved
        $note = LessonNote::where('id', $noteId)
            ->where('classroom_id', $enrollment->classroom_id)
            ->where('status', 'approved')
            ->first();

        if ($note) {
            $this->selectedSubjectId = $note->subject_id;
            $this->selectedNoteId = $note->id;
        }
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

        $assignedSubjectIds = TeacherSubjectAssignment::where('classroom_id', $classroomId)
            ->active()
            ->pluck('subject_id')
            ->unique();

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

    public function getSelectedNote(): ?LessonNote
    {
        if (! $this->selectedNoteId) {
            // Fallback to first available note
            $notes = $this->getWeeklyNotes();
            if ($notes->isNotEmpty()) {
                $this->selectedNoteId = $notes->first()->id;
            } else {
                return null;
            }
        }

        $student = auth()->user()?->student;
        $enrollment = $student?->currentEnrollment();

        if (! $enrollment) {
            return null;
        }

        return LessonNote::with(['subject', 'teacher', 'latestVersion', 'classroom'])
            ->where('id', $this->selectedNoteId)
            ->where('classroom_id', $enrollment->classroom_id)
            ->where('status', 'approved')
            ->first();
    }

    public function goToPreviousWeek(): void
    {
        $notes = $this->getWeeklyNotes();
        $currentIndex = $notes->search(fn ($n) => $n->id === $this->selectedNoteId);

        if ($currentIndex !== false && $currentIndex > 0) {
            $this->selectedNoteId = $notes[$currentIndex - 1]->id;
        }
    }

    public function goToNextWeek(): void
    {
        $notes = $this->getWeeklyNotes();
        $currentIndex = $notes->search(fn ($n) => $n->id === $this->selectedNoteId);

        if ($currentIndex !== false && $currentIndex < $notes->count() - 1) {
            $this->selectedNoteId = $notes[$currentIndex + 1]->id;
        }
    }
}
