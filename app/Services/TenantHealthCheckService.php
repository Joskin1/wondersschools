<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Verifies the structural and data integrity of a tenant's database
 * after provisioning or as a routine operational health check.
 *
 * Used by:
 *   - ProvisionTenantJob (post-provision gate before activation)
 *   - RetryProvisioningCommand (pre-retry diagnostics)
 *   - Future: scheduled health check monitoring
 */
class TenantHealthCheckService
{
    /**
     * Tables that MUST exist in every fully-provisioned tenant database.
     */
    private const CRITICAL_TABLES = [
        'users',
        'settings',
        'academic_sessions',
        'terms',
        'scores',
        'classrooms',
        'subjects',
    ];

    /**
     * Quick boolean gate used by ProvisionTenantJob to decide whether
     * the tenant can be activated. Returns true only if every check passes.
     */
    public function verifyProvisioning(Tenant $tenant): bool
    {
        $report = $this->fullHealthCheck($tenant);

        return collect($report['checks'])->every(fn (array $check) => $check['passed']);
    }

    /**
     * Detailed health check returning a structured report with pass/fail
     * per individual check. Used by the recovery command for diagnostics.
     *
     * @return array{tenant_id: string, healthy: bool, checks: array<string, array{passed: bool, detail: string}>}
     */
    public function fullHealthCheck(Tenant $tenant): array
    {
        $checks = [];

        $tenant->run(function () use (&$checks) {
            $checks['critical_tables']      = $this->checkCriticalTables();
            $checks['migration_integrity']  = $this->checkMigrationIntegrity();
            $checks['active_session']       = $this->checkActiveSession();
            $checks['terms']                = $this->checkTerms();
            $checks['score_heads']          = $this->checkScoreHeads();
        });

        return [
            'tenant_id' => $tenant->id,
            'healthy'   => collect($checks)->every(fn (array $c) => $c['passed']),
            'checks'    => $checks,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Individual checks
    // ──────────────────────────────────────────────────────────────────────

    private function checkCriticalTables(): array
    {
        $missing = [];

        foreach (self::CRITICAL_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        return [
            'passed' => empty($missing),
            'detail' => empty($missing)
                ? 'All ' . count(self::CRITICAL_TABLES) . ' critical tables exist.'
                : 'Missing tables: ' . implode(', ', $missing),
        ];
    }

    /**
     * Validates migration integrity by comparing the number of executed
     * migrations (rows in the `migrations` table) against the actual
     * migration files on disk in database/migrations/tenant/.
     */
    private function checkMigrationIntegrity(): array
    {
        if (! Schema::hasTable('migrations')) {
            return [
                'passed' => false,
                'detail' => 'The migrations table does not exist.',
            ];
        }

        $executedCount = DB::table('migrations')->count();

        $migrationPath = database_path('migrations/tenant');
        $expectedCount = File::isDirectory($migrationPath)
            ? count(File::glob($migrationPath . '/*.php'))
            : 0;

        $passed = $executedCount >= $expectedCount;

        return [
            'passed' => $passed,
            'detail' => $passed
                ? "All {$expectedCount} migrations applied ({$executedCount} recorded)."
                : "Migration mismatch: {$executedCount} applied vs {$expectedCount} expected.",
        ];
    }

    private function checkActiveSession(): array
    {
        if (! Schema::hasTable('academic_sessions')) {
            return ['passed' => false, 'detail' => 'academic_sessions table missing.'];
        }

        $activeCount = DB::table('academic_sessions')->where('is_active', true)->count();

        return [
            'passed' => $activeCount >= 1,
            'detail' => $activeCount >= 1
                ? "Active session found ({$activeCount})."
                : 'No active academic session found.',
        ];
    }

    private function checkTerms(): array
    {
        if (! Schema::hasTable('terms')) {
            return ['passed' => false, 'detail' => 'terms table missing.'];
        }

        $termCount = DB::table('terms')->count();

        return [
            'passed' => $termCount >= 3,
            'detail' => $termCount >= 3
                ? "Terms present ({$termCount})."
                : "Insufficient terms: {$termCount} found, minimum 3 required.",
        ];
    }

    private function checkScoreHeads(): array
    {
        // Score heads may be in 'score_heads' or 'score_categories' depending on migration naming.
        $table = Schema::hasTable('score_heads') ? 'score_heads' : 'score_categories';

        if (! Schema::hasTable($table)) {
            return ['passed' => false, 'detail' => 'Score heads/categories table missing.'];
        }

        $count = DB::table($table)->count();

        return [
            'passed' => $count >= 1,
            'detail' => $count >= 1
                ? "Score heads present ({$count})."
                : 'No score heads/categories found.',
        ];
    }
}
