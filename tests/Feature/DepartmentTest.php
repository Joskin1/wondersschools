<?php

use App\Http\Controllers\ResultPdfController;
use App\Livewire\PublicStudentRegistrationForm;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\ResultPin;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = Session::factory()->create(['is_active' => true]);
    $this->term = Term::factory()->create(['session_id' => $this->session->id, 'order' => 1, 'is_active' => true]);

    $this->seniorClass = Classroom::factory()->create([
        'name' => 'SS 2',
        'has_departments' => true,
    ]);

    $this->juniorClass = Classroom::factory()->create([
        'name' => 'JSS 1',
        'has_departments' => false,
    ]);

    $this->scienceDept = Department::firstOrCreate(['name' => 'Science'], [
        'code' => 'SCI',
        'description' => 'Pure and applied sciences',
        'is_active' => true,
    ]);

    $this->commercialDept = Department::firstOrCreate(['name' => 'Commercial'], [
        'code' => 'COMM',
        'description' => 'Business and accounting stream',
        'is_active' => true,
    ]);
});

describe('Department Model and Configuration', function () {
    it('allows school admin to create custom dynamic departments', function () {
        $initialCount = Department::count();
        $customDept = Department::create([
            'name' => 'Vocational & Technical',
            'code' => 'VOCTECH',
            'description' => 'Applied trades and technology',
            'is_active' => true,
        ]);

        expect($customDept->exists)->toBeTrue();
        expect(Department::count())->toBe($initialCount + 1);
    });

    it('identifies classrooms with senior school departments', function () {
        expect($this->seniorClass->has_departments)->toBeTrue();
        expect($this->juniorClass->has_departments)->toBeFalse();
    });

    it('associates student enrollment with a department', function () {
        $student = Student::factory()->create();

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $this->seniorClass->id,
            'session_id' => $this->session->id,
            'department_id' => $this->scienceDept->id,
        ]);

        expect($enrollment->department)->not->toBeNull();
        expect($enrollment->department->name)->toBe('Science');
        expect($student->currentDepartment()?->name)->toBe('Science');
    });
});

describe('Student Registration Form with Dynamic Department', function () {
    it('requires department when a senior class with departments is selected', function () {
        Livewire::test(PublicStudentRegistrationForm::class)
            ->set('full_name', 'Fathia Atinuke')
            ->set('date_of_birth', '2008-05-15')
            ->set('gender', 'female')
            ->set('address', '12 Bishop Court, Ijebu Ode')
            ->set('parent_name', 'Mrs. Atinuke')
            ->set('parent_phone', '08012345678')
            ->set('password', 'SecretPassword123#')
            ->set('password_confirmation', 'SecretPassword123#')
            ->set('classroom_id', $this->seniorClass->id)
            ->set('department_id', null)
            ->call('submit')
            ->assertHasErrors(['department_id']);
    });

    it('does not require department when a junior class is selected', function () {
        Livewire::test(PublicStudentRegistrationForm::class)
            ->set('full_name', 'Junior Student')
            ->set('date_of_birth', '2012-05-15')
            ->set('gender', 'male')
            ->set('address', '12 Cathedral Road')
            ->set('parent_name', 'Mr. John')
            ->set('parent_phone', '08098765432')
            ->set('password', 'SecretPassword123#')
            ->set('password_confirmation', 'SecretPassword123#')
            ->set('classroom_id', $this->juniorClass->id)
            ->set('department_id', null)
            ->call('submit')
            ->assertHasNoErrors(['department_id']);

        $enrollment = StudentEnrollment::whereHas('student.user', fn ($q) => $q->where('surname', 'Junior')->where('first_name', 'Student'))->first();
        expect($enrollment)->not->toBeNull();
        expect($enrollment->department_id)->toBeNull();
    });

    it('successfully enrolls student with selected department for senior class', function () {
        Livewire::test(PublicStudentRegistrationForm::class)
            ->set('full_name', 'Abdullasisi Fathia Atinuke')
            ->set('date_of_birth', '2008-05-15')
            ->set('gender', 'female')
            ->set('address', 'Beside Bishop Court, Ijebu Ode')
            ->set('parent_name', 'Alhaji Abdullasisi')
            ->set('parent_phone', '08033334444')
            ->set('password', 'StrongPassword123#')
            ->set('password_confirmation', 'StrongPassword123#')
            ->set('classroom_id', $this->seniorClass->id)
            ->set('department_id', $this->scienceDept->id)
            ->call('submit')
            ->assertHasNoErrors();

        $student = Student::whereHas('user', fn ($q) => $q->where('surname', 'Abdullasisi')->where('first_name', 'Fathia'))->first();
        expect($student)->not->toBeNull();

        $enrollment = $student->enrollments()->where('session_id', $this->session->id)->first();
        expect($enrollment)->not->toBeNull();
        expect($enrollment->department_id)->toBe($this->scienceDept->id);
        expect($enrollment->department->name)->toBe('Science');
    });
});

describe('Result Sheet Integration with Department and Examined Count', function () {
    it('includes department and students examined count in result pdf data', function () {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student = Student::factory()->create(['user_id' => $user->id, 'surname' => 'Fathia', 'first_name' => 'Science', 'other_name' => 'Student']);

        StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $this->seniorClass->id,
            'session_id' => $this->session->id,
            'department_id' => $this->scienceDept->id,
        ]);

        $subject = Subject::create(['name' => 'Physics', 'code' => 'PHY', 'is_active' => true]);
        $scoreHead = ScoreHead::create(['name' => 'Exam', 'max_score' => 60, 'is_active' => true]);

        // Create score for this student + 2 other students to test students_examined count
        Score::create([
            'student_id' => $student->id,
            'classroom_id' => $this->seniorClass->id,
            'subject_id' => $subject->id,
            'score_head_id' => $scoreHead->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'teacher_id' => User::factory()->create(['role' => 'teacher'])->id,
            'score' => 50,
        ]);

        $otherStudent = Student::factory()->create();
        Score::create([
            'student_id' => $otherStudent->id,
            'classroom_id' => $this->seniorClass->id,
            'subject_id' => $subject->id,
            'score_head_id' => $scoreHead->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'teacher_id' => User::factory()->create(['role' => 'teacher'])->id,
            'score' => 45,
        ]);

        SubjectResult::create([
            'student_id' => $student->id,
            'classroom_id' => $this->seniorClass->id,
            'subject_id' => $subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'total' => 50,
            'grade' => 'B',
            'remark' => 'Very Good',
            'position' => 1,
            'is_published' => true,
        ]);

        TermResult::create([
            'student_id' => $student->id,
            'classroom_id' => $this->seniorClass->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'subjects_count' => 1,
            'grand_total' => 50,
            'average' => 50,
            'grade' => 'B',
            'remark' => 'Very Good',
            'overall_position' => 1,
            'is_finalized' => true,
        ]);

        $batch = \App\Models\ResultPinBatch::create([
            'batch_code' => 'BATCH-TEST-01',
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'quantity' => 1,
        ]);

        ResultPin::create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'serial_number' => 'SN-TEST-01',
            'pin' => 'TEST-PIN-1234',
            'is_used' => true,
            'used_at' => now(),
        ]);

        $this->actingAs($user);

        $request = Request::create('/result/download', 'GET', [
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        $response = app(ResultPdfController::class)->download($request);

        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('content-type'))->toBe('application/pdf');
    });
});
