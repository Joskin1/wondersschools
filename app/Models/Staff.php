<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'surname',
        'first_name',
        'other_name',
        'role',
        'bio',
        'image',
        'order',
    ];

    /**
     * Get the staff member's full name formatted as "Surname, First Name Other Name".
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->other_name]);
        $rest = implode(' ', $parts);

        if ($this->surname && $rest) {
            return "{$this->surname}, {$rest}";
        }

        return $this->surname ?: $rest ?: '';
    }

    /**
     * Backward compatible name accessor.
     */
    public function getNameAttribute(): string
    {
        $parts = array_filter([$this->surname, $this->first_name, $this->other_name]);
        return implode(' ', $parts);
    }

    /**
     * Backward compatible name mutator.
     */
    public function setNameAttribute($value): void
    {
        $parts = preg_split('/\s+/', trim((string) $value));
        $this->attributes['surname'] = $parts[0] ?? '';
        $this->attributes['first_name'] = $parts[1] ?? '';
        $this->attributes['other_name'] = count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : null;
    }
}
