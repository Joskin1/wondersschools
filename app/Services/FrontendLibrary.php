<?php

namespace App\Services;

use App\Models\FrontendContent;

class FrontendLibrary
{
    public static function get(string $key, $default = null)
    {
        try {
            return FrontendContent::where('key', $key)->value('value') ?? $default;
        } catch (\Throwable) {
            // Table may not exist in central/landlord context — return the default.
            return $default;
        }
    }
}
