<?php

namespace App\Console\Commands;

use App\Models\Central\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantSeed extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant:seed
                            {--school= : The ID of a specific school to seed}
                            {--class= : The seeder class to run}
                            {--force : Force the operation in production}';

    /**
     * The console command description.
     */
    protected $description = 'Run seeders for all tenant databases or a specific tenant';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $schoolId = $this->option('school');

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
            $this->info('No schools found to seed.');
            return self::SUCCESS;
        }

        $this->info("Seeding {$schools->count()} tenant database(s)...");

        $failed = 0;

        foreach ($schools as $school) {
            $this->line('');
            $this->info("▸ Seeding: {$school->name} ({$school->database_name})");

            try {
                $school->configureTenantConnection();

                // Verify connection
                DB::connection('tenant')->getPdo();

                $args = [
                    '--database' => 'tenant',
                    '--force' => $this->option('force') ?: false,
                ];

                if ($this->option('class')) {
                    $args['--class'] = $this->option('class');
                }

                Artisan::call('db:seed', $args, $this->output);

                $this->info("  ✓ {$school->name} seeded successfully.");
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ {$school->name} failed: {$e->getMessage()}");
                Log::error("Tenant seeding failed", [
                    'school_id' => $school->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->line('');
        if ($failed > 0) {
            $this->warn("{$failed} seeding(s) failed. Check logs for details.");
            return self::FAILURE;
        }

        $this->info('All tenant seedings completed successfully.');
        return self::SUCCESS;
    }
}
