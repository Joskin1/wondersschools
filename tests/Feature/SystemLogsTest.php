<?php

namespace Tests\Feature;

use App\Filament\Pages\SystemLogs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class SystemLogsTest extends TestCase
{
    use RefreshDatabase;

    protected string $testLogDir;
    protected string $testLogFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testLogDir = storage_path('logs');
        if (!File::isDirectory($this->testLogDir)) {
            File::makeDirectory($this->testLogDir, 0755, true);
        }

        $this->testLogFile = "{$this->testLogDir}/test_system_logs.log";
        $logContent = <<<LOG
[2026-09-07 08:30:00] local.INFO: Application initialized successfully [] []
[2026-09-07 08:31:00] local.WARNING: Memory limit approaching threshold {"memory":"90%"} []
[2026-09-07 08:32:00] local.ERROR: Registration failed due to database constraint {"code":500}
#0 /var/www/Wonder/app/Livewire/PublicTeacherRegistrationForm.php(85): error()
#1 {main}
LOG;
        File::put($this->testLogFile, $logContent);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testLogFile)) {
            File::delete($this->testLogFile);
        }

        parent::tearDown();
    }

    public function test_sudo_user_can_access_system_logs()
    {
        $user = User::factory()->create(['role' => 'sudo']);
        $this->actingAs($user);

        $this->assertTrue(SystemLogs::canAccess());
    }

    public function test_admin_user_cannot_access_system_logs()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $this->assertFalse(SystemLogs::canAccess());
    }

    public function test_teacher_cannot_access_system_logs()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($user);

        $this->assertFalse(SystemLogs::canAccess());
    }

    public function test_student_cannot_access_system_logs()
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);

        $this->assertFalse(SystemLogs::canAccess());
    }

    public function test_system_logs_page_renders_and_parses_logs()
    {
        $user = User::factory()->create(['role' => 'sudo']);
        $this->actingAs($user);

        Livewire::test(SystemLogs::class)
            ->set('selectedFile', 'Central: test_system_logs.log')
            ->assertSee('Total Log Entries')
            ->assertSee('Registration failed due to database constraint')
            ->assertSee('Memory limit approaching threshold');
    }

    public function test_system_logs_can_filter_by_level()
    {
        $user = User::factory()->create(['role' => 'sudo']);
        $this->actingAs($user);

        Livewire::test(SystemLogs::class)
            ->set('selectedFile', 'Central: test_system_logs.log')
            ->set('level', 'error')
            ->assertSee('Registration failed due to database constraint')
            ->assertDontSee('Memory limit approaching threshold');
    }

    public function test_system_logs_can_search_entries()
    {
        $user = User::factory()->create(['role' => 'sudo']);
        $this->actingAs($user);

        Livewire::test(SystemLogs::class)
            ->set('selectedFile', 'Central: test_system_logs.log')
            ->set('search', 'Memory limit')
            ->assertSee('Memory limit approaching threshold')
            ->assertDontSee('Registration failed due to database constraint');
    }
}
