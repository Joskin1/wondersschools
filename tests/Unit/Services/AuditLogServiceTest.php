<?php

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Models\Score;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogService $auditLogService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditLogService = app(AuditLogService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_creates_audit_log_entry()
    {
        $score = Score::factory()->create();

        $this->auditLogService->log(
            action: 'created',
            auditable: $score,
            user: $this->user,
            oldValue: null,
            newValue: ['value' => 85]
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Score::class,
            'auditable_id' => $score->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_stores_old_and_new_values_as_json()
    {
        $score = Score::factory()->create();

        $oldValue = ['value' => 80];
        $newValue = ['value' => 85];

        $this->auditLogService->log(
            action: 'updated',
            auditable: $score,
            user: $this->user,
            oldValue: $oldValue,
            newValue: $newValue
        );

        $log = AuditLog::first();

        $this->assertEquals($oldValue, $log->old_value);
        $this->assertEquals($newValue, $log->new_value);
    }

    /** @test */
    public function it_captures_ip_address()
    {
        $score = Score::factory()->create();

        $this->auditLogService->log(
            action: 'created',
            auditable: $score,
            user: $this->user
        );

        $log = AuditLog::first();

        $this->assertNotNull($log->ip_address);
    }

    /** @test */
    public function it_allows_null_user()
    {
        $score = Score::factory()->create();

        $this->auditLogService->log(
            action: 'created',
            auditable: $score,
            user: null
        );

        $log = AuditLog::first();

        $this->assertNull($log->user_id);
    }

    /** @test */
    public function it_allows_null_old_and_new_values()
    {
        $score = Score::factory()->create();

        $this->auditLogService->log(
            action: 'deleted',
            auditable: $score,
            user: $this->user,
            oldValue: null,
            newValue: null
        );

        $log = AuditLog::first();

        $this->assertNull($log->old_value);
        $this->assertNull($log->new_value);
    }

    /** @test */
    public function it_creates_multiple_audit_logs()
    {
        $score1 = Score::factory()->create();
        $score2 = Score::factory()->create();

        $this->auditLogService->log('created', $score1, $this->user);
        $this->auditLogService->log('updated', $score2, $this->user);

        $this->assertCount(2, AuditLog::all());
    }

    /** @test */
    public function it_associates_log_with_correct_auditable_model()
    {
        $score = Score::factory()->create();

        $this->auditLogService->log(
            action: 'created',
            auditable: $score,
            user: $this->user
        );

        $log = AuditLog::first();

        $this->assertEquals(Score::class, $log->auditable_type);
        $this->assertEquals($score->id, $log->auditable_id);
        $this->assertEquals($score->id, $log->auditable->id);
    }
}
