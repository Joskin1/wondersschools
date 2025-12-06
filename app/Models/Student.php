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

    public function results()
    {
        return $this->hasMany(Result::class);
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

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName(): string
    {
        return 'admission_number';
    }
}
