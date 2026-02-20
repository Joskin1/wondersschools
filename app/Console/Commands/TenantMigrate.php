<?php

namespace App\Console\Commands;

use App\Models\Central\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantMigrate extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant:migrate
                            {--school= : The ID of a specific school to migrate}
                            {--fresh : Drop all tables and re-run all migrations}
                            {--seed : Seed the database after migrating}
                            {--force : Force the operation in production}';

    /**
     * The console command description.
     */
    protected $description = 'Run migrations for all tenant databases or a specific tenant';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $schoolId = $this->option('school');
        $migrationPath = config('tenancy.tenant_migration_path');
        $relativePath = str_replace(base_path() . '/', '', $migrationPath);

        if ($schoolId) {
            $schools = School::where('id', $schoolId)->get();
            if ($schools->isEmpty()) {
                $this->error("School with ID {$schoolId} not found.");
                return self::FAILURE;
            }
        } else {
            $schools = School::active()->get();
        }

        if ($schools->isEmpty()) {
            $this->info('No schools found to migrate.');
            return self::SUCCESS;
        }

        $this->info("Migrating {$schools->count()} tenant database(s)...");

        $failed = 0;

        foreach ($schools as $school) {
            $this->line('');
            $this->info("▸ Migrating: {$school->name} ({$school->database_name})");

            try {
                $school->configureTenantConnection();

                // Verify connection
                DB::connection('tenant')->getPdo();

                $args = [
                    '--database' => 'tenant',
                    '--path' => $relativePath,
                    '--force' => $this->option('force') ?: false,
                ];

                if ($this->option('fresh')) {
                    Artisan::call('migrate:fresh', $args, $this->output);
                } else {
                    Artisan::call('migrate', $args, $this->output);
                }

                if ($this->option('seed')) {
                    Artisan::call('db:seed', [
                        '--database' => 'tenant',
                        '--force' => true,
                    ], $this->output);
                }

                $this->info("  ✓ {$school->name} migrated successfully.");
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ {$school->name} failed: {$e->getMessage()}");
                Log::error("Tenant migration failed", [
                    'school_id' => $school->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->line('');
        if ($failed > 0) {
            $this->warn("{$failed} migration(s) failed. Check logs for details.");
            return self::FAILURE;
        }

        $this->info('All tenant migrations completed successfully.');
        return self::SUCCESS;
    }
}
