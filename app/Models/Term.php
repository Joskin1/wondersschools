<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AcademicSession;

class Term extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'academic_session_id', 'start_date', 'end_date', 'is_current'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    /**
     * Get the allowed next term based on current term name
     */
    public function getAllowedNextTerm(): ?string
    {
        return match($this->name) {
            'First Term' => 'Second Term',
            'Second Term' => 'Third Term',
            'Third Term' => 'First Term',
            default => null,
        };
    }

    /**
     * Check if migration to a specific term is allowed
     */
    public function canMigrateTo(string $termName): bool
    {
        return $this->getAllowedNextTerm() === $termName;
    }

    /**
     * Check if this is the last term of the session
     */
    public function isLastTerm(): bool
    {
        return $this->name === 'Third Term';
    }

    /**
     * Get the term order number
     */
    public function getTermOrder(): int
    {
        return match($this->name) {
            'First Term' => 1,
            'Second Term' => 2,
            'Third Term' => 3,
            default => 0,
        };
    }
}
