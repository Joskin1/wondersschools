<?php

namespace Tests\Unit\Models;

use App\Models\Classroom;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $teacherUser;
    protected User $staffUser;
    protected Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        
        $this->teacherUser = User::factory()->create(['role' => 'teacher']);
        $this->staff = Staff::factory()->create(['user_id' => $this->teacherUser->id]);
        
        $this->staffUser = User::factory()->create(['role' => 'staff']);
    }

    /** @test */
    public function it_identifies_admin_users_correctly()
    {
        $this->assertTrue($this->adminUser->isAdmin());
        $this->assertFalse($this->teacherUser->isAdmin());
        $this->assertFalse($this->staffUser->isAdmin());
    }

    /** @test */
    public function it_identifies_teacher_users_correctly()
    {
        $this->assertFalse($this->adminUser->isTeacher());
        $this->assertTrue($this->teacherUser->isTeacher());
        $this->assertFalse($this->staffUser->isTeacher());
    }

    /** @test */
    public function it_returns_assigned_subjects_for_teacher()
    {
        $subject1 = Subject::factory()->create(['name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['name' => 'English']);
        $subject3 = Subject::factory()->create(['name' => 'Science']);
        
        $classroom = Classroom::factory()->create();
        $session = '2024/2025';

        // Assign teacher to Math and English
        DB::table('classroom_subject_teacher')->insert([
            [
                'staff_id' => $this->staff->id,
                'classroom_id' => $classroom->id,
                'subject_id' => $subject1->id,
                'session' => $session,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'staff_id' => $this->staff->id,
                'classroom_id' => $classroom->id,
                'subject_id' => $subject2->id,
                'session' => $session,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $assignedSubjects = $this->teacherUser->assignedSubjects($session);

        $this->assertCount(2, $assignedSubjects);
        $this->assertTrue($assignedSubjects->contains('id', $subject1->id));
        $this->assertTrue($assignedSubjects->contains('id', $subject2->id));
        $this->assertFalse($assignedSubjects->contains('id', $subject3->id));
    }

    /** @test */
    public function it_returns_assigned_classrooms_for_teacher()
    {
        $classroom1 = Classroom::factory()->create(['name' => 'Class 5A']);
        $classroom2 = Classroom::factory()->create(['name' => 'Class 5B']);
        $classroom3 = Classroom::factory()->create(['name' => 'Class 6A']);
        
        $subject = Subject::factory()->create();
        $session = '2024/2025';

        // Assign teacher to Class 5A and 5B
        DB::table('classroom_subject_teacher')->insert([
            [
                'staff_id' => $this->staff->id,
                'classroom_id' => $classroom1->id,
                'subject_id' => $subject->id,
                'session' => $session,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'staff_id' => $this->staff->id,
                'classroom_id' => $classroom2->id,
                'subject_id' => $subject->id,
                'session' => $session,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $assignedClassrooms = $this->teacherUser->assignedClassrooms($session);

        $this->assertCount(2, $assignedClassrooms);
        $this->assertTrue($assignedClassrooms->contains('id', $classroom1->id));
        $this->assertTrue($assignedClassrooms->contains('id', $classroom2->id));
        $this->assertFalse($assignedClassrooms->contains('id', $classroom3->id));
    }

    /** @test */
    public function it_validates_teacher_can_access_assigned_subject()
    {
        $subject = Subject::factory()->create();
        $classroom = Classroom::factory()->create();
        $session = '2024/2025';

        DB::table('classroom_subject_teacher')->insert([
            'staff_id' => $this->staff->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'session' => $session,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($this->teacherUser->canAccessSubject($subject->id, $classroom->id, $session));
    }

    /** @test */
    public function it_validates_teacher_cannot_access_unassigned_subject()
    {
        $subject = Subject::factory()->create();
        $classroom = Classroom::factory()->create();
        $session = '2024/2025';

        // No assignment created

        $this->assertFalse($this->teacherUser->canAccessSubject($subject->id, $classroom->id, $session));
    }

    /** @test */
    public function it_validates_teacher_can_access_assigned_classroom()
    {
        $subject = Subject::factory()->create();
        $classroom = Classroom::factory()->create();
        $session = '2024/2025';

        DB::table('classroom_subject_teacher')->insert([
            'staff_id' => $this->staff->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'session' => $session,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($this->teacherUser->canAccessClassroom($classroom->id, $session));
    }

    /** @test */
    public function it_validates_teacher_cannot_access_unassigned_classroom()
    {
        $classroom = Classroom::factory()->create();
        $session = '2024/2025';

        // No assignment created

        $this->assertFalse($this->teacherUser->canAccessClassroom($classroom->id, $session));
    }

    /** @test */
    public function it_returns_empty_collection_for_teacher_with_no_assignments()
    {
        $session = '2024/2025';

        $this->assertCount(0, $this->teacherUser->assignedSubjects($session));
        $this->assertCount(0, $this->teacherUser->assignedClassrooms($session));
    }

    /** @test */
    public function it_filters_assignments_by_session()
    {
        $subject = Subject::factory()->create();
        $classroom = Classroom::factory()->create();
        
        $session2024 = '2024/2025';
        $session2025 = '2025/2026';

        // Create assignment for 2024/2025
        DB::table('classroom_subject_teacher')->insert([
            'staff_id' => $this->staff->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'session' => $session2024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Should find assignment for 2024/2025
        $this->assertCount(1, $this->teacherUser->assignedSubjects($session2024));
        
        // Should not find assignment for 2025/2026
        $this->assertCount(0, $this->teacherUser->assignedSubjects($session2025));
    }

    /** @test */
    public function admin_user_has_no_staff_record()
    {
        $this->assertNull($this->adminUser->staff);
    }

    /** @test */
    public function teacher_user_has_staff_record()
    {
        $this->assertNotNull($this->teacherUser->staff);
        $this->assertEquals($this->staff->id, $this->teacherUser->staff->id);
    }
}
