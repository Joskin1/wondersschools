<?php

use App\Models\AcademicSession;
use App\Models\Classroom;
use App\Models\Result;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Academic Structure', function () {
    it('can create academic session', function () {
        $session = AcademicSession::create([
            'name' => '2024/2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-31',
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('academic_sessions', [
            'name' => '2024/2025',
            'is_current' => true,
        ]);
    });

    it('can create term with session', function () {
        $session = AcademicSession::factory()->create(['is_current' => true]);

        $term = Term::create([
            'name' => 'First Term',
            'academic_session_id' => $session->id,
            'start_date' => '2024-09-01',
            'end_date' => '2024-12-15',
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('terms', [
            'name' => 'First Term',
            'academic_session_id' => $session->id,
            'is_current' => true,
        ]);

        expect($term->academicSession)->toBeInstanceOf(AcademicSession::class);
    });

    it('has terms relationship on academic session', function () {
        $session = AcademicSession::factory()->create();
        $term1 = Term::factory()->create(['academic_session_id' => $session->id]);
        $term2 = Term::factory()->create(['academic_session_id' => $session->id]);

        expect($session->terms)->toHaveCount(2);
        expect($session->terms->contains($term1))->toBeTrue();
        expect($session->terms->contains($term2))->toBeTrue();
    });

    it('has score belongs to session and term', function () {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();

        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'CA', 'max_score' => 40]);

        $score = Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'ca_score' => 15,
            'exam_score' => 0,
        ]);

        expect($score->academicSession)->toBeInstanceOf(AcademicSession::class);
        expect($score->term)->toBeInstanceOf(Term::class);
        expect($score->academic_session_id)->toBe($session->id);
        expect($score->term_id)->toBe($term->id);
    });

    it('can create result', function () {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $result = Result::create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'total_score' => 250.5,
            'average_score' => 83.5,
            'position' => 1,
            'grade' => 'A',
            'teacher_remark' => 'Excellent performance',
            'principal_remark' => 'Keep it up',
        ]);

        $this->assertDatabaseHas('results', [
            'student_id' => $student->id,
            'total_score' => 250.5,
            'average_score' => 83.5,
            'position' => 1,
            'grade' => 'A',
        ]);
    });

    it('has all relationships on result', function () {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $result = Result::create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'total_score' => 250.5,
            'average_score' => 83.5,
        ]);

        expect($result->student)->toBeInstanceOf(Student::class);
        expect($result->academicSession)->toBeInstanceOf(AcademicSession::class);
        expect($result->term)->toBeInstanceOf(Term::class);
        expect($result->classroom)->toBeInstanceOf(Classroom::class);
    });

    it('has results relationship on student', function () {
        $student = Student::factory()->create();
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();

        $result1 = Result::create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'total_score' => 250,
            'average_score' => 83.33,
        ]);

        $result2 = Result::create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'total_score' => 280,
            'average_score' => 93.33,
        ]);

        expect($student->results)->toHaveCount(2);
        expect($student->results->contains($result1))->toBeTrue();
        expect($student->results->contains($result2))->toBeTrue();
    });

    it('requires session and term for bulk score input', function () {
        $user = User::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $term = Term::factory()->create(['academic_session_id' => $session->id, 'is_current' => true]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();

        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'CA', 'max_score' => 40]);
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'Exam', 'max_score' => 60]);

        Livewire::actingAs($user)
            ->test('App\\Filament\\Resources\\ScoreResource\\Pages\\BulkScoreInput')
            ->assertSet('academicSessionId', $session->id)
            ->assertSet('termId', $term->id)
            ->set('classroomId', $classroom->id)
            ->set('subjectId', $subject->id)
            ->call('loadStudents')
            ->set('scores', [
                $student->id => [
                    'ca_score' => 15,
                    'exam_score' => 0,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('scores', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'ca_score' => 15,
        ]);
    });

    it('loads term resource pages', function () {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create();
        $term = \App\Models\Term::factory()->create(['academic_session_id' => $session->id]);

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\TermResource\Pages\ListTerms::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\TermResource\Pages\CreateTerm::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\TermResource\Pages\EditTerm::class, [
                'record' => $term->getRouteKey(),
            ])
            ->assertSuccessful();
    });

    it('loads academic session resource pages', function () {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\AcademicSessionResource\Pages\ListAcademicSessions::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\AcademicSessionResource\Pages\CreateAcademicSession::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\AcademicSessionResource\Pages\EditAcademicSession::class, [
                'record' => $session->getRouteKey(),
            ])
            ->assertSuccessful();
    });

    it('loads result resource pages', function () {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create();
        $term = \App\Models\Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = \App\Models\Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $result = \App\Models\Result::factory()->create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\ResultResource\Pages\ListResult::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\ResultResource\Pages\CreateResult::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\ResultResource\Pages\EditResult::class, [
                'record' => $result->getRouteKey(),
            ])
            ->assertSuccessful();
    });
});
