<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $table = 'academic_sessions';

    protected $fillable = [
        'name',
        'start_year',
        'end_year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_year' => 'integer',
        'end_year' => 'integer',
    ];

    /**
     * Get all terms for this session.
     */
    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    /**
     * Scope to get the currently active session.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Activate this session and deactivate all others.
     */
    public function activate(): void
    {
        // Deactivate all sessions
        static::query()->update(['is_active' => false]);
        
        // Activate this session
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate this session.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Create a new session with its three terms.
     */
    public static function createWithTerms(int $startYear): self
    {
        $session = static::create([
            'name' => "{$startYear}-" . ($startYear + 1),
            'start_year' => $startYear,
            'end_year' => $startYear + 1,
            'is_active' => false,
        ]);

        // Create three terms
        foreach (['First Term' => 1, 'Second Term' => 2, 'Third Term' => 3] as $name => $order) {
            Term::create([
                'session_id' => $session->id,
                'name' => $name,
                'order' => $order,
                'is_active' => false,
            ]);
        }

        return $session->fresh(['terms']);
    }

    /**
     * Get the active term for this session.
     */
    public function getActiveTermAttribute(): ?Term
    {
        return $this->terms()->where('is_active', true)->first();
    }
}
