<?php

use App\Models\Tenant;
use App\Models\Setting;
use App\Services\ReportCardSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Initialize Tenant
$tenant = Tenant::find('chizylite') ?? Tenant::first();
if ($tenant) {
    tenancy()->initialize($tenant);
    echo "Tenant initialized: {$tenant->id} ({$tenant->name})\n";
}

$outputDir = public_path('downloads/results');
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Ensure settings
$schoolName = 'Cathedral Church of Our Saviour College';
$schoolMotto = 'Knowledge, Character and the Fear of God';
$schoolAddress = 'Cathedral Grounds, Ejinrin Road, Ijebu-Ode, Ogun State, Nigeria';
$schoolPhone = '+234 803 300 4567';
$schoolEmail = 'admissions@cathedralcollege.edu.ng';
$primaryColor = '#3D2606'; // Majestic Cathedral Bronze / Gold
$secondaryColor = '#1A1104';
$accentColor = '#D4AF37';

$allSettings = ReportCardSettingsService::loadSettings();
$settings = array_merge($allSettings, [
    'school_name'      => $schoolName,
    'school_short_name'=> 'CCOSC',
    'school_motto'     => $schoolMotto,
    'school_address'   => $schoolAddress,
    'school_phone'     => $schoolPhone,
    'school_email'     => $schoolEmail,
    'primary_color'    => $primaryColor,
    'secondary_color'  => $secondaryColor,
    'accent_color'     => $accentColor,
    'rc_show_school_logo' => false, // Set cleanly if no physical logo file
    'rc_show_student_photo' => false,
    'rc_show_school_stamp' => true,
    'rc_show_teacher_signature' => true,
    'rc_show_principal_signature' => true,
    'rc_page_margin_top' => 14,
    'rc_page_margin_bottom' => 14,
    'rc_page_margin_left' => 14,
    'rc_page_margin_right' => 14,
]);

$marginCss = ReportCardSettingsService::getMarginCss($settings);

// Sample Student Data
$student = [
    'name'           => 'ADEBANJO OLUWATIMILEHIN MALIK',
    'gender'         => 'Male',
    'dob'            => '14/05/2010',
    'admission_no'   => 'CCOSC/2026/SS2/002',
    'department'     => 'Science',
    'arm'            => 'Gold',
    'passport_photo' => null,
];

// ─────────────────────────────────────────────────────────────────────────────
// 1. GENERATE FIRST TERM TERMINAL REPORT CARD
// ─────────────────────────────────────────────────────────────────────────────

$scoreHeads = [
    ['id' => 1, 'name' => '1st CA', 'max_score' => 20],
    ['id' => 2, 'name' => '2nd CA', 'max_score' => 20],
    ['id' => 3, 'name' => 'Exam',   'max_score' => 60],
];

$rawSubjects = [
    ['name' => 'English Language',      'c1' => 18, 'c2' => 17, 'exam' => 52, 'remark' => 'Excellent command of lexis and structure'],
    ['name' => 'Mathematics',           'c1' => 19, 'c2' => 20, 'exam' => 57, 'remark' => 'Outstanding problem-solving capabilities'],
    ['name' => 'Physics',               'c1' => 17, 'c2' => 18, 'exam' => 54, 'remark' => 'Great grasp of theoretical mechanics'],
    ['name' => 'Chemistry',             'c1' => 18, 'c2' => 19, 'exam' => 53, 'remark' => 'Very good laboratory and analytical skills'],
    ['name' => 'Biology',               'c1' => 16, 'c2' => 18, 'exam' => 50, 'remark' => 'Commendable biological diagrams & explanations'],
    ['name' => 'Further Mathematics',   'c1' => 19, 'c2' => 18, 'exam' => 55, 'remark' => 'Exceptional aptitude in advanced calculus'],
    ['name' => 'Civic Education',       'c1' => 18, 'c2' => 19, 'exam' => 51, 'remark' => 'Active participant with deep civic understanding'],
    ['name' => 'Data Processing',       'c1' => 20, 'c2' => 19, 'exam' => 56, 'remark' => 'Distinction in computer applications and algorithms'],
    ['name' => 'Economics',             'c1' => 17, 'c2' => 17, 'exam' => 49, 'remark' => 'Very thoughtful economic analysis and reasoning'],
];

$subjects = [];
$totalObtained = 0;
$totalObtainable = count($rawSubjects) * 100;

foreach ($rawSubjects as $index => $item) {
    $total = $item['c1'] + $item['c2'] + $item['exam'];
    $totalObtained += $total;

    $grade = 'A1';
    if ($total < 50) $grade = 'C6';
    elseif ($total < 60) $grade = 'C4';
    elseif ($total < 70) $grade = 'B3';
    elseif ($total < 75) $grade = 'B2';

    $posSuffix = ($index === 0) ? '1st' : (($index === 1) ? '1st' : (($index === 2) ? '2nd' : '3rd'));

    $subjects[] = [
        'subject'           => $item['name'],
        'head_scores'       => [
            1 => $item['c1'],
            2 => $item['c2'],
            3 => $item['exam'],
        ],
        'total'             => $total,
        'grade'             => $grade,
        'remark'            => $item['remark'],
        'position'          => $posSuffix,
        'students_examined' => 32,
    ];
}

$average = round($totalObtained / count($rawSubjects), 2);

$terminalData = [
    'student'               => $student,
    'classroom'             => 'SS 2',
    'class_size'            => 32,
    'arm_population'        => 20,
    'department_population' => 15,
    'session_name'          => '2026/2027',
    'term_name'             => 'First Term',
    'is_cumulative'         => false,
    'score_heads'           => $scoreHeads,
    'subjects'              => $subjects,
    'times_school_opened'   => 120,
    'next_term_begins'      => '11th January, 2027',
    'term_result'           => [
        'subjects_count'        => count($subjects),
        'grand_total'           => $totalObtained,
        'average'               => $average,
        'grade'                 => 'A1',
        'remark'                => 'An exceptional performance! A model student of high academic discipline.',
        'overall_position'      => '1st',
        'class_population'      => 32,
        'department_position'   => '1st',
        'department_population' => 15,
        'arm_position'          => '1st',
        'arm_population'        => 20,
        'times_present'         => 118,
        'class_teacher_comment' => 'Malik is a brilliant, respectful, and highly focused student. He demonstrates exemplary leadership in all academic activities.',
        'principal_comment'     => 'A stellar and well-deserved terminal result. Congratulations on setting the standard for your set. Keep shining!',
    ],
    'total_obtainable'      => $totalObtainable,
    'settings'              => $settings,
    'margin_css'            => $marginCss,
];

echo "Rendering First Term Terminal Report Card PDF...\n";
$terminalPdf = Pdf::loadView('results.result-sheet', ['data' => $terminalData])
    ->setPaper('a4', 'portrait');

$terminalPath = "{$outputDir}/Cathedral_First_Term_Terminal_Result_Sample.pdf";
$terminalPdf->save($terminalPath);
echo "Saved: {$terminalPath} (" . filesize($terminalPath) . " bytes)\n";

// ─────────────────────────────────────────────────────────────────────────────
// 2. GENERATE MID-TERM ACADEMIC PROGRESS REPORT
// ─────────────────────────────────────────────────────────────────────────────

$midtermHeads = [
    ['id' => 1, 'name' => '1st CA', 'max_score' => 20, 'effective_max' => 20],
    ['id' => 2, 'name' => '2nd CA', 'max_score' => 20, 'effective_max' => 20],
];

$midtermSubjects = [];
$midtermTotalObtained = 0;
$midtermTotalObtainable = count($rawSubjects) * 40;

foreach ($rawSubjects as $item) {
    $rawScore = $item['c1'] + $item['c2'];
    $midtermTotalObtained += $rawScore;
    $scaledScore = round(($rawScore / 40) * 30, 1); // 30% weight
    $percentage  = round(($rawScore / 40) * 100, 1);

    $midtermSubjects[] = [
        'subject'         => $item['name'],
        'head_scores'     => [
            1 => $item['c1'],
            2 => $item['c2'],
        ],
        'raw_score'       => $rawScore,
        'student_raw_max' => 40,
        'scaled_score'    => $scaledScore,
        'percentage'      => $percentage,
        'grade'           => $percentage >= 75 ? 'A' : ($percentage >= 65 ? 'B' : 'C'),
        'remark'          => $item['remark'],
    ];
}

$midtermPercentage = round(($midtermTotalObtained / $midtermTotalObtainable) * 100, 1);

$midtermData = [
    'report_title'          => 'FIRST TERM MID-TERM ACADEMIC PROGRESS REPORT',
    'student'               => $student,
    'classroom'             => 'SS 2',
    'class_size'            => 32,
    'arm_population'        => 20,
    'department_population' => 15,
    'session_name'          => '2026/2027',
    'term_name'             => 'First Term',
    'midterm_weight'        => 30,
    'raw_max'               => 40,
    'midterm_heads'         => $midtermHeads,
    'subjects'              => $midtermSubjects,
    'term_result'           => [
        'subjects_count'        => count($midtermSubjects),
        'total_obtainable'      => $midtermTotalObtainable,
        'total_obtained'        => $midtermTotalObtained,
        'percentage'            => $midtermPercentage,
        'grade'                 => 'A (Distinction)',
        'remark'                => 'Outstanding mid-term mastery across all subjects.',
        'class_population'      => 32,
        'arm_population'        => 20,
        'department_population' => 15,
        'teacher_comment'       => 'Consistently diligent and inquisitive. He has maintained an exceptional score sheet throughout mid-term assessments.',
        'head_teacher_comment'  => 'A commendable start to the academic term. Continue this standard of discipline through to final examinations.',
    ],
    'settings'              => $settings,
];

echo "Rendering Mid-Term Progress Report PDF...\n";
$midtermPdf = Pdf::loadView('results.midterm-slip', ['data' => $midtermData])
    ->setPaper('a4', 'portrait');

$midtermPath = "{$outputDir}/Cathedral_Mid_Term_Progress_Report_Sample.pdf";
$midtermPdf->save($midtermPath);
echo "Saved: {$midtermPath} (" . filesize($midtermPath) . " bytes)\n";

echo "Both sample PDFs generated successfully!\n";
