<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\AcademicSession;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Exception;

class MigrationService
{
    public function getCurrentSessionId(): ?int
    {
        return SystemSetting::where('key', 'current_session_id')->value('value');
    }

    public function getCurrentTermId(): ?int
    {
        return SystemSetting::where('key', 'current_term_id')->value('value');
    }

    public function migrateTerm(int $targetTermId): void
    {
        $currentSessionId = $this->getCurrentSessionId();
        $currentTermId = $this->getCurrentTermId();

        if (!$currentSessionId || !$currentTermId) {
            throw new Exception("Current session or term not set.");
        }

        $currentTerm = Term::find($currentTermId);
        $targetTerm = Term::find($targetTermId);

        if (!$currentTerm || !$targetTerm) {
            throw new Exception("Invalid term ID.");
        }

        // Validate transition
        $this->validateTransition($currentTerm->name, $targetTerm->name);

        DB::transaction(function () use ($targetTerm, $currentSessionId) {
            // If migrating to First Term, increment session
            if ($targetTerm->name === 'First Term') {
                $currentSession = AcademicSession::find($currentSessionId);
                // Assuming session name format "2024/2025"
                $parts = explode('/', $currentSession->name);
                $startYear = (int)$parts[0] + 1;
                $endYear = (int)$parts[1] + 1;
                $newSessionName = "{$startYear}/{$endYear}";

                $newSession = AcademicSession::firstOrCreate(['name' => $newSessionName]);
                
                SystemSetting::updateOrCreate(['key' => 'current_session_id'], ['value' => $newSession->id]);
            }

            SystemSetting::updateOrCreate(['key' => 'current_term_id'], ['value' => $targetTerm->id]);
        });
    }

    protected function validateTransition(string $current, string $target): void
    {
        $allowed = [
            'First Term' => ['Second Term'],
            'Second Term' => ['Third Term'],
            'Third Term' => ['First Term'],
        ];

        if (!in_array($target, $allowed[$current] ?? [])) {
            throw new Exception("Migration from {$current} to {$target} is not permitted. Complete terms sequentially.");
        }
    }
}
