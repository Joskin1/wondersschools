<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Student extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\TeacherScope);
    }

    protected $fillable = ['first_name', 'last_name', 'classroom_id', 'admission_number', 'password', 'is_graduated'];

    protected $hidden = [
        'password',
    ];

    public function scopeGraduated($query)
    {
        return $query->where('is_graduated', true);
    }

    public function scopeNotGraduated($query)
    {
        return $query->where('is_graduated', false);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getNameAttribute(): string
    {
        return $this->getFullNameAttribute();
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Promote student to next classroom
     */
    public function promoteToNextClassroom(): bool
    {
        if ($this->is_graduated) {
            return false;
        }

        $currentClassroom = $this->classroom;
        if (!$currentClassroom) {
            return false;
        }

        // Get next classroom based on current classroom name
        $nextClassroomName = $this->getNextClassroomName($currentClassroom->name);
        
        if (!$nextClassroomName) {
            // Terminal class - mark as graduated
            $this->markAsGraduated();
            return true;
        }

        $nextClassroom = Classroom::where('name', $nextClassroomName)->first();
        if ($nextClassroom) {
            $this->classroom_id = $nextClassroom->id;
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Mark student as graduated
     */
    public function markAsGraduated(): void
    {
        $this->is_graduated = true;
        $this->save();
    }

    /**
     * Get next classroom name based on progression
     */
    private function getNextClassroomName(string $currentName): ?string
    {
        $progression = [
            'Reception' => 'Year 1',
            'Year 1' => 'Year 2',
            'Year 2' => 'Year 3',
            'Year 3' => 'Year 4',
            'Year 4' => 'Year 5',
            'Year 5' => 'Year 6',
            'Year 6' => null, // Terminal class
        ];

        return $progression[$currentName] ?? null;
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
