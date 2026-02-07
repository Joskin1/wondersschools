<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\TeacherAssignmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAssignmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TeacherAssignmentPolicy $policy;
    protected User $admin;
    protected User $teacher;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TeacherAssignmentPolicy();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->staff = User::factory()->create(['role' => 'staff']);
    }

    /** @test */
    public function admin_can_view_any_teacher_assignments()
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    /** @test */
    public function teacher_cannot_view_any_teacher_assignments()
    {
        $this->assertFalse($this->policy->viewAny($this->teacher));
    }

    /** @test */
    public function staff_cannot_view_any_teacher_assignments()
    {
        $this->assertFalse($this->policy->viewAny($this->staff));
    }

    /** @test */
    public function admin_can_create_teacher_assignment()
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    /** @test */
    public function teacher_cannot_create_teacher_assignment()
    {
        $this->assertFalse($this->policy->create($this->teacher));
    }

    /** @test */
    public function admin_can_update_teacher_assignment()
    {
        $this->assertTrue($this->policy->update($this->admin));
    }

    /** @test */
    public function teacher_cannot_update_teacher_assignment()
    {
        $this->assertFalse($this->policy->update($this->teacher));
    }

    /** @test */
    public function admin_can_delete_teacher_assignment()
    {
        $this->assertTrue($this->policy->delete($this->admin));
    }

    /** @test */
    public function teacher_cannot_delete_teacher_assignment()
    {
        $this->assertFalse($this->policy->delete($this->teacher));
    }

    /** @test */
    public function admin_can_delete_any_teacher_assignments()
    {
        $this->assertTrue($this->policy->deleteAny($this->admin));
    }

    /** @test */
    public function teacher_cannot_delete_any_teacher_assignments()
    {
        $this->assertFalse($this->policy->deleteAny($this->teacher));
    }

    /** @test */
    public function admin_can_import_teacher_assignments()
    {
        $this->assertTrue($this->policy->import($this->admin));
    }

    /** @test */
    public function teacher_cannot_import_teacher_assignments()
    {
        $this->assertFalse($this->policy->import($this->teacher));
    }

    /** @test */
    public function admin_can_export_teacher_assignments()
    {
        $this->assertTrue($this->policy->export($this->admin));
    }

    /** @test */
    public function teacher_cannot_export_teacher_assignments()
    {
        $this->assertFalse($this->policy->export($this->teacher));
    }
}
