<?php

namespace App\Services;

use App\Models\FrontendContent;
use App\Models\Setting;

class FrontendLibrary
{
    public static function get(string $key, $default = null)
    {
        try {
            return FrontendContent::where('key', $key)->value('value') ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function getSetting(string $key, $default = null)
    {
        try {
            $value = Setting::where('key', $key)->value('value');
            if ($value === null) {
                if ($key === 'school_name') {
                    return config('app.name') ?? $default;
                }
                if (in_array($key, ['primary_color', 'secondary_color', 'accent_color', 'layout_style'])) {
                    return config("app.tenant_{$key}") ?? $default;
                }
            }
            return $value ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }
}
