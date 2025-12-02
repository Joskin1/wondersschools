<?php

use App\Models\AssessmentType;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Resources\AssessmentTypeResource;
use App\Filament\Resources\ScoreResource;

uses(RefreshDatabase::class);

describe('Scoring System', function () {
    it('can create evaluation setting', function () {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\EvaluationSettingResource\Pages\CreateEvaluationSetting::class)
            ->fillForm([
                'academic_session_id' => $session->id,
                'name' => 'CA',
                'max_score' => 40,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('evaluation_settings', [
            'academic_session_id' => $session->id,
            'name' => 'CA',
            'max_score' => 40,
        ]);
    });

    it('prevents exceeding 100 total max score per session', function () {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create();
        
        \App\Models\EvaluationSetting::create([
            'academic_session_id' => $session->id,
            'name' => 'CA', 
            'max_score' => 40
        ]);

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\EvaluationSettingResource\Pages\CreateEvaluationSetting::class)
            ->fillForm([
                'academic_session_id' => $session->id,
                'name' => 'Exam',
                'max_score' => 70, // 40 + 70 = 110 > 100
            ])
            ->call('create')
            ->assertHasFormErrors(['max_score']);
    });

    it('can create score', function () {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create(['is_current' => true]);
        $term = \App\Models\Term::factory()->create(['academic_session_id' => $session->id, 'is_current' => true]);
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();
        
        // Create evaluation settings
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'CA', 'max_score' => 40]);
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'Exam', 'max_score' => 60]);

        Livewire::actingAs($user)
            ->test(ScoreResource\Pages\CreateScore::class)
            ->fillForm([
                'academic_session_id' => $session->id,
                'term_id' => $term->id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'ca_score' => 30,
                'exam_score' => 50,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('scores', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'ca_score' => 30,
            'exam_score' => 50,
            'total_score' => 80,
        ]);
    });

    it('prevents score from exceeding max score', function () {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create(['is_current' => true]);
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();
        
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'CA', 'max_score' => 40]);

        Livewire::actingAs($user)
            ->test(ScoreResource\Pages\CreateScore::class)
            ->fillForm([
                'academic_session_id' => $session->id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'ca_score' => 45, // 45 > 40
            ])
            ->call('create')
            ->assertHasFormErrors(['ca_score']);
    });

    it('can assign staff as class teacher', function () {
        $user = User::factory()->create();
        $staff = \App\Models\Staff::factory()->create();
        
        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\Classrooms\Pages\CreateClassroom::class)
            ->fillForm([
                'name' => 'Year 1',
                'staff_id' => $staff->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('classrooms', [
            'name' => 'Year 1',
            'staff_id' => $staff->id,
        ]);
    });

    it('has teacher relationship on classroom', function () {
        $staff = \App\Models\Staff::factory()->create();
        $classroom = \App\Models\Classroom::factory()->create(['staff_id' => $staff->id]);

        expect($classroom->teacher)->toBeInstanceOf(\App\Models\Staff::class);
        expect($classroom->teacher->id)->toBe($staff->id);
    });

    it('has classrooms relationship on staff', function () {
        $staff = \App\Models\Staff::factory()->create();
        $classroom1 = \App\Models\Classroom::factory()->create(['staff_id' => $staff->id]);
        $classroom2 = \App\Models\Classroom::factory()->create(['staff_id' => $staff->id]);

        expect($staff->classrooms)->toHaveCount(2);
        expect($staff->classrooms->contains($classroom1))->toBeTrue();
        expect($staff->classrooms->contains($classroom2))->toBeTrue();
    });

    it('has full name accessor on student', function () {
        $student = Student::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        expect($student->full_name)->toBe('John Doe');
    });

    it('loads bulk score input page', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ScoreResource\Pages\BulkScoreInput::class)
            ->assertSuccessful();
    });

    it('saves scores via bulk score input', function () {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create(['is_current' => true]);
        $term = \App\Models\Term::factory()->create(['academic_session_id' => $session->id, 'is_current' => true]);
        $staff = \App\Models\Staff::factory()->create(['user_id' => $user->id]);
        $classroom = \App\Models\Classroom::factory()->create(['staff_id' => $staff->id]);
        $student1 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $student2 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'CA', 'max_score' => 40]);
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'Exam', 'max_score' => 60]);

        Livewire::actingAs($user)
            ->test(ScoreResource\Pages\BulkScoreInput::class)
            ->set('academicSessionId', $session->id)
            ->set('termId', $term->id)
            ->set('classroomId', $classroom->id)
            ->set('subjectId', $subject->id)
            ->call('loadStudents')
            ->set('scores', [
                $student1->id => [
                    'ca_score' => 30,
                    'exam_score' => 50,
                ],
                $student2->id => [
                    'ca_score' => 35,
                    'exam_score' => 55,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('scores', [
            'student_id' => $student1->id,
            'subject_id' => $subject->id,
            'ca_score' => 30,
            'exam_score' => 50,
            'total_score' => 80,
        ]);

        $this->assertDatabaseHas('scores', [
            'student_id' => $student2->id,
            'subject_id' => $subject->id,
            'ca_score' => 35,
            'exam_score' => 55,
            'total_score' => 90,
        ]);
    });
});
