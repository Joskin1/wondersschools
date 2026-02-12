<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LessonNoteVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_note_id',
        'file_path',
        'file_name',
        'file_size',
        'file_hash',
        'uploaded_by',
        'admin_comment',
        'status',
        'reviewed_by',
        'reviewed_at',
        'virus_scan_status',
        'mime_type',
        'is_duplicate',
        'metadata',
        'file_modified_at',
        'original_filename',
        'cdn_available',
        'thumbnail_path',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'reviewed_at' => 'datetime',
        'is_duplicate' => 'boolean',
        'metadata' => 'array',
        'file_modified_at' => 'datetime',
        'cdn_available' => 'boolean',
    ];

    /**
     * Get the lesson note this version belongs to.
     */
    public function lessonNote(): BelongsTo
    {
        return $this->belongsTo(LessonNote::class);
    }

    /**
     * Get the user who uploaded this version.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the user who reviewed this version.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Generate a signed URL for downloading this file.
     * 
     * @param int $expirationMinutes
     * @return string
     */
    public function getDownloadUrl(int $expirationMinutes = 60): string
    {
        return Storage::disk('lesson_notes')->temporaryUrl(
            $this->file_path,
            now()->addMinutes($expirationMinutes)
        );
    }

    /**
     * Get the file size in human-readable format.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Generate file hash from file contents.
     */
    public static function generateFileHash(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * Build the storage path for a lesson note file.
     */
    public static function buildStoragePath(int $sessionId, int $termId, int $weekNumber, int $teacherId, string $hash, string $extension): string
    {
        return "lesson-notes/{$sessionId}/{$termId}/week-{$weekNumber}/{$teacherId}/{$hash}.{$extension}";
    }

    /**
     * Generate a signed URL for file download with expiration.
     */
    public function getSignedDownloadUrl(float $expirationMinutes = 60): string
    {
        $expiresAt = now()->addMinutes($expirationMinutes);
        $path = $this->file_path;
        
        $signature = hash_hmac('sha256', $path . '|' . $expiresAt->timestamp, config('app.key'));
        
        return url('/storage/lesson-notes/' . $path . '?expires=' . $expiresAt->timestamp . '&signature=' . $signature);
    }

    /**
     * Validate a signed URL.
     */
    public function validateSignedUrl(string $url): bool
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        
        $expires = $params['expires'] ?? null;
        $signature = $params['signature'] ?? null;
        
        if (!$expires || !$signature) {
            throw new \Exception('Invalid signature');
        }
        
        if (now()->timestamp > $expires) {
            throw new \Exception('Signed URL has expired');
        }
        
        $expectedSignature = hash_hmac('sha256', $this->file_path . '|' . $expires, config('app.key'));
        
        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid signature');
        }
        
        return true;
    }

    /**
     * Get file content from storage.
     */
    public function getFileContent(): string
    {
        return Storage::disk('lesson_notes')->get($this->file_path);
    }

    /**
     * Generate CDN URL for file delivery.
     */
    public function getCdnUrl(): string
    {
        $cdnDomain = config('filesystems.cdn_domain', 'cdn.example.com');
        return "https://{$cdnDomain}/" . $this->file_path;
    }

    /**
     * Get file URL with CDN fallback.
     */
    public function getFileUrl(): string
    {
        if ($this->cdn_available) {
            return $this->getCdnUrl();
        }
        
        return Storage::disk('lesson_notes')->url($this->file_path);
    }
}
