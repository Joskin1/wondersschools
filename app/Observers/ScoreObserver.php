<?php

namespace App\Observers;

use App\Models\Score;
use App\Services\ResultService;

class ScoreObserver
{
    protected ResultService $resultService;

    public function __construct(ResultService $resultService)
    {
        $this->resultService = $resultService;
    }

    /**
     * Handle the Score "created" event.
     */
    public function created(Score $score): void
    {
        $this->recalculateResult($score);
    }

    /**
     * Handle the Score "updated" event.
     */
    public function updated(Score $score): void
    {
        $this->recalculateResult($score);
    }

    /**
     * Handle the Score "deleted" event.
     */
    public function deleted(Score $score): void
    {
        $this->recalculateResult($score);
    }

    /**
     * Recalculate result for the student and update classroom positions.
     */
    protected function recalculateResult(Score $score): void
    {
        // Calculate result for this student
        $result = $this->resultService->calculateStudentResult(
            $score->student_id,
            $score->academic_session_id,
            $score->term_id
        );

        // Recalculate positions for all students in the classroom
        if ($result && $result->classroom_id) {
            $this->resultService->recalculateClassroomPositions(
                $result->classroom_id,
                $score->academic_session_id,
                $score->term_id
            );
        }
    }
}
