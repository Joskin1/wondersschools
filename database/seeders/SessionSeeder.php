<?php

namespace Database\Seeders;

use App\Models\Session;
use App\Models\Term;
use Illuminate\Database\Seeder;

class SessionSeeder extends Seeder
{
    /**
     * Create an active academic session with its three terms.
     * If the session already exists, it will be retrieved and activated.
     */
    public function run(): void
    {
        $startYear = now()->year;
        $sessionName = "{$startYear}-" . ($startYear + 1);

        // Try to fetch existing session; otherwise create a new one with terms.
        $session = Session::firstOrCreate(
            ['name' => $sessionName],
            [
                'start_year' => $startYear,
                'end_year'   => $startYear + 1,
                'is_active'  => false,
            ]
        );

        // If this is a freshly created session, also create its three terms.
        if ($session->terms()->count() === 0) {
            // Use the model's helper to generate terms.
            $session = Session::createWithTerms($startYear);
        }

        // Activate the session (idempotent – will set is_active true if not already).
        if (! $session->is_active) {
            $session->activate();
        }

        // Ensure the first term is active.
        $firstTerm = $session->terms()->where('order', 1)->first();
        if ($firstTerm && ! $firstTerm->is_active) {
            $firstTerm->update(['is_active' => true]);
        }

        $this->command->info("📅 Session {$session->name} is ready with first term active.");
    }
}
