<?php

namespace App\Filament\Resources\ScoreResource\Pages;

use App\Filament\Resources\ScoreResource;
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
    public $evaluationSettings = [];
    public $availableClassrooms = [];
    public $scores = [];

    public function mount(): void
    {
        // Set defaults to current session and term
        $this->academicSessionId = \App\Models\AcademicSession::where('is_current', true)->first()?->id;
        $this->termId = \App\Models\Term::where('is_current', true)->first()?->id;
        $this->loadAvailableClassrooms();
        $this->loadStudents();
    }

    /**
     * Load classrooms available to the current user.
     * Teachers can only see their assigned classrooms.
     */
    public function loadAvailableClassrooms(): void
    {
        $user = Auth::user();
        
        if ($user && $user->staff && $user->staff->classrooms()->exists()) {
            // Teacher: only their assigned classrooms
            $this->availableClassrooms = $user->staff->classrooms()->pluck('name', 'classrooms.id');
        } else {
            // Admin: all classrooms
            $this->availableClassrooms = Classroom::pluck('name', 'id');
        }
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
        if ($this->classroomId && $this->subjectId && $this->academicSessionId) {
            $this->students = Student::where('classroom_id', $this->classroomId)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            $this->evaluationSettings = \App\Models\EvaluationSetting::where('academic_session_id', $this->academicSessionId)
                ->get()
                ->keyBy('name')
                ->toArray();

            // Load existing scores
            foreach ($this->students as $student) {
                $score = Score::where('student_id', $student->id)
                    ->where('subject_id', $this->subjectId)
                    ->where('academic_session_id', $this->academicSessionId)
                    ->where('term_id', $this->termId)
                    ->first();

                $this->scores[$student->id]['ca_score'] = $score?->ca_score;
                $this->scores[$student->id]['exam_score'] = $score?->exam_score;
            }
        } else {
            $this->students = [];
            $this->evaluationSettings = [];
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

        // Authorization check: ensure teacher can only save scores for their assigned classrooms
        $user = Auth::user();
        if ($user && $user->staff && $user->staff->classrooms()->exists()) {
            $classroomIds = $user->staff->classrooms()->pluck('classrooms.id');
            if (!$classroomIds->contains($this->classroomId)) {
                Notification::make()
                    ->title('Unauthorized: You can only input scores for your assigned classrooms')
                    ->danger()
                    ->send();
                return;
            }
        }

        foreach ($this->scores as $studentId => $studentScores) {
            $caScore = $studentScores['ca_score'] ?? null;
            $examScore = $studentScores['exam_score'] ?? null;

            if (($caScore !== null && $caScore !== '') || ($examScore !== null && $examScore !== '')) {
                
                // Validate CA Score
                if (isset($this->evaluationSettings['CA'])) {
                    $maxCa = $this->evaluationSettings['CA']['max_score'];
                    if ($caScore > $maxCa) {
                        Notification::make()
                            ->title("CA Score for student ID {$studentId} exceeds maximum of {$maxCa}")
                            ->danger()
                            ->send();
                        return;
                    }
                }

                // Validate Exam Score
                if (isset($this->evaluationSettings['Exam'])) {
                    $maxExam = $this->evaluationSettings['Exam']['max_score'];
                    if ($examScore > $maxExam) {
                        Notification::make()
                            ->title("Exam Score for student ID {$studentId} exceeds maximum of {$maxExam}")
                            ->danger()
                            ->send();
                        return;
                    }
                }

                Score::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $this->subjectId,
                        'academic_session_id' => $this->academicSessionId,
                        'term_id' => $this->termId,
                    ],
                    [
                        'ca_score' => $caScore,
                        'exam_score' => $examScore,
                        'teacher_id' => $user->staff ? $user->staff->id : null,
                    ]
                );
            }
        }

        Notification::make()
            ->title('Scores saved successfully')
            ->success()
            ->send();

        $this->loadStudents(); // Reload to show updated scores
    }
}
