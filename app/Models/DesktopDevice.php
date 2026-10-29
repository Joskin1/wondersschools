<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DesktopDevice extends Model
{
    use HasFactory;

    protected $table = 'desktop_devices';

    protected $fillable = [
        'device_name',
        'device_token_hash',
        'device_token_prefix',
        'paired_by',
        'last_ip',
        'last_synced_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * User who paired this device.
     */
    public function pairedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paired_by');
    }

    /**
     * Staff attendance records registered through this hardware kiosk.
     */
    public function staffAttendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class, 'recorded_by_device_id');
    }

    /**
     * Generate a new device token pair with prefix and SHA-256 hash.
     *
     * @return array{plain: string, hash: string, prefix: string}
     */
    public static function generateToken(): array
    {
        $random = Str::random(40);
        $plain = "wnd_dev_{$random}";
        $hash = hash('sha256', $plain);
        $prefix = substr($plain, 0, 14) . '...';

        return [
            'plain' => $plain,
            'hash' => $hash,
            'prefix' => $prefix,
        ];
    }

    /**
     * Locate an active device by plain bearer token.
     */
    public static function findByBearerToken(?string $bearerToken): ?self
    {
        if (empty($bearerToken)) {
            return null;
        }

        $hash = hash('sha256', trim($bearerToken));

        return static::query()
            ->where('device_token_hash', $hash)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Touch device sync stats.
     */
    public function recordSync(?string $ip = null): void
    {
        $this->forceFill([
            'last_synced_at' => now(),
            'last_ip' => $ip ?? request()->ip(),
        ])->saveQuietly();
    }
}
