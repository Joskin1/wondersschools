<?php

namespace Tests\Unit\Policies;

use App\Models\Classroom;
use App\Models\Score;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\User;
use App\Policies\ScorePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScorePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ScorePolicy $policy;
    protected User $admin;
    protected User $teacher;
    protected User $student;
    protected Staff $staff;
    protected Score $score;
    protected Subject $subject;
    protected Classroom $classroom;
    protected string $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ScorePolicy();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => null]); // No role = student
        
        $this->staff = Staff::factory()->create(['user_id' => $this->teacher->id]);
        
        $this->subject = Subject::factory()->create();
        $this->classroom = Classroom::factory()->create();
        $this->session = '2024/2025';
        
        $this->score = Score::factory()->create([
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session' => $this->session,
        ]);
    }

    /** @test */
    public function admin_can_view_any_score()
    {
        $this->assertTrue($this->policy->view($this->admin, $this->score));
    }

    /** @test */
    public function teacher_can_view_assigned_score()
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

        $this->assertTrue($this->policy->view($this->teacher, $this->score));
    }

    /** @test */
    public function teacher_cannot_view_unassigned_score()
    {
        // No assignment created

        $this->assertFalse($this->policy->view($this->teacher, $this->score));
    }

    /** @test */
    public function student_cannot_view_score()
    {
        $this->assertFalse($this->policy->view($this->student, $this->score));
    }

    /** @test */
    public function admin_can_create_score()
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    /** @test */
    public function teacher_can_create_score_for_assigned_subject_classroom()
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

        $this->assertTrue($this->policy->create($this->teacher));
    }

    /** @test */
    public function student_cannot_create_score()
    {
        $this->assertFalse($this->policy->create($this->student));
    }

    /** @test */
    public function admin_can_update_any_score()
    {
        $this->assertTrue($this->policy->update($this->admin, $this->score));
    }

    /** @test */
    public function teacher_can_update_assigned_score()
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

        $this->assertTrue($this->policy->update($this->teacher, $this->score));
    }

    /** @test */
    public function teacher_cannot_update_unassigned_score()
    {
        // No assignment created

        $this->assertFalse($this->policy->update($this->teacher, $this->score));
    }

    /** @test */
    public function student_cannot_update_score()
    {
        $this->assertFalse($this->policy->update($this->student, $this->score));
    }

    /** @test */
    public function admin_can_delete_score()
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->score));
    }

    /** @test */
    public function teacher_can_delete_assigned_score()
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

        $this->assertTrue($this->policy->delete($this->teacher, $this->score));
    }

    /** @test */
    public function teacher_cannot_delete_unassigned_score()
    {
        // No assignment created

        $this->assertFalse($this->policy->delete($this->teacher, $this->score));
    }

    /** @test */
    public function student_cannot_delete_score()
    {
        $this->assertFalse($this->policy->delete($this->student, $this->score));
    }

    /** @test */
    public function teacher_assignment_validation_respects_session()
    {
        // Create assignment for 2024/2025
        DB::table('classroom_subject_teacher')->insert([
            'staff_id' => $this->staff->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'session' => '2024/2025',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Score for 2024/2025 - should be allowed
        $score2024 = Score::factory()->create([
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session' => '2024/2025',
        ]);

        // Score for 2025/2026 - should not be allowed
        $score2025 = Score::factory()->create([
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session' => '2025/2026',
        ]);

        $this->assertTrue($this->policy->view($this->teacher, $score2024));
        $this->assertFalse($this->policy->view($this->teacher, $score2025));
    }
}
