<?php

use App\Models\TermScoringSnapshot;
use App\Services\MidtermCalculationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Purge all orphan MidtermSubjectResult rows where student has no score entries
        DB::table('midterm_subject_results as msr')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('scores as s')
                    ->whereColumn('s.student_id', 'msr.student_id')
                    ->whereColumn('s.subject_id', 'msr.subject_id')
                    ->whereColumn('s.classroom_id', 'msr.classroom_id')
                    ->whereColumn('s.session_id', 'msr.session_id')
                    ->whereColumn('s.term_id', 'msr.term_id');
            })
            ->delete();

        // 2. Re-sync midterm term summaries for all active snapshots
        try {
            $snapshots = TermScoringSnapshot::where('mode', 'midterm')->get();
            $calcService = app(MidtermCalculationService::class);

            foreach ($snapshots as $snapshot) {
                try {
                    $calcService->computeClassMidtermSummary(
                        (int) $snapshot->classroom_id,
                        (int) $snapshot->session_id,
                        (int) $snapshot->term_id,
                        $snapshot
                    );
                } catch (\Throwable $e) {
                    // Fail-safe per snapshot
                }
            }
        } catch (\Throwable $e) {
            // Fail-safe
        }
    }

    public function down(): void
    {
        // Data cleanup is permanent
    }
};
