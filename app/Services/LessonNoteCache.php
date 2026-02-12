<?php

namespace App\Services;

use App\Models\SubmissionWindow;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\Cache;

class LessonNoteCache
{
    /**
     * Cache TTL in minutes.
     */
    private const CACHE_TTL = 15;

    /**
     * Get the active submission window for a specific week (cached).
     * 
     * @param int $sessionId
     * @param int $termId
     * @param int $weekNumber
     * @return SubmissionWindow|null
     */
    public function getActiveWindow(int $sessionId, int $termId, int $weekNumber): ?SubmissionWindow
    {
        $cacheKey = "submission_window:{$sessionId}:{$termId}:{$weekNumber}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($sessionId, $termId, $weekNumber) {
            return SubmissionWindow::forWeek($sessionId, $termId, $weekNumber)
                ->currentlyOpen()
                ->first();
        });
    }

    /**
     * Get all teacher assignments for a specific teacher (cached).
     * 
     * @param int $teacherId
     * @return \Illuminate\Support\Collection
     */
    public function getTeacherAssignments(int $teacherId): \Illuminate\Support\Collection
    {
        $cacheKey = "teacher_assignments:{$teacherId}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($teacherId) {
            return TeacherSubjectAssignment::forTeacher($teacherId)
                ->active()
                ->with(['subject', 'classroom'])
                ->get();
        });
    }

    /**
     * Invalidate the cache for a specific submission window.
     * 
     * @param int $sessionId
     * @param int $termId
     * @param int $weekNumber
     * @return void
     */
    public function invalidateWindow(int $sessionId, int $termId, int $weekNumber): void
    {
        $cacheKey = "submission_window:{$sessionId}:{$termId}:{$weekNumber}";
        Cache::forget($cacheKey);
    }

    /**
     * Invalidate the cache for a teacher's assignments.
     * 
     * @param int $teacherId
     * @return void
     */
    public function invalidateTeacherAssignments(int $teacherId): void
    {
        $cacheKey = "teacher_assignments:{$teacherId}";
        Cache::forget($cacheKey);
    }

    /**
     * Clear all lesson note related caches.
     * 
     * @return void
     */
    public function clearAll(): void
    {
        // In production with Redis, you'd use tags
        // For now, we'll just note that this should be implemented
        // Cache::tags(['lesson_notes'])->flush();
    }

    /**
     * Check if upload rate limit is exceeded for a teacher.
     */
    public function checkUploadRateLimit(int $teacherId): bool
    {
        $cacheKey = "rate_limit:upload:{$teacherId}";
        $count = Cache::get($cacheKey, 0);
        return $count < 10; // Max 10 uploads per hour
    }

    /**
     * Increment upload count for rate limiting.
     */
    public function incrementUploadCount(int $teacherId): void
    {
        $cacheKey = "rate_limit:upload:{$teacherId}";
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, now()->addHour());
    }

    /**
     * Check if review rate limit is exceeded for an admin.
     */
    public function checkReviewRateLimit(int $adminId): bool
    {
        $cacheKey = "rate_limit:review:{$adminId}";
        $count = Cache::get($cacheKey, 0);
        return $count < 100; // Max 100 reviews per hour
    }

    /**
     * Increment review count for rate limiting.
     */
    public function incrementReviewCount(int $adminId): void
    {
        $cacheKey = "rate_limit:review:{$adminId}";
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, now()->addHour());
    }

    /**
     * Check if API rate limit is exceeded.
     */
    public function checkApiRateLimit(int $userId, string $endpoint): bool
    {
        $cacheKey = "rate_limit:api:{$userId}:{$endpoint}";
        $count = Cache::get($cacheKey, 0);
        return $count < 50; // Max 50 API calls per minute
    }

    /**
     * Increment API call count for rate limiting.
     */
    public function incrementApiCall(int $userId, string $endpoint): void
    {
        $cacheKey = "rate_limit:api:{$userId}:{$endpoint}";
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, now()->addMinute());
    }

    /**
     * Get active window with database fallback if cache fails.
     */
    public function getActiveWindowWithFallback(int $sessionId, int $termId, int $weekNumber): ?SubmissionWindow
    {
        try {
            return $this->getActiveWindow($sessionId, $termId, $weekNumber);
        } catch (\Exception $e) {
            // Fallback to direct database query
            return SubmissionWindow::forWeek($sessionId, $termId, $weekNumber)
                ->currentlyOpen()
                ->first();
        }
    }
}

