<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_students_in_assigned_classroom()
    {
        // Create teacher user
        $teacherUser = User::factory()->create();
        $staff = Staff::factory()->create(['user_id' => $teacherUser->id]);
        
        // Create classroom assigned to teacher
        $classroom = Classroom::factory()->create(['staff_id' => $staff->id]);
        
        // Create student in that classroom
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Assert teacher can view student
        $this->assertTrue($teacherUser->can('view', $student));
    }

    public function test_teacher_cannot_view_students_in_other_classrooms()
    {
        // Create teacher user
        $teacherUser = User::factory()->create();
        $staff = Staff::factory()->create(['user_id' => $teacherUser->id]);
        
        // Create classroom assigned to teacher
        $teacherClassroom = Classroom::factory()->create(['staff_id' => $staff->id]);
        
        // Create another classroom NOT assigned to teacher
        $otherClassroom = Classroom::factory()->create();
        
        // Create student in other classroom
        $student = Student::factory()->create(['classroom_id' => $otherClassroom->id]);

        // Assert teacher cannot view student
        $this->assertFalse($teacherUser->can('view', $student));
    }

    public function test_admin_can_view_all_students()
    {
        // Create admin user (no staff record)
        $adminUser = User::factory()->create();
        
        // Create classroom and student
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Assert admin can view student
        $this->assertTrue($adminUser->can('view', $student));
    }

    public function test_teacher_can_update_students_in_assigned_classroom()
    {
        // Create teacher user
        $teacherUser = User::factory()->create();
        $staff = Staff::factory()->create(['user_id' => $teacherUser->id]);
        
        // Create classroom assigned to teacher
        $classroom = Classroom::factory()->create(['staff_id' => $staff->id]);
        
        // Create student in that classroom
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Assert teacher can update student
        $this->assertTrue($teacherUser->can('update', $student));
    }

    public function test_teacher_cannot_update_students_in_other_classrooms()
    {
        // Create teacher user
        $teacherUser = User::factory()->create();
        $staff = Staff::factory()->create(['user_id' => $teacherUser->id]);
        
        // Create classroom assigned to teacher
        $teacherClassroom = Classroom::factory()->create(['staff_id' => $staff->id]);
        
        // Create another classroom NOT assigned to teacher
        $otherClassroom = Classroom::factory()->create();
        
        // Create student in other classroom
        $student = Student::factory()->create(['classroom_id' => $otherClassroom->id]);

        // Assert teacher cannot update student
        $this->assertFalse($teacherUser->can('update', $student));
    }

    public function test_teacher_cannot_delete_students()
    {
        // Create teacher user
        $teacherUser = User::factory()->create();
        $staff = Staff::factory()->create(['user_id' => $teacherUser->id]);
        
        // Create classroom assigned to teacher
        $classroom = Classroom::factory()->create(['staff_id' => $staff->id]);
        
        // Create student in that classroom
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Assert teacher cannot delete student
        $this->assertFalse($teacherUser->can('delete', $student));
    }

    public function test_admin_can_delete_students()
    {
        // Create admin user (no staff record)
        $adminUser = User::factory()->create();
        
        // Create classroom and student
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Assert admin can delete student
        $this->assertTrue($adminUser->can('delete', $student));
    }
}
