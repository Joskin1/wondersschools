<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    /**
     * Get the value as an integer (useful for ID fields)
     */
    public static function getInt(string $key): ?int
    {
        $value = static::where('key', $key)->value('value');
        return $value !== null ? (int) $value : null;
    }

    /**
     * Get the value as a string
     */
    public static function getString(string $key): ?string
    {
        return static::where('key', $key)->value('value');
    }
}
