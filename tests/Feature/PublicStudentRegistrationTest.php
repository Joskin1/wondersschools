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
            ->assertSet('submitted', true)
            ->assertSee('Your Official Admission Number')
            ->assertSee('How to Sign In to Your Portal')
            ->assertSee('Tell the School to Activate Your Account')
            ->assertSee('Keep It Safe');

        // Verify Student created
        $student = Student::whereHas('user', fn ($q) => $q->where('surname', 'Alexander')->where('first_name', 'Michael'))->first();
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

    public function test_student_registration_is_rate_limited(): void
    {
        $component = Livewire::test(PublicStudentRegistrationForm::class);

        // First 5 attempts should pass the rate limit check
        for ($i = 0; $i < 5; $i++) {
            $component->call('submit');
        }

        // 6th attempt should be blocked by rate limiting with notification
        $component->call('submit')
            ->assertNotified('Too many registration attempts');
    }

    public function test_student_can_register_with_manual_admission_number_when_setting_is_enabled(): void
    {
        \App\Models\Setting::updateOrCreate(
            ['key' => 'allow_manual_admission_number'],
            ['value' => '1']
        );
        \App\Services\FrontendLibrary::flush();

        $session = Session::factory()->create(['is_active' => true]);
        $classroom = Classroom::factory()->create();

        Livewire::test(PublicStudentRegistrationForm::class)
            ->assertSee('Admission / Registration Number')
            ->set('full_name', 'Grace Hopper')
            ->set('admission_number', 'SCH/2026/077')
            ->set('date_of_birth', '2015-05-10')
            ->set('gender', 'female')
            ->set('classroom_id', $classroom->id)
            ->set('address', '10 Naval Way')
            ->set('parent_name', 'Admiral Hopper')
            ->set('parent_phone', '08099887766')
            ->set('password', 'SecurePass123!')
            ->set('password_confirmation', 'SecurePass123!')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $student = Student::whereHas('user', fn ($q) => $q->where('surname', 'Grace')->where('first_name', 'Hopper'))->first();
        $this->assertNotNull($student);
        $this->assertEquals('SCH/2026/077', $student->admission_number);

        $user = User::where('id', $student->user_id)->first();
        $this->assertNotNull($user);
        $this->assertEquals('sch.2026.077@student.local', $user->email);
    }

    public function test_manual_admission_number_enforces_validation_when_setting_is_enabled(): void
    {
        \App\Models\Setting::updateOrCreate(
            ['key' => 'allow_manual_admission_number'],
            ['value' => '1']
        );
        \App\Services\FrontendLibrary::flush();

        $session = Session::factory()->create(['is_active' => true]);
        $classroom = Classroom::factory()->create();

        // Create an existing student with this admission number
        Student::factory()->create([
            'admission_number' => 'EXISTING/123',
        ]);

        // Attempt 1: Empty admission number
        Livewire::test(PublicStudentRegistrationForm::class)
            ->set('full_name', 'Student Test')
            ->set('admission_number', '')
            ->set('date_of_birth', '2015-05-10')
            ->set('gender', 'female')
            ->set('classroom_id', $classroom->id)
            ->set('address', '10 Test St')
            ->set('parent_name', 'Parent Test')
            ->set('parent_phone', '08012345678')
            ->set('password', 'SecurePass123!')
            ->set('password_confirmation', 'SecurePass123!')
            ->call('submit')
            ->assertHasErrors(['admission_number' => 'required']);

        // Attempt 2: Duplicate admission number
        Livewire::test(PublicStudentRegistrationForm::class)
            ->set('full_name', 'Student Test 2')
            ->set('admission_number', 'EXISTING/123')
            ->set('date_of_birth', '2015-05-10')
            ->set('gender', 'female')
            ->set('classroom_id', $classroom->id)
            ->set('address', '10 Test St')
            ->set('parent_name', 'Parent Test')
            ->set('parent_phone', '08012345678')
            ->set('password', 'SecurePass123!')
            ->set('password_confirmation', 'SecurePass123!')
            ->call('submit')
            ->assertHasErrors(['admission_number' => 'unique']);
    }

    public function test_manual_admission_number_field_is_hidden_when_disabled(): void
    {
        \App\Models\Setting::updateOrCreate(
            ['key' => 'allow_manual_admission_number'],
            ['value' => '0']
        );
        \App\Services\FrontendLibrary::flush();

        Livewire::test(PublicStudentRegistrationForm::class)
            ->assertDontSee('Admission / Registration Number')
            ->assertSet('allowManualAdmissionNumber', false);
    }

    public function test_classroom_can_have_multiple_arms(): void
    {
        $classroom = Classroom::create([
            'name' => 'JSS 1',
            'class_order' => 1,
            'is_active' => true,
        ]);

        $armGold = \App\Models\ClassArm::create([
            'classroom_id' => $classroom->id,
            'name' => 'Gold',
            'is_active' => true,
        ]);

        $armSilver = \App\Models\ClassArm::create([
            'classroom_id' => $classroom->id,
            'name' => 'Silver',
            'is_active' => true,
        ]);

        $this->assertEquals(2, $classroom->arms()->count());
        $this->assertTrue($classroom->hasArms());
        $this->assertEquals('JSS 1 — Gold', $armGold->full_name);
    }

    public function test_student_can_register_with_classroom_and_arm_when_arms_enabled(): void
    {
        \App\Models\Setting::updateOrCreate(
            ['key' => 'enable_class_arms'],
            ['value' => '1']
        );
        \App\Services\FrontendLibrary::flush();

        $session = Session::factory()->create(['is_active' => true]);
        $classroom = Classroom::create([
            'name' => 'JSS 1',
            'class_order' => 1,
            'is_active' => true,
        ]);

        $armGold = \App\Models\ClassArm::create([
            'classroom_id' => $classroom->id,
            'name' => 'Gold',
            'is_active' => true,
        ]);

        Livewire::test(PublicStudentRegistrationForm::class)
            ->assertSee('Classroom / Grade')
            ->assertSee('Class Arm / Stream')
            ->set('full_name', 'Chinedu Eze')
            ->set('classroom_id', $classroom->id)
            ->set('class_arm_id', $armGold->id)
            ->set('date_of_birth', '2014-06-12')
            ->set('gender', 'male')
            ->set('address', '15 Palm Avenue')
            ->set('parent_name', 'Chief Eze')
            ->set('parent_phone', '08022334455')
            ->set('password', 'StudentPass123!')
            ->set('password_confirmation', 'StudentPass123!')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $student = Student::whereHas('user', fn ($q) => $q->where('surname', 'Chinedu')->where('first_name', 'Eze'))->first();
        $this->assertNotNull($student);

        // Verify enrolled in JSS 1 with Gold arm
        $enrollment = StudentEnrollment::where('student_id', $student->id)->first();
        $this->assertNotNull($enrollment);
        $this->assertEquals($classroom->id, $enrollment->classroom_id);
        $this->assertEquals($armGold->id, $enrollment->class_arm_id);
        $this->assertEquals('JSS 1 — Gold', $enrollment->getFullClassName());
    }

    public function test_student_can_register_with_nullable_arm_when_arms_enabled(): void
    {
        \App\Models\Setting::updateOrCreate(
            ['key' => 'enable_class_arms'],
            ['value' => '1']
        );
        \App\Services\FrontendLibrary::flush();

        $session = Session::factory()->create(['is_active' => true]);
        $classroom = Classroom::create([
            'name' => 'JSS 1',
            'class_order' => 1,
            'is_active' => true,
        ]);

        Livewire::test(PublicStudentRegistrationForm::class)
            ->set('full_name', 'Amina Bello')
            ->set('classroom_id', $classroom->id)
            ->set('class_arm_id', null) // Arm left unassigned / nullable
            ->set('date_of_birth', '2014-09-18')
            ->set('gender', 'female')
            ->set('address', '22 Crescent Road')
            ->set('parent_name', 'Hajiya Bello')
            ->set('parent_phone', '08033445566')
            ->set('password', 'StudentPass123!')
            ->set('password_confirmation', 'StudentPass123!')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $student = Student::whereHas('user', fn ($q) => $q->where('surname', 'Amina')->where('first_name', 'Bello'))->first();
        $this->assertNotNull($student);

        $enrollment = StudentEnrollment::where('student_id', $student->id)->first();
        $this->assertNotNull($enrollment);
        $this->assertEquals($classroom->id, $enrollment->classroom_id);
        $this->assertNull($enrollment->class_arm_id);
        $this->assertEquals('JSS 1', $enrollment->getFullClassName());
    }
}
