<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    use HasFactory;

    /**
     * Always use the central database connection.
     */
    protected $connection = 'central';

    protected $fillable = [
        'school_id',
        'domain',
        'is_primary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the school that owns this domain.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Find a school by domain name.
     * Uses caching to avoid repeated DB queries within the same request lifecycle.
     */
    public static function findSchoolByDomain(string $domain): ?School
    {
        // Cache within the request lifecycle using a static variable
        static $cache = [];

        if (isset($cache[$domain])) {
            return $cache[$domain];
        }

        $domainRecord = static::with('school')
            ->where('domain', $domain)
            ->first();

        if ($domainRecord && $domainRecord->school) {
            $cache[$domain] = $domainRecord->school;
            return $domainRecord->school;
        }

        return null;
    }
}
