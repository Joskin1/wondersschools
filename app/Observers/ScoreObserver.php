<?php

namespace App\Observers;

use App\Models\Score;
use App\Services\AuditLogService;

class ScoreObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the Score "created" event.
     */
    public function created(Score $score): void
    {
        if (auth()->check() && auth()->user() instanceof \App\Models\User) {
            $this->auditLogService->logScoreChange(
                auth()->user(),
                $score,
                'created'
            );
        }
    }

    /**
     * Handle the Score "updated" event.
     */
    public function updated(Score $score): void
    {
        if (auth()->check() && auth()->user() instanceof \App\Models\User) {
            $this->auditLogService->logScoreChange(
                auth()->user(),
                $score,
                'updated',
                $score->getOriginal()
            );
        }
    }

    /**
     * Handle the Score "deleted" event.
     */
    public function deleted(Score $score): void
    {
        if (auth()->check() && auth()->user() instanceof \App\Models\User) {
            $this->auditLogService->logScoreChange(
                auth()->user(),
                $score,
                'deleted',
                $score->getOriginal()
            );
        }
    }

    /**
     * Handle the Score "restored" event.
     */
    public function restored(Score $score): void
    {
        if (auth()->check() && auth()->user() instanceof \App\Models\User) {
            $this->auditLogService->logScoreChange(
                auth()->user(),
                $score,
                'restored'
            );
        }
    }
}
