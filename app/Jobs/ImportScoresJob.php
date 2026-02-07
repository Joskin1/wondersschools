<?php

namespace App\Jobs;

use App\Services\ScoreImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportScoresJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public int $classroomId,
        public string $session,
        public int $term,
        public ?int $userId = null
    ) {
        $this->onQueue('imports');
    }

    /**
     * Execute the job.
     */
    public function handle(ScoreImportService $importService): void
    {
        try {
            Log::info('Starting score import', [
                'classroom_id' => $this->classroomId,
                'session' => $this->session,
                'term' => $this->term,
                'rows_count' => count($this->data),
            ]);

            // Validate file structure
            $validation = $importService->validateImportFile(
                $this->data,
                $this->classroomId,
                $this->session,
                $this->term
            );

            if (!$validation['valid']) {
                Log::error('Import validation failed', [
                    'message' => $validation['message'],
                ]);
                throw new \Exception($validation['message']);
            }

            // Import scores
            $result = $importService->importScores(
                $this->data,
                $this->classroomId,
                $this->session,
                $this->term
            );

            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Import failed');
            }

            Log::info('Score import completed', [
                'classroom_id' => $this->classroomId,
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'errors_count' => count($result['errors']),
            ]);

        } catch (\Exception $e) {
            Log::error('Score import failed', [
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
        Log::error('ImportScoresJob failed permanently', [
            'classroom_id' => $this->classroomId,
            'session' => $this->session,
            'term' => $this->term,
            'error' => $exception->getMessage(),
        ]);
    }
}
