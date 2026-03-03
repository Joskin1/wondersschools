<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = App\Models\Tenant::find('soso');
tenancy()->initialize($tenant);

$studentId = 70;
$sessionId = 1;

// Simulate the PDF controller logic to build $data
function buildData($studentId, $sessionId, $termId) {
    $student = App\Models\User::find(11)->student;
    $term = App\Models\Term::find($termId);
    $termResult = App\Models\TermResult::where('student_id', $student->id)->where('term_id', $termId)->first();
    $classroomId = App\Models\StudentEnrollment::where('student_id', $student->id)->value('classroom_id');
    
    $isCumulative = $term->order === 3;
    $scoreHeads = [];
    if (!$isCumulative) {
        $structure = App\Models\ClassScoreStructure::where('class_id', $classroomId)->where('term_id', $termId)->first();
        $scoreHeads = App\Models\ClassScoreStructureItem::where('class_score_structure_id', $structure->id)
            ->join('score_heads', 'score_heads.id', '=', 'class_score_structure_items.score_head_id')
            ->select('score_heads.id', 'score_heads.name')
            ->get()->toArray();
    }
    
    $subjectResults = App\Models\SubjectResult::where('student_id', $student->id)->where('term_id', $termId)->with('subject')->get();
    $subjectRows = [];
    
    if ($isCumulative) {
        $service = app(\App\Services\ResultCalculationService::class);
        $termIds = $service->resolveTermIds($sessionId);
        $priorResults = [];
        $priorRows = App\Models\SubjectResult::where('student_id', $student->id)->whereIn('term_id', [$termIds[1], $termIds[2]])->get();
        foreach ($priorRows as $pr) {
            $priorResults[$pr->subject_id][$pr->term_id] = (float) $pr->total;
        }
        $term3RawScores = App\Models\Score::where('student_id', $student->id)->where('term_id', $termId)
            ->groupBy('subject_id')->selectRaw('subject_id, SUM(score) as subject_total')->pluck('subject_total', 'subject_id');
            
        foreach ($subjectResults as $sr) {
            $sid = $sr->subject_id;
            $subjectRows[] = [
                'subject' => $sr->subject->name,
                'term1' => $priorResults[$sid][$termIds[1]] ?? 0,
                'term2' => $priorResults[$sid][$termIds[2]] ?? 0,
                'term3_raw' => round($term3RawScores[$sid] ?? 0, 2),
                'average' => (float)$sr->total,
                'grade' => $sr->grade, 'position' => $sr->position, 'remark' => $sr->remark,
            ];
        }
    } else {
        foreach ($subjectResults as $sr) {
            $row = ['subject' => $sr->subject->name, 'total' => $sr->total, 'grade' => $sr->grade, 'position' => $sr->position, 'remark' => $sr->remark, 'scores' => []];
            foreach ($scoreHeads as $sh) {
                $score = App\Models\Score::where('student_id', $student->id)->where('subject_id', $sr->subject_id)->where('score_head_id', $sh['id'])->where('term_id', $termId)->first();
                $row['scores'][$sh['id']] = $score ? $score->score : '-';
            }
            $subjectRows[] = $row;
        }
    }
    
    return [
        'is_cumulative' => $isCumulative,
        'score_heads' => $scoreHeads,
        'subjects' => $subjectRows,
        'student' => ['name' => 'Browser Test', 'gender' => 'M', 'dob' => '2010'],
        'term_result' => ['average' => $termResult->average, 'grand_total' => $termResult->grand_total, 'grade' => 'A', 'remark' => 'Good', 'overall_position' => 1, 'subjects_count' => 5],
        'classroom' => 'JSS 1', 'session_name' => '2026', 'term_name' => $term->name,
        'class_size' => 1, 'total_obtainable' => 500, 'settings' => []
    ];
}

// Render Term 1
$dataT1 = buildData($studentId, $sessionId, 1);
$htmlT1 = view('results.result-sheet', ['data' => $dataT1])->render();
echo "TERM 1 EXPECTED HEADERS (CA1, Exam, Total):\n";
echo strpos($htmlT1, '<th>CA1</th>') !== false ? "  CA1 found\n" : "  CA1 missing\n";
echo strpos($htmlT1, '<th>Exam</th>') !== false ? "  Exam found\n" : "  Exam missing\n";
echo strpos($htmlT1, '<th>Total</th>') !== false ? "  Total found\n" : "  Total missing\n";
echo strpos($htmlT1, '<th>1st Term</th>') === false ? "  1st Term implicitly omitted\n" : "  1st Term INCORRECTLY found\n";
echo strpos($htmlT1, '<td class="total-col">77.2</td>') !== false ? "  Total 77.2 found\n" : "  Total 77.2 missing\n";

echo "\n--------------------------------\n\n";

// Render Term 3
$dataT3 = buildData($studentId, $sessionId, 3);
$htmlT3 = view('results.result-sheet', ['data' => $dataT3])->render();
echo "TERM 3 EXPECTED HEADERS (1st Term, 2nd Term, 3rd Term, Average):\n";
echo strpos($htmlT3, '<th>1st Term</th>') !== false ? "  1st Term found\n" : "  1st Term missing\n";
echo strpos($htmlT3, '<th>2nd Term</th>') !== false ? "  2nd Term found\n" : "  2nd Term missing\n";
echo strpos($htmlT3, '<th>3rd Term</th>') !== false ? "  3rd Term found\n" : "  3rd Term missing\n";
echo strpos($htmlT3, '<th>Average</th>') !== false ? "  Average found\n" : "  Average missing\n";
echo strpos($htmlT3, '<th>CA1</th>') === false ? "  CA1 implicitly omitted\n" : "  CA1 INCORRECTLY found\n";

// Dump one subject row representation from Term 3 to verify the view
echo "\nSample Term 3 Subject Row Data:\n";
print_r($dataT3['subjects'][0]);
