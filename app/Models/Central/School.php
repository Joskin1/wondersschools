<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class School extends BaseTenant implements TenantWithDatabase
{
    use HasFactory, HasDatabase, HasDomains;

    protected $table = 'schools';

    /**
     * Always use the central database connection.
     */
    protected $connection = 'central';

    /**
     * The custom columns that map to the tenant model.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'database_name',
            'database_username',
            'database_password',
            'status',
        ];
    }

    protected $fillable = [
        'id',
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
}
