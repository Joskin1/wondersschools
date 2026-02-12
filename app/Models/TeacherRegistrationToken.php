<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeacherRegistrationToken extends Model
{
    /**
     * Indicates if the model should have updated_at timestamp.
     *
     * @var bool
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the registration token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a cryptographically secure random token.
     *
     * @return string
     */
    public static function generateToken(): string
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
     * Create a new registration token for a user.
     *
     * @param User $user
     * @return string The raw token (only returned once, for email)
     */
    public static function createForUser(User $user): string
    {
        // Generate raw token
        $rawToken = self::generateToken();

        // Create token record with hashed token
        self::create([
            'user_id' => $user->id,
            'token_hash' => self::hashToken($rawToken),
            'expires_at' => now()->addDays(3), // 3-day expiry
        ]);

        // Return raw token for email (never stored)
        return $rawToken;
    }

    /**
     * Validate a token and return the token record if valid.
     *
     * @param string $token
     * @return self|null
     */
    public static function validate(string $token): ?self
    {
        $hash = self::hashToken($token);

        return self::where('token_hash', $hash)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();
    }

    /**
     * Mark this token as used.
     *
     * @return void
     */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    /**
     * Check if the token has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token has been used.
     *
     * @return bool
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Check if the token is valid (not expired and not used).
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }
}
