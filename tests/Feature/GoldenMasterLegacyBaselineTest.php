<?php

use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\ScoreHead;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ResultCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = Session::create([
        'name'       => 'GM-2025/2026',
        'start_year' => 2025,
        'end_year'   => 2026,
        'is_active'  => true,
    ]);

    $this->term1 = Term::create(['session_id' => $this->session->id, 'name' => 'First Term', 'order' => 1, 'is_active' => false]);
    $this->term2 = Term::create(['session_id' => $this->session->id, 'name' => 'Second Term', 'order' => 2, 'is_active' => false]);
    $this->term3 = Term::create(['session_id' => $this->session->id, 'name' => 'Third Term', 'order' => 3, 'is_active' => true]);

    $this->classroom = Classroom::create(['name' => 'GM JSS 1', 'class_order' => 99, 'is_active' => true]);
    $this->teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

    $this->math = Subject::create(['name' => 'GM Mathematics', 'code' => 'GMMTH', 'is_active' => true]);
    $this->english = Subject::create(['name' => 'GM English Language', 'code' => 'GMENG', 'is_active' => true]);
    $this->classroom->subjects()->attach([$this->math->id, $this->english->id]);

    $this->alice = Student::create(['first_name' => 'Alice', 'last_name' => 'Adenuga', 'admission_number' => 'GM-001', 'is_active' => true]);
    $this->bob   = Student::create(['first_name' => 'Bob',   'last_name' => 'Bello',   'admission_number' => 'GM-002', 'is_active' => true]);
    $this->carol = Student::create(['first_name' => 'Carol', 'last_name' => 'Chukwu',  'admission_number' => 'GM-003', 'is_active' => true]);

    foreach ([$this->alice, $this->bob, $this->carol] as $st) {
        StudentEnrollment::create([
            'student_id'   => $st->id,
            'classroom_id' => $this->classroom->id,
            'session_id'   => $this->session->id,
        ]);
    }

    $this->cw = ScoreHead::create(['name' => 'GM Classwork',  'max_score' => 20, 'type' => 'exam', 'is_active' => true]);
    $this->as = ScoreHead::create(['name' => 'GM Assignment', 'max_score' => 20, 'type' => 'exam', 'is_active' => true]);
    $this->ex = ScoreHead::create(['name' => 'GM Exam',       'max_score' => 60, 'type' => 'exam', 'is_active' => true]);

    foreach ([$this->term1, $this->term2, $this->term3] as $t) {
        $str = ClassScoreStructure::create([
            'class_id'    => $this->classroom->id,
            'session_id'  => $this->session->id,
            'term_id'     => $t->id,
            'total_score' => 100,
            'locked'      => true,
        ]);
        ClassScoreStructureItem::create(['class_score_structure_id' => $str->id, 'score_head_id' => $this->cw->id]);
        ClassScoreStructureItem::create(['class_score_structure_id' => $str->id, 'score_head_id' => $this->as->id]);
        ClassScoreStructureItem::create(['class_score_structure_id' => $str->id, 'score_head_id' => $this->ex->id]);
    }

    $this->service = app(ResultCalculationService::class);
});

it('matches the expanded golden-master legacy baseline output exactly (tied positions, decimals, Term 3 cumulative)', function () {
    // ── TERM 1 (Tied positions & decimals) ──────────────────────────
    $term1Scores = [
        [$this->alice->id, $this->math->id, 18.0, 17.0, 45.0],
        [$this->bob->id,   $this->math->id, 18.0, 17.0, 45.0],
        [$this->carol->id, $this->math->id, 15.5, 16.5, 38.0],
        [$this->alice->id, $this->english->id, 16.0, 18.0, 46.0],
        [$this->bob->id,   $this->english->id, 16.0, 18.0, 46.0],
        [$this->carol->id, $this->english->id, 14.5, 15.5, 35.0],
    ];

    foreach ($term1Scores as [$studentId, $subjectId, $c, $a, $e]) {
        Score::create(['student_id' => $studentId, 'classroom_id' => $this->classroom->id, 'subject_id' => $subjectId, 'score_head_id' => $this->cw->id, 'session_id' => $this->session->id, 'term_id' => $this->term1->id, 'teacher_id' => $this->teacher->id, 'score' => $c]);
        Score::create(['student_id' => $studentId, 'classroom_id' => $this->classroom->id, 'subject_id' => $subjectId, 'score_head_id' => $this->as->id, 'session_id' => $this->session->id, 'term_id' => $this->term1->id, 'teacher_id' => $this->teacher->id, 'score' => $a]);
        Score::create(['student_id' => $studentId, 'classroom_id' => $this->classroom->id, 'subject_id' => $subjectId, 'score_head_id' => $this->ex->id, 'session_id' => $this->session->id, 'term_id' => $this->term1->id, 'teacher_id' => $this->teacher->id, 'score' => $e]);
    }

    $this->service->calculateForClass($this->classroom->id, $this->session->id, $this->term1->id);

    // ── TERM 2 ──────────────────────────────────────────────────────
    $term2Scores = [
        [$this->alice->id, $this->math->id, 19.0, 18.0, 48.0],
        [$this->bob->id,   $this->math->id, 17.0, 16.0, 42.0],
        [$this->carol->id, $this->math->id, 16.5, 17.5, 41.0],
        [$this->alice->id, $this->english->id, 18.0, 17.0, 47.0],
        [$this->bob->id,   $this->english->id, 18.0, 18.0, 44.0],
        [$this->carol->id, $this->english->id, 15.5, 16.5, 38.0],
    ];

    foreach ($term2Scores as [$studentId, $subjectId, $c, $a, $e]) {
        Score::create(['student_id' => $studentId, 'classroom_id' => $this->classroom->id, 'subject_id' => $subjectId, 'score_head_id' => $this->cw->id, 'session_id' => $this->session->id, 'term_id' => $this->term2->id, 'teacher_id' => $this->teacher->id, 'score' => $c]);
        Score::create(['student_id' => $studentId, 'classroom_id' => $this->classroom->id, 'subject_id' => $subjectId, 'score_head_id' => $this->as->id, 'session_id' => $this->session->id, 'term_id' => $this->term2->id, 'teacher_id' => $this->teacher->id, 'score' => $a]);
        Score::create(['student_id' => $studentId, 'classroom_id' => $this->classroom->id, 'subject_id' => $subjectId, 'score_head_id' => $this->ex->id, 'session_id' => $this->session->id, 'term_id' => $this->term2->id, 'teacher_id' => $this->teacher->id, 'score' => $e]);
    }

    $this->service->calculateForClass($this->classroom->id, $this->session->id, $this->term2->id);

    // ── TERM 3 (Cumulative) ─────────────────────────────────────────
    $term3Scores = [
        [$this->alice->id, $this->math->id, 20.0, 20.0, 50.0],
        [$this->bob->id,   $this->math->id, 18.0, 18.0, 49.0],
        [$this->carol->id, $this->math->id, 17.5, 18.5, 44.0],
        [$this->alice->id, $this->english->id, 19.0, 19.0, 48.0],
        [$this->bob->id,   $this->english->id, 17.0, 17.0, 46.0],
        [$this->carol->id, $this->english->id, 16.5, 17.5, 41.0],
    ];

    foreach ($term3Scores as [$studentId, $subjectId, $c, $a, $e]) {
        Score::create(['student_id' => $studentId, 'classroom_id' => $this->classroom->id, 'subject_id' => $subjectId, 'score_head_id' => $this->cw->id, 'session_id' => $this->session->id, 'term_id' => $this->term3->id, 'teacher_id' => $this->teacher->id, 'score' => $c]);
        Score::create(['student_id' => $studentId, 'classroom_id' => $this->classroom->id, 'subject_id' => $subjectId, 'score_head_id' => $this->as->id, 'session_id' => $this->session->id, 'term_id' => $this->term3->id, 'teacher_id' => $this->teacher->id, 'score' => $a]);
        Score::create(['student_id' => $studentId, 'classroom_id' => $this->classroom->id, 'subject_id' => $subjectId, 'score_head_id' => $this->ex->id, 'session_id' => $this->session->id, 'term_id' => $this->term3->id, 'teacher_id' => $this->teacher->id, 'score' => $e]);
    }

    $this->service->calculateForClass($this->classroom->id, $this->session->id, $this->term3->id);

    $studentsMap = [$this->alice->id => 'student_1', $this->bob->id => 'student_2', $this->carol->id => 'student_3'];
    $subjectsMap = [$this->math->id => 'subject_math', $this->english->id => 'subject_english'];
    $termsMap    = [$this->term1->id => 'term_1', $this->term2->id => 'term_2', $this->term3->id => 'term_3'];

    $allSubjectResults = SubjectResult::where('classroom_id', $this->classroom->id)
        ->where('session_id', $this->session->id)
        ->orderBy('term_id')
        ->orderBy('student_id')
        ->orderBy('subject_id')
        ->get()
        ->map(function ($r) use ($studentsMap, $subjectsMap, $termsMap) {
            return [
                'term'         => $termsMap[$r->term_id] ?? $r->term_id,
                'student'      => $studentsMap[$r->student_id] ?? $r->student_id,
                'subject'      => $subjectsMap[$r->subject_id] ?? $r->subject_id,
                'total'        => (float) $r->total,
                'grade'        => $r->grade,
                'remark'       => $r->remark,
                'position'     => $r->position,
                'is_published' => (bool) $r->is_published,
            ];
        })->values()->toArray();

    $allTermResults = TermResult::where('classroom_id', $this->classroom->id)
        ->where('session_id', $this->session->id)
        ->orderBy('term_id')
        ->orderBy('student_id')
        ->get()
        ->map(function ($r) use ($studentsMap, $termsMap) {
            return [
                'term'             => $termsMap[$r->term_id] ?? $r->term_id,
                'student'          => $studentsMap[$r->student_id] ?? $r->student_id,
                'subjects_count'   => (int) $r->subjects_count,
                'grand_total'      => (float) $r->grand_total,
                'average'          => (float) $r->average,
                'grade'            => $r->grade,
                'remark'           => $r->remark,
                'overall_position' => (int) $r->overall_position,
                'is_finalized'     => (bool) $r->is_finalized,
            ];
        })->values()->toArray();

    $payload = [
        'subject_results' => $allSubjectResults,
        'term_results'    => $allTermResults,
    ];

    $fixturePath = base_path('tests/Fixtures/legacy_results_golden_master.json');
    $expected = json_decode(file_get_contents($fixturePath), true);

    expect($payload)->toEqual($expected);
});
