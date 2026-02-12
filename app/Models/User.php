<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'registration_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'registration_completed_at' => 'datetime',
        ];
    }

    /**
     * Check if user is a sudo user (developer/system owner).
     */
    public function isSudo(): bool
    {
        return $this->role === 'sudo';
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Check if user is a student.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if user can manage academic sessions and terms.
     */
    public function canManageAcademics(): bool
    {
        return in_array($this->role, ['sudo', 'admin']);
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => in_array($this->role, ['sudo', 'admin']),
            'teacher' => $this->role === 'teacher' && $this->isActive(),
            default => false,
        };
    }

    /**
     * Get the teacher profile for this user.
     */
    public function teacherProfile()
    {
        return $this->hasOne(TeacherProfile::class);
    }

    /**
     * Get the registration tokens for this user.
     */
    public function registrationTokens()
    {
        return $this->hasMany(TeacherRegistrationToken::class);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include teachers.
     */
    public function scopeTeachers($query)
    {
        return $query->where('role', 'teacher');
    }

    /**
     * Scope a query to only include active teachers.
     */
    public function scopeActiveTeachers($query)
    {
        return $query->teachers()->active();
    }

    /**
     * Scope a query to only include teachers pending registration.
     */
    public function scopePendingRegistration($query)
    {
        return $query->teachers()
            ->where('is_active', false)
            ->whereNull('registration_completed_at');
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if the user has completed registration.
     */
    public function hasCompletedRegistration(): bool
    {
        return $this->registration_completed_at !== null;
    }

    /**
     * Check if the user can be assigned to classes/subjects.
     * Only active teachers who have completed registration can be assigned.
     */
    public function canBeAssigned(): bool
    {
        return $this->isTeacher() && $this->isActive() && $this->hasCompletedRegistration();
    }
}
