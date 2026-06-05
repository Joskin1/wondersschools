<?php

declare(strict_types=1);

namespace App\Jobs\Tenancy;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\ProvisionLogger;
use App\Services\TenantBrandingService;
use App\Services\TenantHealthCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Single orchestrator job for the entire tenant provisioning pipeline.
 *
 * Responsibilities (executed in sequence):
 *   1. Acquire a distributed lock to prevent duplicate provisioning.
 *   2. Create the tenant's dedicated database.
 *   3. Run tenant-specific migrations.
 *   4. Run TenantDatabaseSeeder (transaction-wrapped, idempotent).
 *   5. Validate provisioning via TenantHealthCheckService.
 *   6. Activate the tenant and warm the branding cache.
 *
 * Every step is idempotent, so retries re-run the full pipeline safely:
 *   - CreateDatabase catches "database already exists"
 *   - Migrations are inherently idempotent
 *   - Seeder uses firstOrCreate() and count guards
 *
 * On failure: tenant status is set to "failed" and the exception is re-thrown
 * so the queue worker can retry (up to $tries).
 */
class ProvisionTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts before the job is marked as failed.
     */
    public int $tries = 3;

    /**
     * Backoff intervals between retries (seconds).
     *
     * @var array<int>
     */
    public array $backoff = [30, 120];

    /**
     * Maximum seconds the job can run before timing out.
     */
    public int $timeout = 300;

    public function __construct(
        public Tenant $tenant,
    ) {}

    public function handle(TenantHealthCheckService $healthCheck, TenantBrandingService $branding): void
    {
        $tenantId = $this->tenant->id;

        // ── Distributed lock ─────────────────────────────────────────────
        // Prevents duplicate provisioning from double-clicks, multiple
        // queue retries, or concurrent queue workers. Lock TTL matches
        // the job timeout (300s) + buffer.
        $lock = Cache::lock("tenant-provision:{$tenantId}", 600);

        if (! $lock->get()) {
            // Another worker is already provisioning this tenant.
            // Release back to the queue with a delay instead of failing.
            $this->release(60);

            return;
        }

        try {
            // ── Status: provisioning ─────────────────────────────────────
            $this->tenant->update(['status' => TenantStatus::Provisioning]);

            // ── Step 1: Create database ──────────────────────────────────
            ProvisionLogger::log($tenantId, 'database_creation', 'started');
            $this->createDatabase();
            ProvisionLogger::log($tenantId, 'database_creation', 'success');

            // ── Step 2: Run migrations ───────────────────────────────────
            ProvisionLogger::log($tenantId, 'migration', 'started');
            $this->migrateDatabase();
            ProvisionLogger::log($tenantId, 'migration', 'success');

            // ── Step 3: Seed database ────────────────────────────────────
            ProvisionLogger::log($tenantId, 'seeding', 'started');
            $this->seedDatabase();
            ProvisionLogger::log($tenantId, 'seeding', 'success');

            // ── Step 3.5: Create public storage symlink ──────────────────
            ProvisionLogger::log($tenantId, 'storage_link', 'started');
            $this->createStorageLink();
            ProvisionLogger::log($tenantId, 'storage_link', 'success');

            // ── Step 4: Validate provisioning ────────────────────────────
            ProvisionLogger::log($tenantId, 'validation', 'started');

            if (! $healthCheck->verifyProvisioning($this->tenant)) {
                $report = $healthCheck->fullHealthCheck($this->tenant);
                $failedChecks = collect($report['checks'])
                    ->filter(fn (array $c) => ! $c['passed'])
                    ->map(fn (array $c) => $c['detail'])
                    ->implode('; ');

                throw new \RuntimeException("Post-provision validation failed: {$failedChecks}");
            }

            ProvisionLogger::log($tenantId, 'validation', 'success');

            // ── Step 5: Activate ─────────────────────────────────────────
            $this->tenant->update([
                'status'              => TenantStatus::Active,
                'last_provisioned_at' => now(),
            ]);
            ProvisionLogger::log($tenantId, 'activation', 'success');

            // ── Step 6: Warm branding cache ──────────────────────────────
            $this->tenant->load('domains');
            $branding->warm($this->tenant);

        } catch (Throwable $e) {
            $this->tenant->update(['status' => TenantStatus::Failed]);
            ProvisionLogger::log($tenantId, 'provisioning', 'failed', $e->getMessage());

            // Re-throw so the queue worker records the failure and retries.
            throw $e;
        } finally {
            $lock->forceRelease();
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private step implementations (idempotent)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Step 1: Create the tenant's dedicated database.
     * Idempotent: catches "database already exists" (HY000 / 1007).
     */
    private function createDatabase(): void
    {
        $this->tenant->database()->makeCredentials();

        try {
            $this->tenant->database()->manager()->createDatabase($this->tenant);
        } catch (QueryException $e) {
            if ($e->getCode() !== 'HY000' || ! str_contains($e->getMessage(), 'database exists')) {
                throw $e;
            }
            // Database already exists — idempotent no-op.
        }
    }

    /**
     * Step 2: Run tenant-specific migrations.
     * Idempotent: Laravel migrations track applied state in the migrations table.
     */
    private function migrateDatabase(): void
    {
        $this->tenant->run(function () {
            Artisan::call('migrate', [
                '--path'     => config('tenancy.migration_parameters.--path'),
                '--realpath' => true,
                '--force'    => true,
            ]);
        });
    }

    /**
     * Step 3: Run TenantDatabaseSeeder.
     * Idempotent: seeder uses firstOrCreate() and count guards inside a transaction.
     */
    private function seedDatabase(): void
    {
        $this->tenant->run(function () {
            Artisan::call('db:seed', [
                '--class' => config('tenancy.seeder_parameters.--class', 'Database\\Seeders\\TenantDatabaseSeeder'),
                '--force' => true,
            ]);
        });
    }

    /**
     * Handle job failure.
     * Reverts active tenancy state to landlord connection if the job crashes or times out,
     * ensuring that subsequent jobs picked up by this worker thread do not leak contexts.
     */
    public function failed(Throwable $exception): void
    {
        try {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        } catch (Throwable) {
            // Swallow connection cleanup errors to preserve the original exception report.
        }
    }

    /**
     * Create symbolic link from central public storage to tenant public directory.
     * Idempotent: checks for existing links and handles directories.
     */
    private function createStorageLink(): void
    {
        $tenantId = $this->tenant->id;
        $suffixBase = config('tenancy.filesystem.suffix_base', 'tenant');
        $suffix = $suffixBase . $tenantId;

        $target = storage_path("{$suffix}/app/public");
        $link = storage_path("app/public/{$suffix}");

        // Ensure target directory exists
        if (! is_dir($target)) {
            if (! mkdir($target, 0755, true) && ! is_dir($target)) {
                throw new \RuntimeException("Failed to create tenant storage directory: {$target}");
            }
        }

        // Ensure central public directory exists
        if (! is_dir(storage_path('app/public'))) {
            mkdir(storage_path('app/public'), 0755, true);
        }

        // Handle existing link/directory
        if (file_exists($link) || is_link($link)) {
            if (is_link($link)) {
                $existingTarget = readlink($link);
                if ($existingTarget === $target) {
                    return;
                }
                unlink($link);
            } else {
                // Skip if it's already a real directory (to prevent data deletion)
                return;
            }
        }

        if (! symlink($target, $link)) {
            throw new \RuntimeException("Failed to create symbolic link: {$link} -> {$target}");
        }
    }
}
