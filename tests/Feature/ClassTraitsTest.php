<?php

use App\Filament\Teacher\Pages\ProcessClassResults;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ClassResultPdfService;
use App\Services\ClassTraitsSpreadsheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

function setupTraitsTestContext(): array
{
    $session = Session::create([
        'name'       => '2025-2026',
        'start_year' => 2025,
        'end_year'   => 2026,
        'is_active'  => true,
    ]);

    $term = Term::create([
        'session_id' => $session->id,
        'name'       => 'First Term',
        'order'      => 1,
        'is_active'  => true,
    ]);

    $classroom = Classroom::create(['name' => 'Year 3 Gold', 'class_order' => 3, 'is_active' => true]);

    $teacher = User::factory()->create(['role' => 'admin']);

    $students = [];
    for ($i = 1; $i <= 3; $i++) {
        $user = User::factory()->create([
            'name'  => "Student {$i} Test",
            'email' => "student{$i}@traits.test",
        ]);

        $student = Student::create([
            'user_id'          => $user->id,
            'admission_number' => "TRAIT202600{$i}",
        ]);

        StudentEnrollment::create([
            'student_id'   => $student->id,
            'classroom_id' => $classroom->id,
            'session_id'   => $session->id,
            'is_active'    => true,
        ]);

        TermResult::create([
            'student_id'   => $student->id,
            'classroom_id' => $classroom->id,
            'session_id'   => $session->id,
            'term_id'      => $term->id,
            'is_finalized' => false,
        ]);

        $students[] = $student;
    }

    return compact('session', 'term', 'classroom', 'teacher', 'students');
}

test('traits spreadsheet service generates a valid excel template', function () {
    $ctx = setupTraitsTestContext();
    $service = app(ClassTraitsSpreadsheetService::class);

    $tempPath = $service->exportToTempFile(
        $ctx['classroom']->id,
        $ctx['session']->id,
        $ctx['term']->id
    );

    expect(file_exists($tempPath))->toBeTrue();

    $spreadsheet = IOFactory::load($tempPath);
    $sheet = $spreadsheet->getActiveSheet();

    // Check title and headers
    expect($sheet->getCell('A1')->getValue())->toContain('BEHAVIORAL TRAITS');
    expect($sheet->getCell('A3')->getValue())->toBe('STUDENT INFORMATION');
    expect($sheet->getCell('A4')->getValue())->toBe('S/N');
    expect($sheet->getCell('B4')->getValue())->toBe('Admission No');
    expect($sheet->getCell('C4')->getValue())->toBe('Student Full Name');
    expect($sheet->getCell('E4')->getValue())->toContain('Punctuality');
    expect($sheet->getCell('K4')->getValue())->toContain('Handwriting');

    // 3 students -> rows 5, 6, 7
    expect($sheet->getCell('B5')->getValue())->toBe('TRAIT2026001');
    expect($sheet->getCell('B6')->getValue())->toBe('TRAIT2026002');
    expect($sheet->getCell('B7')->getValue())->toBe('TRAIT2026003');

    unlink($tempPath);
});

test('traits spreadsheet service imports ratings correctly and validates 1-5 scale', function () {
    $ctx = setupTraitsTestContext();
    $service = app(ClassTraitsSpreadsheetService::class);

    $tempPath = $service->exportToTempFile(
        $ctx['classroom']->id,
        $ctx['session']->id,
        $ctx['term']->id
    );

    $spreadsheet = IOFactory::load($tempPath);
    $sheet = $spreadsheet->getActiveSheet();

    // Student 1 (Row 5): Set affective to 5, psychomotor to 4 (valid)
    $sheet->setCellValue('E5', 5); // punctuality
    $sheet->setCellValue('F5', 5); // neatness
    $sheet->setCellValue('G5', 4); // attentiveness
    $sheet->setCellValue('H5', 5); // honesty
    $sheet->setCellValue('I5', 4); // peers
    $sheet->setCellValue('J5', 5); // leadership
    $sheet->setCellValue('K5', 4); // handwriting
    $sheet->setCellValue('L5', 5); // sports
    $sheet->setCellValue('M5', 4); // crafts
    $sheet->setCellValue('N5', 5); // fluency
    $sheet->setCellValue('O5', 4); // musical
    $sheet->setCellValue('P5', 5); // practical

    // Student 2 (Row 6): Set an invalid rating (e.g., 9) -> row should be rejected
    $sheet->setCellValue('E6', 9); // invalid!
    $sheet->setCellValue('F6', 3);

    // Student 3 (Row 7): Valid ratings
    $sheet->setCellValue('E7', 4);
    $sheet->setCellValue('K7', 5);

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($tempPath);

    $result = $service->importSpreadsheet(
        $tempPath,
        $ctx['classroom']->id,
        $ctx['session']->id,
        $ctx['term']->id
    );

    // Student 1 and Student 3 are updated, Student 2 rejected with error
    expect($result['updated'])->toBe(2);
    expect($result['errors'])->not->toBeEmpty();
    expect($result['errors'][0])->toContain("Row 6 (Student 2 Test): 'punctuality' must be between 1 and 5 (got '9').");

    // Verify Student 1 in DB
    $tr1 = TermResult::where('student_id', $ctx['students'][0]->id)
        ->where('term_id', $ctx['term']->id)
        ->first();

    expect($tr1->affective_domain)->toBeArray();
    expect($tr1->affective_domain['punctuality'])->toBe(5);
    expect($tr1->affective_domain['attentiveness'])->toBe(4);
    expect($tr1->psychomotor_skills['handwriting'])->toBe(4);
    expect($tr1->psychomotor_skills['sports'])->toBe(5);

    // Verify Student 2 was NOT updated because row was rejected
    $tr2 = TermResult::where('student_id', $ctx['students'][1]->id)
        ->where('term_id', $ctx['term']->id)
        ->first();

    expect($tr2->affective_domain)->toBeNull();

    // Verify Student 3 was updated
    $tr3 = TermResult::where('student_id', $ctx['students'][2]->id)
        ->where('term_id', $ctx['term']->id)
        ->first();

    expect($tr3->affective_domain['punctuality'])->toBe(4);
    expect($tr3->psychomotor_skills['handwriting'])->toBe(5);

    unlink($tempPath);
});

test('process class results component can prefill and save traits online', function () {
    $ctx = setupTraitsTestContext();

    Livewire::actingAs($ctx['teacher'])
        ->test(ProcessClassResults::class, [
            'session_id'   => $ctx['session']->id,
            'term_id'      => $ctx['term']->id,
            'classroom_id' => $ctx['classroom']->id,
        ])
        ->assertOk()
        ->call('prefillAllTraits', 5)
        ->set("traits.{$ctx['students'][0]->id}.affective.punctuality", 4)
        ->set("traits.{$ctx['students'][0]->id}.psychomotor.sports", 3)
        ->call('saveTraits')
        ->assertNotified('Behavioral traits & skills ratings saved successfully!');

    $tr1 = TermResult::where('student_id', $ctx['students'][0]->id)
        ->where('term_id', $ctx['term']->id)
        ->first();

    expect($tr1->affective_domain['punctuality'])->toBe(4);
    expect($tr1->affective_domain['neatness'])->toBe(5);
    expect($tr1->psychomotor_skills['sports'])->toBe(3);
    expect($tr1->psychomotor_skills['crafts'])->toBe(5);

    $tr2 = TermResult::where('student_id', $ctx['students'][1]->id)
        ->where('term_id', $ctx['term']->id)
        ->first();

    expect($tr2->affective_domain['punctuality'])->toBe(5);
    expect($tr2->psychomotor_skills['sports'])->toBe(5);
});

test('class result pdf service includes traits in terminal class data', function () {
    $ctx = setupTraitsTestContext();

    // Set custom traits on Student 1
    $tr1 = TermResult::where('student_id', $ctx['students'][0]->id)
        ->where('term_id', $ctx['term']->id)
        ->first();

    $tr1->affective_domain = ['punctuality' => 3, 'neatness' => 4];
    $tr1->psychomotor_skills = ['handwriting' => 2, 'sports' => 5];
    $tr1->save();

    $pdfService = app(ClassResultPdfService::class);
    $batchData = $pdfService->buildTerminalClassData(
        $ctx['classroom']->id,
        $ctx['session']->id,
        $ctx['term']->id
    );

    $studentData = collect($batchData['studentsData'])->first(function ($s) use ($ctx) {
        return $s['student']['id'] === $ctx['students'][0]->id;
    });

    expect($studentData)->not->toBeNull();
    expect($studentData['term_result']['affective_domain'])->toBeArray();
    expect($studentData['term_result']['affective_domain']['punctuality'])->toBe(3);
    expect($studentData['term_result']['psychomotor_skills']['handwriting'])->toBe(2);
});

test('report card blade partial renders custom traits accurately', function () {
    $ctx = setupTraitsTestContext();

    $data = [
        'settings' => [
            'rc_show_affective_domain'   => true,
            'rc_show_psychomotor_skills' => true,
            'rc_show_domain_scale'       => true,
        ],
        'student' => [
            'id'               => $ctx['students'][0]->id,
            'name'             => 'Test Student',
            'admission_number' => 'TRAIT001',
        ],
        'term_result' => [
            'times_present'         => 80,
            'class_teacher_comment' => 'Great performance!',
            'principal_comment'     => 'Keep it up!',
            'affective_domain'      => [
                'punctuality'             => 3,
                'neatness'                => 2,
                'attentiveness'           => 5,
                'honesty'                 => 4,
                'relationship_with_peers' => 1,
                'leadership'              => 5,
            ],
            'psychomotor_skills'    => [
                'handwriting'      => 2,
                'sports'           => 3,
                'crafts'           => 4,
                'verbal_fluency'   => 5,
                'musical_skills'   => 1,
                'practical_skills' => 4,
            ],
        ],
        'times_school_opened' => 90,
    ];

    $rendered = view('results.partials.report-card-content', ['data' => $data])->render();

    expect($rendered)->toContain('Affective Domain (Character &amp; Conduct)');
    expect($rendered)->toContain('Psychomotor Skills &amp; Activities');
    expect($rendered)->toContain('Punctuality &amp; Regularity');
    expect($rendered)->toContain('Neatness &amp; Personal Hygiene');
    expect($rendered)->toContain('Rating (1 - 5)');
});
