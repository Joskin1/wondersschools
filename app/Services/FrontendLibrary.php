<?php

namespace App\Services;

use App\Models\FrontendContent;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class FrontendLibrary
{
    /**
     * Per-request static in-memory store, keyed by tenant ID.
     */
    private static array $contents = [];
    private static array $settings = [];

    /**
     * Get the active tenant identifier for caching and scoping.
     */
    protected static function getTenantKey(): string
    {
        if (function_exists('tenant') && tenant('id')) {
            return (string) tenant('id');
        }

        return 'landlord';
    }

    /**
     * Load and memoize all frontend contents for the active tenant in a single query/cache call.
     */
    protected static function loadAllContents(): array
    {
        $tenantKey = self::getTenantKey();

        if (isset(self::$contents[$tenantKey])) {
            return self::$contents[$tenantKey];
        }

        try {
            self::$contents[$tenantKey] = Cache::rememberForever("tenant_{$tenantKey}_frontend_contents", function () {
                return FrontendContent::query()->pluck('value', 'key')->toArray();
            });
        } catch (\Throwable) {
            self::$contents[$tenantKey] = [];
        }

        return self::$contents[$tenantKey] ?? [];
    }

    /**
     * Load and memoize all settings for the active tenant in a single query/cache call.
     */
    protected static function loadAllSettings(): array
    {
        $tenantKey = self::getTenantKey();

        if (isset(self::$settings[$tenantKey])) {
            return self::$settings[$tenantKey];
        }

        try {
            self::$settings[$tenantKey] = Cache::rememberForever("tenant_{$tenantKey}_settings", function () {
                return Setting::query()->pluck('value', 'key')->toArray();
            });
        } catch (\Throwable) {
            self::$settings[$tenantKey] = [];
        }

        return self::$settings[$tenantKey] ?? [];
    }

    /**
     * Retrieve a frontend content string or value by key, falling back to default when unseeded.
     */
    public static function get(string $key, $default = null)
    {
        $all = self::loadAllContents();

        if (array_key_exists($key, $all)) {
            return $all[$key] ?? $default;
        }

        return $default;
    }

    /**
     * Retrieve and decode a JSON array/repeater content value, falling back to default when unseeded.
     */
    public static function getJson(string $key, array $default = []): array
    {
        $all = self::loadAllContents();

        if (! array_key_exists($key, $all)) {
            return $default;
        }

        $value = $all[$key];

        if ($value === null) {
            return $default;
        }

        if ($value === '' || $value === '[]') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Retrieve a setting value by key, falling back to app configuration and default.
     */
    public static function getSetting(string $key, $default = null)
    {
        $all = self::loadAllSettings();

        if (array_key_exists($key, $all) && $all[$key] !== null) {
            return $all[$key];
        }

        if ($key === 'school_name') {
            return config('app.name') ?? $default;
        }

        if (in_array($key, ['primary_color', 'secondary_color', 'accent_color', 'layout_style'])) {
            return config("app.tenant_{$key}") ?? $default;
        }

        return $default;
    }

    /**
     * Resolve an image path to a full public URL, supporting uploaded files, external URLs, and fallbacks.
     *
     * @param string|array|null $path
     */
    public static function imageUrl($path, ?string $default = null): ?string
    {
        if (empty($path)) {
            return $default;
        }

        if (is_array($path)) {
            $first = reset($path);
            return is_string($first) ? self::imageUrl($first, $default) : $default;
        }

        if (! is_string($path)) {
            return $default;
        }

        // If it's already a full URL or data URI
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//') || str_starts_with($path, 'data:')) {
            return $path;
        }

        // If stored as a JSON array or object string by Filament FileUpload
        if (str_starts_with($path, '[') || str_starts_with($path, '{')) {
            $decoded = json_decode($path, true);
            if (is_array($decoded) && ! empty($decoded)) {
                $first = reset($decoded);
                if (is_string($first)) {
                    return self::imageUrl($first, $default);
                }
            }
        }

        try {
            $disk = config('filesystems.upload_disk', 'public');
            return \Illuminate\Support\Facades\Storage::disk($disk)->url($path);
        } catch (\Throwable) {
            return asset('storage/' . ltrim($path, '/'));
        }
    }

    /**
     * Clear all in-memory and persistent cache for a tenant (or all tenants).
     */
    public static function flush(?string $tenantId = null): void
    {
        if ($tenantId !== null) {
            Cache::forget("tenant_{$tenantId}_frontend_contents");
            Cache::forget("tenant_{$tenantId}_settings");
            unset(self::$contents[$tenantId], self::$settings[$tenantId]);
        } else {
            $tenantKey = self::getTenantKey();
            Cache::forget("tenant_{$tenantKey}_frontend_contents");
            Cache::forget("tenant_{$tenantKey}_settings");
            self::$contents = [];
            self::$settings = [];
        }
    }
}
