<?php

namespace App\Services;

use App\Models\Term;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\TermMigrationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TermMigrationService
{
    /**
     * Migrate to the next term
     *
     * @param Term $currentTerm
     * @param string $targetTermName
     * @return array
     * @throws \Exception
     */
    public function migrateTerm(Term $currentTerm, string $targetTermName): array
    {
        // Validate the migration
        if (!$currentTerm->canMigrateTo($targetTermName)) {
            throw new \Exception('You cannot migrate to this term. Please follow the term sequence.');
        }

        return DB::transaction(function () use ($currentTerm, $targetTermName) {
            $isNewSession = $currentTerm->isLastTerm() && $targetTermName === 'First Term';
            
            // Create new session if migrating from Third to First
            if ($isNewSession) {
                $newSession = $this->createNewSession($currentTerm->academicSession);
                $newTerm = $this->createTerm($newSession, $targetTermName);
                
                // Promote all students
                $promotionResults = $this->promoteAllStudents();
            } else {
                // Create new term in same session
                $newTerm = $this->createTerm($currentTerm->academicSession, $targetTermName);
                $promotionResults = ['promoted' => 0, 'graduated' => 0];
            }

            // Mark old term as not current
            $currentTerm->is_current = false;
            $currentTerm->save();

            // Mark new term as current
            $newTerm->is_current = true;
            $newTerm->save();

            // Log the migration
            $this->logMigration($currentTerm, $newTerm, $promotionResults);

            return [
                'success' => true,
                'old_term' => $currentTerm->name,
                'new_term' => $newTerm->name,
                'new_session' => $isNewSession,
                'students_promoted' => $promotionResults['promoted'] ?? 0,
                'students_graduated' => $promotionResults['graduated'] ?? 0,
            ];
        });
    }

    /**
     * Create a new academic session
     */
    private function createNewSession(AcademicSession $previousSession): AcademicSession
    {
        // Parse the previous session name (e.g., "2024/2025")
        $years = explode('/', $previousSession->name);
        $newStartYear = (int)$years[0] + 1;
        $newEndYear = (int)$years[1] + 1;

        return AcademicSession::create([
            'name' => "{$newStartYear}/{$newEndYear}",
            'start_date' => now()->addYear(),
            'end_date' => now()->addYear()->addMonths(9),
            'is_current' => true,
        ]);
    }

    /**
     * Create a new term
     */
    private function createTerm(AcademicSession $session, string $termName): Term
    {
        return Term::create([
            'name' => $termName,
            'academic_session_id' => $session->id,
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'is_current' => false, // Will be set to true after old term is marked as not current
        ]);
    }

    /**
     * Promote all non-graduated students
     */
    private function promoteAllStudents(): array
    {
        $promoted = 0;
        $graduated = 0;

        $students = Student::where('is_graduated', false)->get();

        foreach ($students as $student) {
            $wasInTerminalClass = $student->classroom && 
                                  $student->classroom->name === 'Year 6';
            
            if ($student->promoteToNextClassroom()) {
                if ($wasInTerminalClass) {
                    $graduated++;
                } else {
                    $promoted++;
                }
            }
        }

        return [
            'promoted' => $promoted,
            'graduated' => $graduated,
        ];
    }

    /**
     * Log the term migration
     */
    private function logMigration(Term $oldTerm, Term $newTerm, array $promotionResults): void
    {
        TermMigrationLog::create([
            'from_term_id' => $oldTerm->id,
            'to_term_id' => $newTerm->id,
            'from_session_id' => $oldTerm->academic_session_id,
            'to_session_id' => $newTerm->academic_session_id,
            'user_id' => Auth::id(),
            'students_promoted' => $promotionResults['promoted'] ?? 0,
            'students_graduated' => $promotionResults['graduated'] ?? 0,
        ]);
    }

    /**
     * Get validation error message for invalid migration
     */
    public function getValidationError(Term $currentTerm, string $targetTermName): string
    {
        if (!$currentTerm->canMigrateTo($targetTermName)) {
            return 'You cannot migrate to this term. Please follow the term sequence.';
        }

        return '';
    }
}
