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
        return SystemSetting::getInt('current_session_id');
    }

    public function getCurrentTermId(): ?int
    {
        return SystemSetting::getInt('current_term_id');
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

        DB::transaction(function () use ($targetTerm, $currentSessionId, $currentTerm) {
            // If migrating to First Term, increment session and promote students
            if ($targetTerm->name === 'First Term') {
                $currentSession = AcademicSession::find($currentSessionId);
                // Assuming session name format "2024/2025"
                $parts = explode('/', $currentSession->name);
                $startYear = (int)$parts[0] + 1;
                $endYear = (int)$parts[1] + 1;
                $newSessionName = "{$startYear}/{$endYear}";

                $newSession = AcademicSession::firstOrCreate(['name' => $newSessionName]);
                
                SystemSetting::updateOrCreate(['key' => 'current_session_id'], ['value' => $newSession->id]);

                // Promote students to next classroom
                $this->promoteStudents();
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

    /**
     * Promote all students to the next classroom.
     * This is called when migrating from Third Term to First Term.
     */
    protected function promoteStudents(): void
    {
        $classrooms = \App\Models\Classroom::withoutGlobalScopes()->orderBy('name')->get();
        
        // Promote students in reverse order to avoid cascading updates
        // (e.g. Move Class 2 to Class 3, THEN move Class 1 to Class 2)
        for ($i = count($classrooms) - 2; $i >= 0; $i--) {
            $currentClassroomId = $classrooms[$i]->id;
            $nextClassroomId = $classrooms[$i + 1]->id;

            \App\Models\Student::withoutGlobalScopes()
                ->where('classroom_id', $currentClassroomId)
                ->update(['classroom_id' => $nextClassroomId]);
        }
        
        // Students in the final classroom remain there (or could be graduated/archived)
        // This is a design decision - for now they stay in the same classroom
    }
}
