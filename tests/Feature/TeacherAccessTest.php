<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_only_see_assigned_classrooms()
    {
        $user = User::factory()->create();
        $teacher = Staff::factory()->create(['user_id' => $user->id, 'role' => 'teacher']);
        
        $class1 = Classroom::factory()->create(['name' => 'Class 1']);
        $class2 = Classroom::factory()->create(['name' => 'Class 2']);

        // Assign teacher to Class 1
        $class1->teachers()->attach($teacher->id);

        $this->actingAs($user);

        $this->assertTrue(Classroom::where('id', $class1->id)->exists());
        $this->assertFalse(Classroom::where('id', $class2->id)->exists());
    }

    public function test_teacher_can_only_see_students_in_assigned_classrooms()
    {
        $user = User::factory()->create();
        $teacher = Staff::factory()->create(['user_id' => $user->id, 'role' => 'teacher']);
        
        $class1 = Classroom::factory()->create(['name' => 'Class 1']);
        $class2 = Classroom::factory()->create(['name' => 'Class 2']);

        $student1 = Student::factory()->create(['classroom_id' => $class1->id]);
        $student2 = Student::factory()->create(['classroom_id' => $class2->id]);

        // Assign teacher to Class 1
        $class1->teachers()->attach($teacher->id);

        $this->actingAs($user);

        $this->assertTrue(Student::where('id', $student1->id)->exists());
        $this->assertFalse(Student::where('id', $student2->id)->exists());
    }
}
