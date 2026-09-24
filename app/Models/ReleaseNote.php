<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReleaseNote extends Model
{
    protected $fillable = [
        'version',
        'title',
        'category',
        'summary',
        'changes',
        'procedure_guide',
        'published_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'published_at' => 'datetime',
    ];

    public function reads(): HasMany
    {
        return $this->hasMany(ReleaseNoteRead::class);
    }

    /**
     * Check if a specific user has read this release note.
     */
    public function isReadBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->reads()->where('user_id', $user->id)->exists();
    }

    /**
     * Mark this release note as read for a specific user.
     */
    public function markAsReadFor(User $user): void
    {
        ReleaseNoteRead::firstOrCreate(
            [
                'release_note_id' => $this->id,
                'user_id' => $user->id,
            ],
            [
                'read_at' => now(),
            ]
        );
    }

    /**
     * Scope query to published release notes.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Scope query to release notes unread by a user.
     */
    public function scopeUnreadFor(Builder $query, User $user): Builder
    {
        return $query->published()
            ->whereDoesntHave('reads', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            });
    }

    /**
     * Get badge color representation for category.
     */
    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'workflow_change' => 'warning',
            'feature' => 'success',
            'improvement' => 'info',
            'bugfix' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get human-readable label for category.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'workflow_change' => 'Workflow Change',
            'feature' => 'New Feature',
            'improvement' => 'Improvement',
            'bugfix' => 'Bug Fix',
            default => ucfirst(str_replace('_', ' ', $this->category ?? 'General')),
        };
    }
}
