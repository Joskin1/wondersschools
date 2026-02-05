<?php

namespace App\Filament\Resources\ScoreResource\Pages;

use App\Filament\Resources\ScoreResource;
use App\Models\AcademicSession;
use App\Models\Classroom;
use App\Models\EvaluationSetting;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class BulkScoreInput extends Page
{
    protected static string $resource = ScoreResource::class;

    protected string $view = 'filament.resources.score-resource.pages.bulk-score-input';

    public ?int $academicSessionId = null;
    public ?int $termId = null;
    public ?int $classroomId = null;
    public ?int $subjectId = null;
    public array $scores = [];
    public $students = [];
    public array $evaluationSettings = [];
    public array $availableClassrooms = [];

    public function mount(): void
    {
        // Set current session and term if available
        $currentSession = AcademicSession::where('is_current', true)->first();
        $currentTerm = Term::where('is_current', true)->first();

        if ($currentSession) {
            $this->academicSessionId = $currentSession->id;
        }

        if ($currentTerm) {
            $this->termId = $currentTerm->id;
        }

        $this->loadAvailableClassrooms();
    }

    public function updatedAcademicSessionId(): void
    {
        $this->loadEvaluationSettings();
        $this->loadStudents();
    }

    public function updatedTermId(): void
    {
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

    public function loadAvailableClassrooms(): void
    {
        $this->availableClassrooms = Classroom::pluck('name', 'id')->toArray();
    }

    public function loadEvaluationSettings(): void
    {
        if (!$this->academicSessionId) {
            $this->evaluationSettings = [];
            return;
        }

        $settings = EvaluationSetting::where('academic_session_id', $this->academicSessionId)
            ->get();

        $this->evaluationSettings = [];
        foreach ($settings as $setting) {
            $this->evaluationSettings[$setting->name] = [
                'id' => $setting->id,
                'max_score' => $setting->max_score,
            ];
        }
    }

    public function loadStudents(): void
    {
        if (!$this->academicSessionId || !$this->termId || !$this->classroomId || !$this->subjectId) {
            $this->students = [];
            return;
        }

        $this->students = Student::where('classroom_id', $this->classroomId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Load existing scores
        $existingScores = Score::where('academic_session_id', $this->academicSessionId)
            ->where('term_id', $this->termId)
            ->where('subject_id', $this->subjectId)
            ->get()
            ->keyBy('student_id');

        foreach ($this->students as $student) {
            $studentId = $student->id;
            if (isset($existingScores[$studentId])) {
                $this->scores[$studentId] = [
                    'ca_score' => $existingScores[$studentId]->ca_score ?? 0,
                    'exam_score' => $existingScores[$studentId]->exam_score ?? 0,
                ];
            } else {
                $this->scores[$studentId] = [
                    'ca_score' => 0,
                    'exam_score' => 0,
                ];
            }
        }

        $this->loadEvaluationSettings();
    }

    public function save(): void
    {
        if (!$this->academicSessionId || !$this->termId || !$this->classroomId || !$this->subjectId) {
            $this->addError('general', 'Please select session, term, classroom, and subject.');
            return;
        }

        DB::transaction(function () {
            foreach ($this->scores as $studentId => $scoreData) {
                Score::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $this->subjectId,
                        'academic_session_id' => $this->academicSessionId,
                        'term_id' => $this->termId,
                    ],
                    [
                        'ca_score' => $scoreData['ca_score'] ?? 0,
                        'exam_score' => $scoreData['exam_score'] ?? 0,
                    ]
                );
            }
        });

        Notification::make()
            ->title('Scores saved successfully!')
            ->success()
            ->send();
    }
}
