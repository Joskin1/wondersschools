<?php

namespace Tests\Feature;

use App\Filament\Pages\ReleaseNotes;
use App\Models\ReleaseNote;
use App\Models\ReleaseNoteRead;
use App\Models\User;
use App\Services\ReleaseNoteNotificationService;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReleaseNotesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sudo;
    protected User $teacher;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->sudo = User::factory()->create([
            'role' => 'sudo',
            'is_active' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        // Clean out and ensure specific seeded test notes
        ReleaseNote::query()->delete();

        ReleaseNote::create([
            'version' => 'v1.3.0',
            'title' => 'Standardized Lesson Note & Lesson Plan Online Editor',
            'category' => 'workflow_change',
            'summary' => 'Replaced arbitrary file uploads with standardized online editor.',
            'changes' => ['Online editor for notes and plans', 'Enforce UPPERCASE topics'],
            'procedure_guide' => 'Step 1: Write note online. Step 2: Write plan online.',
            'published_at' => now(),
        ]);

        ReleaseNote::create([
            'version' => 'v1.2.0',
            'title' => 'Dynamic Score Structure',
            'category' => 'feature',
            'summary' => 'Configurable score heads and weighting.',
            'changes' => ['Score heads setup', 'Lock/unlock structure'],
            'procedure_guide' => null,
            'published_at' => now()->subDays(5),
        ]);
    }

    public function test_admin_and_sudo_can_access_release_notes_page()
    {
        $this->actingAs($this->admin);
        $this->assertTrue(ReleaseNotes::canAccess());

        $this->actingAs($this->sudo);
        $this->assertTrue(ReleaseNotes::canAccess());
    }

    public function test_teacher_and_student_cannot_access_release_notes_page()
    {
        $this->actingAs($this->teacher);
        $this->assertFalse(ReleaseNotes::canAccess());

        $this->actingAs($this->student);
        $this->assertFalse(ReleaseNotes::canAccess());
    }

    public function test_release_notes_page_renders_and_displays_notes()
    {
        $this->actingAs($this->admin);

        Livewire::test(ReleaseNotes::class)
            ->assertSuccessful()
            ->assertSee('v1.3.0')
            ->assertSee('Standardized Lesson Note')
            ->assertSee('Action Required')
            ->assertSee('v1.2.0')
            ->assertSee('Dynamic Score Structure');
    }

    public function test_category_filtering_works()
    {
        $this->actingAs($this->admin);

        Livewire::test(ReleaseNotes::class)
            ->set('selectedCategory', 'workflow_change')
            ->assertSee('v1.3.0')
            ->assertDontSee('v1.2.0')
            ->set('selectedCategory', 'feature')
            ->assertSee('v1.2.0')
            ->assertDontSee('v1.3.0');
    }

    public function test_search_filtering_works()
    {
        $this->actingAs($this->admin);

        Livewire::test(ReleaseNotes::class)
            ->set('search', 'Lesson Plan')
            ->assertSee('v1.3.0')
            ->assertDontSee('v1.2.0');
    }

    public function test_unread_release_notes_tracking()
    {
        $this->assertEquals(2, ReleaseNote::unreadFor($this->admin)->count());

        $note = ReleaseNote::where('version', 'v1.3.0')->first();
        $this->assertFalse($note->isReadBy($this->admin));

        $note->markAsReadFor($this->admin);
        $this->assertTrue($note->isReadBy($this->admin));
        $this->assertEquals(1, ReleaseNote::unreadFor($this->admin)->count());
    }

    public function test_notification_service_sends_database_notification_to_admin()
    {
        $service = app(ReleaseNoteNotificationService::class);
        $service->notifyUnreadReleases($this->admin);

        $this->assertEquals(1, $this->admin->unreadNotifications()->count());

        $notification = $this->admin->unreadNotifications()->first();
        $this->assertStringContainsString('New System Update', $notification->data['title'] ?? '');
        $this->assertEquals('v1.3.0', $notification->data['viewData']['release_note_version'] ?? null);

        // Subsequent call does not create duplicate notification
        $service->notifyUnreadReleases($this->admin);
        $this->assertEquals(1, $this->admin->unreadNotifications()->count());
    }

    public function test_notification_service_does_not_notify_teachers()
    {
        $service = app(ReleaseNoteNotificationService::class);
        $service->notifyUnreadReleases($this->teacher);

        $this->assertEquals(0, $this->teacher->unreadNotifications()->count());
    }

    public function test_login_event_triggers_release_note_notification()
    {
        event(new Login('web', $this->admin, false));

        $this->assertEquals(1, $this->admin->unreadNotifications()->count());
    }

    public function test_mark_all_as_read_clears_unread_status_and_notifications()
    {
        $service = app(ReleaseNoteNotificationService::class);
        $service->notifyUnreadReleases($this->admin);

        $this->assertEquals(2, ReleaseNote::unreadFor($this->admin)->count());
        $this->assertEquals(1, $this->admin->unreadNotifications()->count());

        $service->markAllAsRead($this->admin);

        $this->assertEquals(0, ReleaseNote::unreadFor($this->admin)->count());
        $this->assertEquals(0, $this->admin->unreadNotifications()->count());
    }
}
