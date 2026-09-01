<?php

namespace Tests\Feature;

use App\Livewire\PublicStudentRegistrationForm;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PublicStudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_student_registration_page_is_accessible()
    {
        $response = $this->get(route('public.student.register'));

        $response->assertStatus(200);
        $response->assertSee('Student Online Registration');
    }

    public function test_student_can_self_register_via_public_form()
    {
        Storage::fake('public');

        $session = Session::factory()->create(['is_active' => true]);
        $classroom = Classroom::factory()->create();

        $file = UploadedFile::fake()->image('student_photo.jpg', 100, 100);

        Livewire::test(PublicStudentRegistrationForm::class)
            ->set('full_name', 'Alexander Michael Great')
            ->set('date_of_birth', '2015-08-20')
            ->set('gender', 'male')
            ->set('classroom_id', $classroom->id)
            ->set('profile_picture', $file)
            ->set('previous_school', 'Bright Stars Primary')
            ->set('address', '45 Education Blvd')
            ->set('parent_name', 'Mr. Samuel Great')
            ->set('parent_phone', '08011223344')
            ->set('parent_email', 'parent.great@example.com')
            ->set('password', 'StudentPass123!')
            ->set('password_confirmation', 'StudentPass123!')
            ->call('submit')
            ->assertSet('submitted', true);

        // Verify Student created
        $student = Student::where('full_name', 'Alexander Michael Great')->first();
        $this->assertNotNull($student);
        $this->assertNotEmpty($student->admission_number);
        $this->assertEquals('male', $student->gender);
        $this->assertEquals('Mr. Samuel Great', $student->parent_name);
        $this->assertFalse($student->is_portal_active);

        // Verify StudentEnrollment created
        $enrollment = StudentEnrollment::where('student_id', $student->id)->first();
        $this->assertNotNull($enrollment);
        $this->assertEquals($classroom->id, $enrollment->classroom_id);
        $this->assertEquals($session->id, $enrollment->session_id);

        // Verify User account created
        $user = User::where('id', $student->user_id)->first();
        $this->assertNotNull($user);
        $this->assertEquals('student', $user->role);
        $this->assertFalse($user->isActive());
    }
}
