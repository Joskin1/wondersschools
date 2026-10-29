<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'surname',
        'first_name',
        'other_name',
        'email',
        'password',
        'role',
        'is_primary_admin',
        'admin_designation',
        'allowed_admin_pages',
        'is_active',
        'email_verified_at',
        'registration_completed_at',
        'primary_biometric_template',
        'backup_biometric_template',
        'biometric_enrolled_at',
    ];

    /**
     * Get the user's full name formatted as "Surname, First Name Other Name".
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->other_name]);
        $rest = implode(' ', $parts);

        if ($this->surname && $rest) {
            return "{$this->surname}, {$rest}";
        }

        if ($this->surname || $rest) {
            return $this->surname ?: $rest;
        }

        return (string) ($this->attributes['name'] ?? '');
    }

    /**
     * Backward compatible name accessor.
     */
    public function getNameAttribute(): string
    {
        $parts = array_filter([$this->surname, $this->first_name, $this->other_name]);
        if (! empty($parts)) {
            return implode(' ', $parts);
        }

        return (string) ($this->attributes['name'] ?? '');
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
            'is_primary_admin' => 'boolean',
            'allowed_admin_pages' => 'array',
            'is_active' => 'boolean',
            'registration_completed_at' => 'datetime',
            'biometric_enrolled_at' => 'datetime',
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
     * Check if user is the primary admin (Principal / School Owner).
     * Protected full-access designation within this tenant.
     */
    public function isPrimaryAdmin(): bool
    {
        return $this->isAdmin() && (bool) $this->is_primary_admin;
    }

    /**
     * Check if user has any active admin permissions assigned.
     */
    public function hasAnyAdminPermission(): bool
    {
        $allowed = $this->allowed_admin_pages;

        return is_array($allowed) && !empty($allowed);
    }

    /**
     * Determine if a user can access a specific admin page/resource.
     * Sudo and Principal have automatic unrestricted access to all current and future pages.
     */
    public function canAccessAdminPage(string $pageKey): bool
    {
        if ($this->isSudo() || $this->isPrimaryAdmin()) {
            return true;
        }

        if (!$this->isAdmin() && !$this->isTeacher()) {
            return false;
        }

        $allowed = $this->allowed_admin_pages;
        if (!is_array($allowed) || empty($allowed)) {
            return false;
        }

        if (in_array('*', $allowed, true) || in_array($pageKey, $allowed, true) || in_array("{$pageKey}.*", $allowed, true)) {
            return true;
        }

        // If user has any granular action permission for this resource/page (e.g. students.view, students.create)
        foreach ($allowed as $permission) {
            if (is_string($permission) && str_starts_with($permission, "{$pageKey}.")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a user can perform a specific granular action on a resource.
     * Sudo and Principal have automatic unrestricted access to all current and future actions.
     */
    public function canPerformAdminAction(string $resourceKey, string $action): bool
    {
        if ($this->isSudo() || $this->isPrimaryAdmin()) {
            return true;
        }

        if (!$this->isAdmin() && !$this->isTeacher()) {
            return false;
        }

        $allowed = $this->allowed_admin_pages;
        if (!is_array($allowed) || empty($allowed)) {
            return false;
        }

        // Wildcard or full-resource grant
        if (in_array('*', $allowed, true) || in_array($resourceKey, $allowed, true) || in_array("{$resourceKey}.*", $allowed, true)) {
            return true;
        }

        // Direct action key check
        if (in_array("{$resourceKey}.{$action}", $allowed, true)) {
            return true;
        }

        // Action aliases matching
        $actionAliases = match ($action) {
            'viewAny', 'view' => ['view', 'viewAny', 'read'],
            'create' => ['create', 'add'],
            'update', 'edit' => ['update', 'edit'],
            'delete', 'deleteAny', 'forceDelete' => ['delete', 'deleteAny'],
            'restore' => ['restore', 'update', 'edit'],
            default => [$action],
        };

        foreach ($actionAliases as $alias) {
            if (in_array("{$resourceKey}.{$alias}", $allowed, true)) {
                return true;
            }
        }

        return false;
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
        if ($this->isSudo() || $this->isPrimaryAdmin()) {
            return true;
        }

        if (!$this->isAdmin()) {
            return false;
        }

        return $this->canAccessAdminPage('academic_sessions')
            || $this->canAccessAdminPage('terms')
            || $this->canAccessAdminPage('classrooms')
            || $this->canAccessAdminPage('enter_scores')
            || $this->canAccessAdminPage('process_class_results');
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Under no circumstances may an impersonated session access the central sudo panel
        if (\STS\FilamentImpersonate\Facades\Impersonation::isImpersonating() && $panel->getId() === 'sudo') {
            return false;
        }

        // Allow impersonated users to access the admin panel so the
        // "Leave Impersonation" banner remains reachable when impersonating from admin
        if (\STS\FilamentImpersonate\Facades\Impersonation::isImpersonating() && $panel->getId() === 'admin') {
            return true;
        }

        return match ($panel->getId()) {
            'sudo' => $this->role === 'sudo',
            'admin' => in_array($this->role, ['sudo', 'admin']) || ($this->isTeacher() && $this->hasAnyAdminPermission()),
            'teacher' => (in_array($this->role, ['sudo', 'admin']) || $this->role === 'teacher') && $this->isActive(),
            'student' => $this->role === 'student' && $this->isActive(),
            default => false,
        };
    }

    /**
     * Get the teacher record for this user.
     */
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Get the Filament avatar URL for this user.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        $avatarPath = null;

        if ($this->isStudent() && $this->student) {
            $avatarPath = $this->student->profile_picture;
        } elseif ($this->isTeacher() && $this->teacher) {
            $avatarPath = $this->teacher->profile_picture;
        }

        if ($avatarPath) {
            return Storage::disk(config('filesystems.upload_disk', 'public'))->url($avatarPath);
        }

        return null;
    }

    /**
     * Get the linked student record for this user.
     */
    public function student()
    {
        return $this->hasOne(Student::class);
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
        return (bool) $this->is_active;
    }

    /**
     * Check if the user has completed registration.
     */
    public function hasCompletedRegistration(): bool
    {
        return $this->registration_completed_at !== null;
    }

    /**
     * Get the class teacher assignments for this user.
     */
    public function classTeacherAssignments()
    {
        return $this->hasMany(ClassTeacherAssignment::class, 'teacher_id');
    }

    /**
     * Get the teacher subject assignments for this user.
     */
    public function teacherSubjectAssignments()
    {
        return $this->hasMany(TeacherSubjectAssignment::class, 'teacher_id');
    }

    /**
     * Scope a query to only include users eligible to be assigned as teachers (active teachers + active admins).
     */
    public function scopeAssignableTeachers($query)
    {
        return $query->where('is_active', true)
            ->whereIn('role', ['teacher', 'admin']);
    }

    /**
     * Check if user is actively assigned as a class teacher or subject teacher.
     */
    public function isAssignedTeacher(): bool
    {
        return $this->classTeacherAssignments()->exists()
            || $this->teacherSubjectAssignments()->exists();
    }

    /**
     * Check if user is a teacher or an admin who has teaching duties.
     */
    public function isTeachingStaff(): bool
    {
        return $this->isTeacher() || $this->isAssignedTeacher();
    }

    /**
     * Check if the user can be assigned to classes/subjects.
     * Active teachers who have completed registration or active administrators can be assigned.
     */
    public function canBeAssigned(): bool
    {
        if ($this->isAdmin()) {
            return $this->isActive();
        }

        return $this->isTeacher() && $this->isActive() && $this->hasCompletedRegistration();
    }

    /**
     * Determine if the user can impersonate another user.
     */
    public function canImpersonate(): bool
    {
        return $this->isSudo() || $this->isAdmin();
    }

    /**
     * Determine if the user can be impersonated.
     */
    public function canBeImpersonated(): bool
    {
        return !$this->isSudo() && $this->isActive();
    }

    /**
     * Determine if this user has registered biometrics.
     */
    public function isBiometricEnrolled(): bool
    {
        return ! empty($this->primary_biometric_template);
    }

    /**
     * Clear and reset biometric templates for re-enrollment.
     */
    public function clearBiometrics(): void
    {
        $this->forceFill([
            'primary_biometric_template' => null,
            'backup_biometric_template' => null,
            'biometric_enrolled_at' => null,
        ])->save();
    }
}

