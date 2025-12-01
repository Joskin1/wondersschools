<?php

namespace App\Filament\Resources\ScoreResource\Pages;

use App\Filament\Resources\ScoreResource;
use App\Models\AssessmentType;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class BulkScoreInput extends Page
{
    use InteractsWithForms;

    protected static string $resource = ScoreResource::class;

    protected string $view = 'filament.resources.score-resource.pages.bulk-score-input';

    public function getTitle(): string
    {
        return 'Bulk Score Input';
    }

    public ?int $academicSessionId = null;
    public ?int $termId = null;
    public ?int $classroomId = null;
    public ?int $subjectId = null;
    public $students = [];
    public $assessmentTypes = [];
    public $scores = [];

    public function mount(): void
    {
        // Set defaults to current session and term
        $this->academicSessionId = \App\Models\AcademicSession::where('is_current', true)->first()?->id;
        $this->termId = \App\Models\Term::where('is_current', true)->first()?->id;
        $this->loadStudents();
    }

    public function updatedClassroomId(): void
    {
        $this->loadStudents();
    }

    public function updatedSubjectId(): void
    {
        $this->loadStudents();
    }

    public function updatedAcademicSessionId(): void
    {
        $this->loadStudents();
    }

    public function updatedTermId(): void
    {
        $this->loadStudents();
    }

    public function loadStudents(): void
    {
        if ($this->classroomId && $this->subjectId) {
            $this->students = Student::where('classroom_id', $this->classroomId)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            $this->assessmentTypes = AssessmentType::where('is_active', true)
                ->orderBy('name')
                ->get();

            // Load existing scores
            foreach ($this->students as $student) {
                foreach ($this->assessmentTypes as $assessmentType) {
                    $score = Score::where('student_id', $student->id)
                        ->where('subject_id', $this->subjectId)
                        ->where('assessment_type_id', $assessmentType->id)
                        ->first();

                    $this->scores[$student->id][$assessmentType->id] = $score?->score ?? null;
                }
            }
        } else {
            $this->students = [];
            $this->assessmentTypes = [];
        }
    }

    public function save(): void
    {
        if (!$this->academicSessionId || !$this->termId || !$this->classroomId || !$this->subjectId) {
            Notification::make()
                ->title('Please select session, term, classroom and subject')
                ->danger()
                ->send();
            return;
        }

        foreach ($this->scores as $studentId => $assessmentScores) {
            foreach ($assessmentScores as $assessmentTypeId => $scoreValue) {
                if ($scoreValue !== null && $scoreValue !== '') {
                    // Validate score against max_score
                    $assessmentType = AssessmentType::find($assessmentTypeId);
                    if ($scoreValue > $assessmentType->max_score) {
                        Notification::make()
                            ->title("Score for student ID {$studentId} exceeds maximum for {$assessmentType->name}")
                            ->danger()
                            ->send();
                        return;
                    }

                    Score::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'subject_id' => $this->subjectId,
                            'assessment_type_id' => $assessmentTypeId,
                            'academic_session_id' => $this->academicSessionId,
                            'term_id' => $this->termId,
                        ],
                        [
                            'score' => $scoreValue,
                        ]
                    );
                }
            }
        }

        Notification::make()
            ->title('Scores saved successfully')
            ->success()
            ->send();

        $this->loadStudents(); // Reload to show updated scores
    }
}
