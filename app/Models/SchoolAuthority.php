<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolAuthority extends Model
{
    protected $fillable = [
        'name',
        'title',
        'signature_path',
        'signature_top',
        'signature_left',
        'comment_top',
        'comment_left',
        'display_order',
        'school_id',
    ];

    protected $casts = [
        'signature_top' => 'integer',
        'signature_left' => 'integer',
        'comment_top' => 'integer',
        'comment_left' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Get the school this authority belongs to
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get comments for this authority
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ResultComment::class, 'comment_authority_scope_id');
    }

    /**
     * Get full name with title
     */
    public function getFullNameAttribute(): string
    {
        return $this->title ? "{$this->title} {$this->name}" : $this->name;
    }

    /**
     * Get authorities for a school ordered by display_order
     */
    public static function getAuthorities(int $schoolId): array
    {
        return static::where('school_id', $schoolId)
            ->orderBy('display_order')
            ->get()
            ->toArray();
    }
}

