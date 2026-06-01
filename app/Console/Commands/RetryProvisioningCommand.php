<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Jobs\Tenancy\ProvisionTenantJob;
use App\Models\Tenant;
use App\Services\ProvisionLogger;
use App\Services\TenantHealthCheckService;
use Illuminate\Console\Command;

/**
 * Artisan command for recovering tenants stuck in a failed provisioning state.
 *
 * Usage:
 *   php artisan tenancy:retry-provisioning                 # retry all failed tenants
 *   php artisan tenancy:retry-provisioning royal-academy    # retry a single tenant
 *   php artisan tenancy:retry-provisioning --diagnose       # show health report without retrying
 */
class RetryProvisioningCommand extends Command
{
    protected $signature = 'tenancy:retry-provisioning
                            {tenant_id? : The ID/slug of a specific tenant to retry}
                            {--diagnose : Show a health report without dispatching a retry}';

    protected $description = 'Retry provisioning for tenants in a failed state.';

    public function handle(TenantHealthCheckService $healthCheck): int
    {
        $tenantId = $this->argument('tenant_id');

        $query = Tenant::on('landlord');

        if ($tenantId) {
            $query->where('id', $tenantId);
        } else {
            $query->where('status', TenantStatus::Failed);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info($tenantId
                ? "Tenant '{$tenantId}' not found."
                : 'No tenants in a failed state.'
            );

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            /** @var Tenant $tenant */
            $this->line('');
            $this->info("── {$tenant->id} ({$tenant->name}) ──");
            $this->line("  Status: {$tenant->status?->label() ?? $tenant->getRawOriginal('status')}");

            // ── Diagnose mode ────────────────────────────────────────────
            if ($this->option('diagnose')) {
                $this->runDiagnostics($tenant, $healthCheck);
                continue;
            }

            // ── Safety gate ──────────────────────────────────────────────
            if ($tenant->status !== TenantStatus::Failed) {
                $this->warn("  Skipping: tenant is not in a 'failed' state (current: {$tenant->status?->label()}).");
                continue;
            }

            // ── Reset and dispatch ───────────────────────────────────────
            $tenant->update(['status' => TenantStatus::Pending]);
            ProvisionLogger::log($tenant->id, 'retry', 'started', 'Manual retry via artisan command.');

            ProvisionTenantJob::dispatch($tenant);

            $this->info("  ✓ ProvisionTenantJob dispatched. Status reset to 'pending'.");
        }

        $this->line('');

        return self::SUCCESS;
    }

    private function runDiagnostics(Tenant $tenant, TenantHealthCheckService $healthCheck): void
    {
        try {
            $report = $healthCheck->fullHealthCheck($tenant);
        } catch (\Throwable $e) {
            $this->error("  Could not run health check: {$e->getMessage()}");

            return;
        }

        $this->line("  Overall: " . ($report['healthy'] ? '✅ Healthy' : '❌ Unhealthy'));

        foreach ($report['checks'] as $checkName => $check) {
            $icon = $check['passed'] ? '✅' : '❌';
            $this->line("    {$icon} {$checkName}: {$check['detail']}");
        }
    }
}
