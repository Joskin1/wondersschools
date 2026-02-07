<?php

namespace Tests\Unit\Services;

use App\Models\Classroom;
use App\Models\Score;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\User;
use App\Services\ScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ScoreService $scoreService;
    protected User $teacher;
    protected Staff $staff;
    protected Subject $subject;
    protected Classroom $classroom;
    protected string $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scoreService = app(ScoreService::class);
        
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->staff = Staff::factory()->create(['user_id' => $this->teacher->id]);
        
        $this->subject = Subject::factory()->create();
        $this->classroom = Classroom::factory()->create();
        $this->session = '2024/2025';
    }

    /** @test */
    public function it_validates_teacher_assignment_successfully()
    {
        // Create assignment
        DB::table('classroom_subject_teacher')->insert([
            'staff_id' => $this->staff->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'session' => $this->session,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->scoreService->validateTeacherAssignment(
            $this->teacher,
            $this->subject->id,
            $this->classroom->id,
            $this->session
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_fails_validation_for_unassigned_teacher()
    {
        // No assignment created

        $result = $this->scoreService->validateTeacherAssignment(
            $this->teacher,
            $this->subject->id,
            $this->classroom->id,
            $this->session
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_fails_validation_for_wrong_session()
    {
        // Create assignment for 2024/2025
        DB::table('classroom_subject_teacher')->insert([
            'staff_id' => $this->staff->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'session' => $this->session,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Try to validate for different session
        $result = $this->scoreService->validateTeacherAssignment(
            $this->teacher,
            $this->subject->id,
            $this->classroom->id,
            '2025/2026'
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_scoped_query_for_teacher()
    {
        $assignedClassroom = Classroom::factory()->create();
        $unassignedClassroom = Classroom::factory()->create();
        
        $assignedSubject = Subject::factory()->create();
        $unassignedSubject = Subject::factory()->create();

        // Create assignment
        DB::table('classroom_subject_teacher')->insert([
            'staff_id' => $this->staff->id,
            'classroom_id' => $assignedClassroom->id,
            'subject_id' => $assignedSubject->id,
            'session' => $this->session,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create scores
        $assignedScore = Score::factory()->create([
            'subject_id' => $assignedSubject->id,
            'classroom_id' => $assignedClassroom->id,
            'session' => $this->session,
        ]);

        $unassignedScore = Score::factory()->create([
            'subject_id' => $unassignedSubject->id,
            'classroom_id' => $unassignedClassroom->id,
            'session' => $this->session,
        ]);

        $query = $this->scoreService->getScoresQueryForTeacher($this->teacher);
        $scores = $query->get();

        $this->assertCount(1, $scores);
        $this->assertEquals($assignedScore->id, $scores->first()->id);
    }

    /** @test */
    public function it_returns_teacher_classrooms()
    {
        $classroom1 = Classroom::factory()->create();
        $classroom2 = Classroom::factory()->create();
        $classroom3 = Classroom::factory()->create();

        // Assign to classroom1 and classroom2
        DB::table('classroom_subject_teacher')->insert([
            [
                'staff_id' => $this->staff->id,
                'classroom_id' => $classroom1->id,
                'subject_id' => $this->subject->id,
                'session' => $this->session,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'staff_id' => $this->staff->id,
                'classroom_id' => $classroom2->id,
                'subject_id' => $this->subject->id,
                'session' => $this->session,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $classrooms = $this->scoreService->getTeacherClassrooms($this->teacher, $this->session);

        $this->assertCount(2, $classrooms);
        $this->assertTrue($classrooms->contains('id', $classroom1->id));
        $this->assertTrue($classrooms->contains('id', $classroom2->id));
        $this->assertFalse($classrooms->contains('id', $classroom3->id));
    }

    /** @test */
    public function it_returns_teacher_subjects()
    {
        $subject1 = Subject::factory()->create();
        $subject2 = Subject::factory()->create();
        $subject3 = Subject::factory()->create();

        // Assign to subject1 and subject2
        DB::table('classroom_subject_teacher')->insert([
            [
                'staff_id' => $this->staff->id,
                'classroom_id' => $this->classroom->id,
                'subject_id' => $subject1->id,
                'session' => $this->session,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'staff_id' => $this->staff->id,
                'classroom_id' => $this->classroom->id,
                'subject_id' => $subject2->id,
                'session' => $this->session,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $subjects = $this->scoreService->getTeacherSubjects($this->teacher, $this->session);

        $this->assertCount(2, $subjects);
        $this->assertTrue($subjects->contains('id', $subject1->id));
        $this->assertTrue($subjects->contains('id', $subject2->id));
        $this->assertFalse($subjects->contains('id', $subject3->id));
    }

    /** @test */
    public function it_returns_empty_collection_for_teacher_with_no_assignments()
    {
        $classrooms = $this->scoreService->getTeacherClassrooms($this->teacher, $this->session);
        $subjects = $this->scoreService->getTeacherSubjects($this->teacher, $this->session);

        $this->assertCount(0, $classrooms);
        $this->assertCount(0, $subjects);
    }

    /** @test */
    public function it_filters_by_session_correctly()
    {
        $session2024 = '2024/2025';
        $session2025 = '2025/2026';

        // Create assignment for 2024/2025
        DB::table('classroom_subject_teacher')->insert([
            'staff_id' => $this->staff->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'session' => $session2024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Should find for 2024/2025
        $this->assertTrue($this->scoreService->validateTeacherAssignment(
            $this->teacher,
            $this->subject->id,
            $this->classroom->id,
            $session2024
        ));

        // Should not find for 2025/2026
        $this->assertFalse($this->scoreService->validateTeacherAssignment(
            $this->teacher,
            $this->subject->id,
            $this->classroom->id,
            $session2025
        ));
    }
}
