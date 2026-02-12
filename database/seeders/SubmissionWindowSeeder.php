<?php

namespace Database\Seeders;

use App\Models\Session;
use App\Models\SubmissionWindow;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

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
            $this->command->error('No active session or term found. Please run SessionSeeder first.');
            return;
        }

        $activeTerm = $activeSession->activeTerm;

        // Create submission windows for weeks 1-12 of the current term
        $windowsCreated = 0;
        for ($week = 1; $week <= 12; $week++) {
            // Calculate dates for this week
            // Week 1 starts now, each subsequent week starts 7 days later
            $weekStart = Carbon::now()->addWeeks($week - 1)->startOfWeek();
            $opensAt = $weekStart->copy()->setTime(0, 0, 0); // Monday 00:00
            $closesAt = $weekStart->copy()->addDays(4)->setTime(23, 59, 59); // Friday 23:59

            // Current week and next week are open, others are closed
            $isOpen = $week <= 2;

            SubmissionWindow::firstOrCreate(
                [
                    'session_id' => $activeSession->id,
                    'term_id' => $activeTerm->id,
                    'week_number' => $week,
                ],
                [
                    'opens_at' => $opensAt,
                    'closes_at' => $closesAt,
                    'is_open' => $isOpen,
                    'updated_by' => null,
                ]
            );
            $windowsCreated++;
        }

        $this->command->info("Created {$windowsCreated} submission windows for {$activeSession->name} - {$activeTerm->name}!");
        $this->command->info("Weeks 1-2 are currently open for submissions.");
    }
}
