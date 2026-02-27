<?php

namespace App\Jobs;

use App\Services\ResultCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateTermResults implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly int $classroomId,
        public readonly int $sessionId,
        public readonly int $termId,
    ) {}

    public function handle(ResultCalculationService $service): void
    {
        try {
            $service->calculateForClass(
                $this->classroomId,
                $this->sessionId,
                $this->termId,
            );

            Log::info('Term results calculated.', [
                'classroom_id' => $this->classroomId,
                'session_id'   => $this->sessionId,
                'term_id'      => $this->termId,
            ]);
        } catch (\RuntimeException $e) {
            Log::warning('Term result calculation skipped: ' . $e->getMessage(), [
                'classroom_id' => $this->classroomId,
                'session_id'   => $this->sessionId,
                'term_id'      => $this->termId,
            ]);
        }
    }
}
