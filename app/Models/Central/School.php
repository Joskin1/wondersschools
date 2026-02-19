<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class School extends Model
{
    use HasFactory;

    /**
     * Always use the central database connection.
     */
    protected $connection = 'central';

    protected $fillable = [
        'name',
        'database_name',
        'database_username',
        'database_password',
        'status',
    ];

    protected $hidden = [
        'database_password',
        'database_username',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'database_password' => 'encrypted',
        ];
    }

    /**
     * Get the domains associated with this school.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Scope a query to only include active schools.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include suspended schools.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Check if the school is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the school is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Get the primary domain for this school.
     */
    public function primaryDomain(): ?Domain
    {
        return $this->domains()->where('is_primary', true)->first();
    }

    /**
     * Configure the tenant database connection for this school.
     * This is used to dynamically switch the DB connection at runtime.
     */
    public function configureTenantConnection(): void
    {
        config([
            'database.connections.tenant.database' => $this->database_name,
            'database.connections.tenant.username' => $this->database_username,
            'database.connections.tenant.password' => $this->database_password,
        ]);

        // Purge any existing tenant connection to force reconnect with new credentials
        \Illuminate\Support\Facades\DB::purge('tenant');
    }
}
