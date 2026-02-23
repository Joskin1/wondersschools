<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Loads all tenant settings in ONE query per request.
 * Registered as a singleton so the DB is hit only once regardless
 * of how many views, components, or composers consume it.
 *
 * JSON-valued keys (arrays/objects stored as JSON strings) are
 * automatically decoded on read, so callers always get native PHP types.
 */
class FrontendContentService
{
    private ?array $settings = null;

    /**
     * Return a single setting value, decoded if JSON.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        // Auto-decode JSON arrays/objects stored in the value column
        if (is_string($value)) {
            $trimmed = trim($value);
            if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
        }

        return $value;
    }

    /**
     * Return all settings as a key→value array (raw, not decoded).
     * Loaded lazily and cached for the lifetime of this singleton instance.
     */
    public function all(): array
    {
        return $this->settings ??= Setting::pluck('value', 'key')->toArray();
    }

    /**
     * Magic property access: $site->hero_heading
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Flush the cached settings (useful after saving from admin panel).
     */
    public function flush(): void
    {
        $this->settings = null;
    }
}
