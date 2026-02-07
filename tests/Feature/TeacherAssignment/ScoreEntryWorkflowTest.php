<?php

namespace Tests\Feature\TeacherAssignment;

use App\Models\Classroom;
use App\Models\Score;
use App\Models\ScoreHeader;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScoreEntryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected Staff $staff;
    protected Classroom $classroom;
    protected Subject $subject;
    protected ScoreHeader $scoreHeader;
    protected string $session;
    protected int $term;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->staff = Staff::factory()->create(['user_id' => $this->teacher->id]);
        
        $this->classroom = Classroom::factory()->create();
        $this->subject = Subject::factory()->create();
        $this->session = '2024/2025';
        $this->term = 1;
        
        $this->scoreHeader = ScoreHeader::factory()->create([
            'classroom_id' => $this->classroom->id,
            'session' => $this->session,
            'term' => $this->term,
            'name' => 'CA1',
        ]);

        // Create assignment
        DB::table('classroom_subject_teacher')->insert([
            'staff_id' => $this->staff->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'session' => $this->session,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function teacher_can_complete_full_score_entry_workflow()
    {
        // Create students
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $student3 = Student::factory()->create();

        // Attach students to classroom
        $this->classroom->students()->attach([$student1->id, $student2->id, $student3->id]);

        // Teacher enters scores
        $scores = [
            [
                'student_id' => $student1->id,
                'subject_id' => $this->subject->id,
                'classroom_id' => $this->classroom->id,
                'score_header_id' => $this->scoreHeader->id,
                'session' => $this->session,
                'term' => $this->term,
                'value' => 85,
            ],
            [
                'student_id' => $student2->id,
                'subject_id' => $this->subject->id,
                'classroom_id' => $this->classroom->id,
                'score_header_id' => $this->scoreHeader->id,
                'session' => $this->session,
                'term' => $this->term,
                'value' => 92,
            ],
            [
                'student_id' => $student3->id,
                'subject_id' => $this->subject->id,
                'classroom_id' => $this->classroom->id,
                'score_header_id' => $this->scoreHeader->id,
                'session' => $this->session,
                'term' => $this->term,
                'value' => 78,
            ],
        ];

        foreach ($scores as $scoreData) {
            Score::create($scoreData);
        }

        // Verify scores were created
        $this->assertDatabaseCount('scores', 3);
        
        $this->assertDatabaseHas('scores', [
            'student_id' => $student1->id,
            'value' => 85,
        ]);
        
        $this->assertDatabaseHas('scores', [
            'student_id' => $student2->id,
            'value' => 92,
        ]);
        
        $this->assertDatabaseHas('scores', [
            'student_id' => $student3->id,
            'value' => 78,
        ]);
    }

    /** @test */
    public function teacher_can_update_existing_scores()
    {
        $student = Student::factory()->create();
        $this->classroom->students()->attach($student->id);

        // Create initial score
        $score = Score::create([
            'student_id' => $student->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'score_header_id' => $this->scoreHeader->id,
            'session' => $this->session,
            'term' => $this->term,
            'value' => 80,
        ]);

        // Update score
        $score->update(['value' => 85]);

        $this->assertDatabaseHas('scores', [
            'id' => $score->id,
            'value' => 85,
        ]);
    }

    /** @test */
    public function audit_log_is_created_when_score_is_entered()
    {
        $student = Student::factory()->create();
        $this->classroom->students()->attach($student->id);

        $this->actingAs($this->teacher);

        // Create score
        Score::create([
            'student_id' => $student->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'score_header_id' => $this->scoreHeader->id,
            'session' => $this->session,
            'term' => $this->term,
            'value' => 85,
        ]);

        // Verify audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Score::class,
            'user_id' => $this->teacher->id,
        ]);
    }

    /** @test */
    public function audit_log_tracks_score_updates()
    {
        $student = Student::factory()->create();
        $this->classroom->students()->attach($student->id);

        $this->actingAs($this->teacher);

        // Create score
        $score = Score::create([
            'student_id' => $student->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'score_header_id' => $this->scoreHeader->id,
            'session' => $this->session,
            'term' => $this->term,
            'value' => 80,
        ]);

        // Update score
        $score->update(['value' => 85]);

        // Verify update audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'auditable_type' => Score::class,
            'auditable_id' => $score->id,
            'user_id' => $this->teacher->id,
        ]);

        // Verify old and new values are stored
        $auditLog = DB::table('audit_logs')
            ->where('action', 'updated')
            ->where('auditable_id', $score->id)
            ->first();

        $oldValue = json_decode($auditLog->old_value, true);
        $newValue = json_decode($auditLog->new_value, true);

        $this->assertEquals(80, $oldValue['value']);
        $this->assertEquals(85, $newValue['value']);
    }

    /** @test */
    public function teacher_can_only_see_assigned_class_scores()
    {
        $assignedClassroom = $this->classroom;
        $unassignedClassroom = Classroom::factory()->create();

        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();

        $assignedClassroom->students()->attach($student1->id);
        $unassignedClassroom->students()->attach($student2->id);

        // Create scores for both classrooms
        $assignedScore = Score::factory()->create([
            'student_id' => $student1->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $assignedClassroom->id,
            'session' => $this->session,
        ]);

        $unassignedScore = Score::factory()->create([
            'student_id' => $student2->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $unassignedClassroom->id,
            'session' => $this->session,
        ]);

        // Get teacher's scoped query
        $scoreService = app(\App\Services\ScoreService::class);
        $query = $scoreService->getScoresQueryForTeacher($this->teacher);
        $scores = $query->get();

        // Teacher should only see assigned classroom score
        $this->assertCount(1, $scores);
        $this->assertEquals($assignedScore->id, $scores->first()->id);
    }

    /** @test */
    public function teacher_cannot_enter_scores_for_unassigned_subject()
    {
        $unassignedSubject = Subject::factory()->create();
        $student = Student::factory()->create();
        $this->classroom->students()->attach($student->id);

        $this->actingAs($this->teacher);

        // Try to create score for unassigned subject
        $score = Score::factory()->make([
            'student_id' => $student->id,
            'subject_id' => $unassignedSubject->id,
            'classroom_id' => $this->classroom->id,
            'session' => $this->session,
        ]);

        // Verify policy denies access
        $this->assertFalse($this->teacher->can('create', $score));
    }
}
