<?php

namespace Database\Seeders;

use App\Models\Session;
use App\Models\SubmissionWindow;
use Illuminate\Database\Seeder;

class SubmissionWindowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get active session and term
        $activeSession = Session::active()->first();
        
        if (!$activeSession || !$activeSession->activeTerm) {
            $this->command?->error('No active session or term found. Please run SessionSeeder first.');
            return;
        }

        $activeTerm = $activeSession->activeTerm;

        // Keep legacy records available for existing installations. They no longer
        // control whether teachers can submit lesson notes or lesson plans.
        $windowsCreated = 0;
        for ($week = 1; $week <= config('academic.weeks_per_term'); $week++) {
            SubmissionWindow::firstOrCreate(
                [
                    'session_id' => $activeSession->id,
                    'term_id' => $activeTerm->id,
                    'week_number' => $week,
                ]
            );
            $windowsCreated++;
        }

        $this->command?->info("Created {$windowsCreated} submission windows for {$activeSession->name} - {$activeTerm->name}!");
        $this->command?->info('All 14 weeks are available for submissions without manual window management.');
    }
}
