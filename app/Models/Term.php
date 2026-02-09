<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'name',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the session this term belongs to.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Scope to get the currently active term.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if migration to the next term is valid.
     */
    public function canMigrateTo(Term $nextTerm): bool
    {
        // First Term → Second Term (same session)
        if ($this->order === 1 && $nextTerm->order === 2 && $this->session_id === $nextTerm->session_id) {
            return true;
        }

        // Second Term → Third Term (same session)
        if ($this->order === 2 && $nextTerm->order === 3 && $this->session_id === $nextTerm->session_id) {
            return true;
        }

        // Third Term → First Term (new session)
        if ($this->order === 3 && $nextTerm->order === 1 && $this->session_id !== $nextTerm->session_id) {
            return true;
        }

        return false;
    }

    /**
     * Migrate to the next term with validation and logging.
     */
    public function migrate(?string $notes = null): Term
    {
        return DB::transaction(function () use ($notes) {
            $currentSession = $this->session;
            
            // Determine next term
            if ($this->order === 3) {
                // Create new session for next academic year
                $newSession = Session::createWithTerms($currentSession->end_year);
                $nextTerm = $newSession->terms()->where('order', 1)->first();
                
                // Activate new session
                $newSession->activate();
            } else {
                // Get next term in current session
                $nextTerm = $this->session->terms()
                    ->where('order', $this->order + 1)
                    ->first();
            }

            // Validate transition
            if (!$this->canMigrateTo($nextTerm)) {
                throw new \Exception('Invalid term migration. You must follow the academic sequence.');
            }

            // Deactivate current term
            $this->update(['is_active' => false]);

            // Activate next term
            $nextTerm->update(['is_active' => true]);

            // Log the migration
            TermMigrationLog::create([
                'user_id' => auth()->id(),
                'from_session_id' => $this->session_id,
                'from_term_id' => $this->id,
                'to_session_id' => $nextTerm->session_id,
                'to_term_id' => $nextTerm->id,
                'notes' => $notes,
            ]);

            return $nextTerm->fresh(['session']);
        });
    }

    /**
     * Get the next term in sequence.
     */
    public function getNextTermAttribute(): ?Term
    {
        if ($this->order === 3) {
            // Would be first term of next session (if it exists)
            $nextSession = Session::where('start_year', $this->session->end_year)->first();
            return $nextSession?->terms()->where('order', 1)->first();
        }

        return $this->session->terms()->where('order', $this->order + 1)->first();
    }
}
