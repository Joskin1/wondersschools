<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = App\Models\Tenant::find('soso');
tenancy()->initialize($tenant);

// Check the student linked to fesewypad@mailinator.com
$user = App\Models\User::where('email', 'fesewypad@mailinator.com')->first();
echo "User: {$user->name} (ID:{$user->id}, email:{$user->email})\n";
$student = $user->student;
echo "Student: " . ($student ? "ID:{$student->id} Name:{$student->full_name}" : "NONE") . "\n";

if ($student) {
    $trs = App\Models\TermResult::where('student_id', $student->id)->get();
    echo "TermResults: " . $trs->count() . "\n";
    foreach ($trs as $tr) {
        $term = App\Models\Term::find($tr->term_id);
        echo "  Term:{$term->name} avg:{$tr->average} pos:{$tr->overall_position}\n";
    }
}
