<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = App\Models\Tenant::find('soso');
tenancy()->initialize($tenant);

$studentId = 70;
$sessionId = 1;
$classId = App\Models\StudentEnrollment::where('student_id', $studentId)->where('session_id', $sessionId)->value('classroom_id');
$teacherId = App\Models\User::first()->id;

echo "Student {$studentId} is in class {$classId}, using teacher {$teacherId}\n";

$terms = [1, 2, 3];
$subjects = clone App\Models\Subject::take(5)->pluck('id');

foreach ($terms as $termId) {
    App\Models\Term::where('id', $termId)->update(['is_active' => true]);

    $structure = App\Models\ClassScoreStructure::firstOrCreate([
        'class_id' => $classId,
        'session_id' => $sessionId,
        'term_id' => $termId,
    ], ['total_score' => 100]);
    $structure->locked = true;
    $structure->save();
    
    // Add items: CA1 (id:1) and Exam (id:2)
    App\Models\ClassScoreStructureItem::firstOrCreate([
        'class_score_structure_id' => $structure->id,
        'score_head_id' => 1,
    ], ['max_score' => 40]);
    App\Models\ClassScoreStructureItem::firstOrCreate([
        'class_score_structure_id' => $structure->id,
        'score_head_id' => 2,
    ], ['max_score' => 60]);

    foreach ($subjects as $subjectId) {
        // Insert CA1
        App\Models\Score::updateOrCreate([
            'student_id' => $studentId,
            'classroom_id' => $classId,
            'subject_id' => $subjectId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'score_head_id' => 1,
        ], ['teacher_id' => $teacherId, 'score' => rand(20, 39)]);
        
        // Insert Exam
        App\Models\Score::updateOrCreate([
            'student_id' => $studentId,
            'classroom_id' => $classId,
            'subject_id' => $subjectId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'score_head_id' => 2,
        ], ['teacher_id' => $teacherId, 'score' => rand(30, 59)]);
    }

    // Run calculation
    $service = app(\App\Services\ResultCalculationService::class);
    $service->calculateForClass($classId, $sessionId, $termId);
    echo "Calculated results for Term {$termId}\n";
    
    $tr = App\Models\TermResult::where('student_id', $studentId)->where('term_id', $termId)->first();
    echo "  -> Average: " . ($tr ? $tr->average : 'None') . "\n";
}
