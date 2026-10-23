<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MidtermClassTermApproval extends Model
{
    protected $fillable = [
        'classroom_id',
        'session_id',
        'term_id',
        'is_locked',
        'locked_at',
        'locked_by',
        'is_published',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'is_locked'    => 'boolean',
        'is_published' => 'boolean',
        'locked_at'    => 'datetime',
        'published_at' => 'datetime',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
