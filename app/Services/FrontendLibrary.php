<?php

namespace App\Services;

use App\Models\FrontendContent;

class FrontendLibrary
{
    public static function get(string $key, $default = null)
    {
        return FrontendContent::where('key', $key)->value('value') ?? $default;
    }
}
