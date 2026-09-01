<?php

namespace Tests\Feature;

use App\Livewire\PublicTeacherRegistrationForm;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\TeacherPortalActivated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PublicTeacherRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_registration_page_is_accessible()
    {
        $response = $this->get(route('public.teacher.register'));

        $response->assertStatus(200);
        $response->assertSee('Teacher Self-Registration');
    }

    public function test_teacher_can_self_register_via_public_form()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        Livewire::test(PublicTeacherRegistrationForm::class)
            ->set('name', 'Jane Teacher')
            ->set('email', 'jane.teacher@example.com')
            ->set('phone', '08098765432')
            ->set('gender', 'female')
            ->set('dob', '1992-05-15')
            ->set('address', '123 Academic Way')
            ->set('profile_picture', $file)
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->call('submit')
            ->assertSet('submitted', true);

        // Verify User record created
        $user = User::where('email', 'jane.teacher@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Jane Teacher', $user->name);
        $this->assertEquals('teacher', $user->role);
        $this->assertFalse($user->isActive(), 'Teacher portal access must be inactive upon registration');
        $this->assertNotNull($user->registration_completed_at);

        // Verify Teacher profile created
        $teacher = Teacher::where('user_id', $user->id)->first();
        $this->assertNotNull($teacher);
        $this->assertEquals('08098765432', $teacher->phone);
        $this->assertEquals('female', $teacher->gender);
        $this->assertEquals('123 Academic Way', $teacher->address);
        $this->assertNotNull($teacher->profile_picture);
    }

    public function test_inactive_teacher_cannot_access_teacher_panel()
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'is_active' => false,
        ]);

        $panel = \Filament\Facades\Filament::getPanel('teacher');
        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_admin_activation_triggers_notification_and_enables_panel_access()
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'teacher',
            'is_active' => false,
        ]);

        $user->update(['is_active' => true]);
        $user->notify(new TeacherPortalActivated());

        Notification::assertSentTo(
            $user,
            TeacherPortalActivated::class
        );

        $panel = \Filament\Facades\Filament::getPanel('teacher');
        $this->assertTrue($user->fresh()->canAccessPanel($panel));
    }
}
