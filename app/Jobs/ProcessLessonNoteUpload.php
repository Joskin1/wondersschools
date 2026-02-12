<?php

namespace App\Jobs;

use App\Models\LessonNote;
use App\Models\LessonNoteVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessLessonNoteUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [10, 30, 60]; // Exponential backoff: 10s, 30s, 60s
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $lessonNoteId,
        public string $filePath,
        public string $fileName,
        public int $uploadedBy
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lessonNote = LessonNote::findOrFail($this->lessonNoteId);

        try {
            // File was uploaded by Filament to the 'public' disk
            $sourceDisk = Storage::disk('public');
            $destinationDisk = Storage::disk('lesson_notes');

            if (!$sourceDisk->exists($this->filePath)) {
                Log::error("File not found on public disk: {$this->filePath}");
                throw new \Exception("File not found in storage");
            }

            // Get file metadata from source
            $fileContents = $sourceDisk->get($this->filePath);
            $fileSize = $sourceDisk->size($this->filePath);
            $fileHash = hash('sha256', $fileContents);
            $mimeType = $sourceDisk->mimeType($this->filePath);

            // Validate MIME type
            $allowedMimes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!in_array($mimeType, $allowedMimes)) {
                Log::error("Invalid MIME type: {$mimeType}");
                throw new \Exception("Only PDF, DOC, and DOCX files are allowed");
            }

            // Validate file size (max 10MB)
            $maxSize = 10 * 1024 * 1024;
            if ($fileSize > $maxSize) {
                Log::error("File too large: {$fileSize} bytes");
                throw new \Exception("File size exceeds 10MB limit");
            }

            // Virus scan (mock implementation - in production use ClamAV or VirusTotal)
            $virusScanStatus = $this->performVirusScan($fileContents);
            if ($virusScanStatus !== 'clean') {
                throw new \Exception('Virus detected');
            }

            // Validate file extension
            $extension = pathinfo($this->fileName, PATHINFO_EXTENSION);
            if (!in_array(strtolower($extension), ['pdf', 'doc', 'docx'])) {
                throw new \Exception('Only PDF, DOC, and DOCX files are allowed');
            }

            // Check for executable files
            if (str_starts_with($fileContents, 'MZ')) {
                throw new \Exception('Invalid file type');
            }

            // Build permanent storage path on lesson_notes disk
            $permanentPath = LessonNoteVersion::buildStoragePath(
                $lessonNote->session_id,
                $lessonNote->term_id,
                $lessonNote->week_number,
                $this->uploadedBy,
                $fileHash,
                strtolower($extension)
            );

            // Move file from public disk to lesson_notes disk
            $destinationDisk->put($permanentPath, $fileContents);

            // Extract metadata
            $metadata = $this->extractMetadata($fileContents, $mimeType);

            // Check for duplicates
            $isDuplicate = LessonNoteVersion::where('file_hash', $fileHash)
                ->where('lesson_note_id', '!=', $this->lessonNoteId)
                ->exists();

            // Create version record
            $version = LessonNoteVersion::create([
                'lesson_note_id' => $this->lessonNoteId,
                'file_path' => $permanentPath,
                'file_name' => $this->fileName,
                'file_size' => $fileSize,
                'file_hash' => $fileHash,
                'uploaded_by' => $this->uploadedBy,
                'status' => 'pending',
                'virus_scan_status' => $virusScanStatus,
                'mime_type' => $mimeType,
                'is_duplicate' => $isDuplicate,
                'metadata' => $metadata,
                'file_modified_at' => now(),
                'original_filename' => $this->fileName,
            ]);

            // Update lesson note with latest version
            $lessonNote->update([
                'latest_version_id' => $version->id,
                'status' => 'pending',
            ]);

            // Clean up temp file from public disk
            $sourceDisk->delete($this->filePath);

            // Dispatch notification job
            NotifyAdminOfNewSubmission::dispatch($this->lessonNoteId);

            // Log the action
            LogLessonNoteAction::dispatch(
                $this->lessonNoteId,
                'upload',
                $this->uploadedBy,
                "Uploaded new version: {$this->fileName}"
            );

            Log::info("Lesson note upload processed successfully", [
                'lesson_note_id' => $this->lessonNoteId,
                'version_id' => $version->id,
                'file_hash' => $fileHash,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process lesson note upload", [
                'lesson_note_id' => $this->lessonNoteId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Lesson note upload job failed permanently", [
            'lesson_note_id' => $this->lessonNoteId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Perform virus scan on file contents.
     */
    private function performVirusScan(string $contents): string
    {
        // Mock virus scan - in production, integrate with ClamAV or VirusTotal
        // Check for EICAR test pattern
        if (str_contains($contents, 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE')) {
            return 'infected';
        }
        
        return 'clean';
    }

    /**
     * Extract metadata from file.
     */
    private function extractMetadata(string $contents, string $mimeType): array
    {
        $metadata = [];
        
        if ($mimeType === 'application/pdf') {
            // Mock PDF metadata extraction
            // In production, use a library like smalot/pdfparser
            $metadata['page_count'] = 1; // Mock value
            
            // Try to extract author
            if (preg_match('/\/Author \(([^)]+)\)/', $contents, $matches)) {
                $metadata['author'] = $matches[1];
            }
        }
        
        return $metadata;
    }

    /**
     * Generate thumbnail for PDF.
     */
    private function generateThumbnail(string $filePath): ?string
    {
        // Mock thumbnail generation
        // In production, use Imagick or similar
        $thumbnailPath = str_replace('.pdf', '_thumb.jpg', $filePath);
        
        try {
            Storage::disk('thumbnails')->put($thumbnailPath, 'mock thumbnail content');
            return $thumbnailPath;
        } catch (\Exception $e) {
            Log::warning("Failed to generate thumbnail: {$e->getMessage()}");
            return null;
        }
    }
}
