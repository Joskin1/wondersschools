<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermMigrationLog extends Model
{
    use HasFactory;

    protected $table = 'term_migrations_log';

    protected $fillable = [
        'user_id',
        'from_session_id',
        'from_term_id',
        'to_session_id',
        'to_term_id',
        'notes',
    ];

    /**
     * Get the user who performed the migration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the session migrated from.
     */
    public function fromSession(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'from_session_id');
    }

    /**
     * Get the term migrated from.
     */
    public function fromTerm(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'from_term_id');
    }

    /**
     * Get the session migrated to.
     */
    public function toSession(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'to_session_id');
    }

    /**
     * Get the term migrated to.
     */
    public function toTerm(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'to_term_id');
    }
}
