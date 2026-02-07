<?php

namespace App\Jobs;

use App\Models\Result;
use App\Services\ResultCacheService;
use App\Services\ResultComputationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateResultJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $classroomId,
        public string $session,
        public int $term,
        public ?string $settingsName = null,
        public bool $cacheResults = true
    ) {
        $this->onQueue('results');
    }

    /**
     * Execute the job.
     */
    public function handle(
        ResultComputationService $computationService,
        ResultCacheService $cacheService
    ): void {
        try {
            Log::info('Starting result generation', [
                'classroom_id' => $this->classroomId,
                'session' => $this->session,
                'term' => $this->term,
            ]);

            // Compute results for the classroom
            $results = $computationService->computeResults(
                $this->classroomId,
                $this->session,
                $this->term,
                $this->settingsName
            );

            // Cache results if enabled
            if ($this->cacheResults) {
                foreach ($results as $result) {
                    $cacheService->cacheResult($result);
                }
            }

            Log::info('Result generation completed', [
                'classroom_id' => $this->classroomId,
                'results_count' => $results->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Result generation failed', [
                'classroom_id' => $this->classroomId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateResultJob failed permanently', [
            'classroom_id' => $this->classroomId,
            'session' => $this->session,
            'term' => $this->term,
            'error' => $exception->getMessage(),
        ]);
    }
}
