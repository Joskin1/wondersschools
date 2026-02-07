<?php

namespace App\Services;

use App\Models\Result;
use Illuminate\Support\Facades\Cache;

class ResultCacheService
{
    private const CACHE_PREFIX = 'result_';
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Cache a result
     */
    public function cacheResult(Result $result): bool
    {
        $cacheKey = $this->getCacheKey($result->cache_key);
        
        return Cache::put($cacheKey, $result->toArray(), self::CACHE_TTL);
    }

    /**
     * Get cached result
     */
    public function getCachedResult(string $cacheKey): ?array
    {
        return Cache::get($this->getCacheKey($cacheKey));
    }

    /**
     * Navigate results (FIRST/PREV/NEXT/LAST)
     */
    public function navigateResults(string $currentKey, string $direction, int $classroomId, string $session, int $term): ?Result
    {
        $results = Result::where('classroom_id', $classroomId)
            ->where('session', $session)
            ->where('term', $term)
            ->orderBy('position')
            ->get();

        if ($results->isEmpty()) {
            return null;
        }

        return match(strtoupper($direction)) {
            'FIRST' => $results->first(),
            'LAST' => $results->last(),
            'NEXT' => $this->getNextResult($results, $currentKey),
            'PREV' => $this->getPreviousResult($results, $currentKey),
            default => null,
        };
    }

    /**
     * Get next result
     */
    private function getNextResult(mixed $results, string $currentKey): ?Result
    {
        $currentIndex = $results->search(fn($r) => $r->cache_key === $currentKey);
        
        if ($currentIndex === false || $currentIndex >= $results->count() - 1) {
            return null;
        }
        
        return $results->get($currentIndex + 1);
    }

    /**
     * Get previous result
     */
    private function getPreviousResult(mixed $results, string $currentKey): ?Result
    {
        $currentIndex = $results->search(fn($r) => $r->cache_key === $currentKey);
        
        if ($currentIndex === false || $currentIndex <= 0) {
            return null;
        }
        
        return $results->get($currentIndex - 1);
    }

    /**
     * Invalidate result cache
     */
    public function invalidateResult(string $cacheKey): bool
    {
        return Cache::forget($this->getCacheKey($cacheKey));
    }

    /**
     * Invalidate all results for a classroom
     */
    public function invalidateClassroomResults(int $classroomId, string $session, int $term): int
    {
        $results = Result::where('classroom_id', $classroomId)
            ->where('session', $session)
            ->where('term', $term)
            ->get();

        $count = 0;
        foreach ($results as $result) {
            if ($this->invalidateResult($result->cache_key)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get full cache key with prefix
     */
    private function getCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }

    /**
     * Check if result is cached
     */
    public function isCached(string $cacheKey): bool
    {
        return Cache::has($this->getCacheKey($cacheKey));
    }

    /**
     * Get cache TTL
     */
    public function getCacheTTL(): int
    {
        return self::CACHE_TTL;
    }
}
