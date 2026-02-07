<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ResultComment extends Model
{
    protected $fillable = [
        'result_id',
        'comment_authority_scope_id',
        'comment_text',
        'comment_type',
    ];

    protected $casts = [
        'comment_type' => 'string',
    ];

    /**
     * Get the result this comment belongs to
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    /**
     * Get the authority who made this comment
     */
    public function authority(): BelongsTo
    {
        return $this->belongsTo(SchoolAuthority::class, 'comment_authority_scope_id');
    }

    /**
     * Scope to filter by comment type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('comment_type', $type);
    }

    /**
     * Scope to get teacher comments
     */
    public function scopeTeacherComments(Builder $query): Builder
    {
        return $query->where('comment_type', 'teacher');
    }

    /**
     * Scope to get principal comments
     */
    public function scopePrincipalComments(Builder $query): Builder
    {
        return $query->where('comment_type', 'principal');
    }
}
