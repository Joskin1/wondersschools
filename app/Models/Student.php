<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'full_name',
        'registration_slug',
        'registration_token',
        'registration_expires_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registration_expires_at' => 'datetime',
        ];
    }

    /**
     * Get the student's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * Get all enrollments for this student.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * Get the current enrollment for the active session.
     */
    public function currentEnrollment()
    {
        return $this->enrollments()
            ->whereHas('session', function (Builder $query) {
                $query->where('is_active', true);
            })
            ->first();
    }

    /**
     * Generate a unique registration slug.
     *
     * @return string
     */
    public static function generateRegistrationSlug(string $fullName): string
    {
        $baseSlug = Str::slug($fullName);
        $randomString = Str::lower(Str::random(5));
        
        return "{$baseSlug}-{$randomString}";
    }

    /**
     * Generate a cryptographically secure random token.
     *
     * @return string
     */
    public static function generateRegistrationToken(): string
    {
        return Str::random(64); // 64 characters, cryptographically secure
    }

    /**
     * Hash a token using SHA-256.
     *
     * @param string $token
     * @return string
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Create registration link for this student.
     *
     * @return string The raw token (only returned once)
     */
    public function createRegistrationLink(): string
    {
        // Generate raw token
        $rawToken = self::generateRegistrationToken();
        
        // Update student with hashed token and slug
        $this->update([
            'registration_slug' => self::generateRegistrationSlug($this->full_name),
            'registration_token' => self::hashToken($rawToken),
            'registration_expires_at' => now()->addDays(3), // 3-day expiry
        ]);
        
        // Return raw token for URL (never stored)
        return $rawToken;
    }

    /**
     * Clear registration link after successful completion.
     *
     * @return void
     */
    public function clearRegistrationLink(): void
    {
        $this->update([
            'registration_slug' => null,
            'registration_token' => null,
            'registration_expires_at' => null,
        ]);
    }

    /**
     * Validate a registration token for a given slug.
     *
     * @param string $slug
     * @param string $token
     * @return self|null
     */
    public static function validateRegistration(string $slug, string $token): ?self
    {
        $hash = self::hashToken($token);
        
        return self::where('registration_slug', $slug)
            ->where('registration_token', $hash)
            ->where('registration_expires_at', '>', now())
            ->where('status', 'pending')
            ->first();
    }

    /**
     * Complete the student registration.
     *
     * @param array $profileData
     * @return void
     */
    public function completeRegistration(array $profileData): void
    {
        // Create or update profile
        $this->profile()->updateOrCreate(
            ['student_id' => $this->id],
            $profileData
        );
        
        // Update status to active
        $this->update(['status' => 'active']);
        
        // Clear registration link
        $this->clearRegistrationLink();
    }

    /**
     * Check if the student is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the student is pending registration.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if registration link has expired.
     *
     * @return bool
     */
    public function hasExpiredRegistration(): bool
    {
        return $this->registration_expires_at && $this->registration_expires_at->isPast();
    }

    /**
     * Scope a query to only include active students.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Scope a query to only include pending students.
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }
}
