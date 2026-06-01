<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatus;
use App\Services\TenantBrandingService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\InvalidatesResolverCache;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, InvalidatesResolverCache;

    protected $connection = 'landlord';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'id',
        'name',
        'primary_color',
        'status',
        'last_provisioned_at',
    ];

    /**
     * Promote name, primary_color, status, and last_provisioned_at to real
     * SQL columns so that Filament can sort/search them with standard
     * Eloquent — no JSON extraction needed. All other dynamic attributes
     * continue to live in the JSON `data` column.
     */
    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'primary_color', 'status', 'last_provisioned_at'];
    }

    protected function casts(): array
    {
        return [
            'status'              => TenantStatus::class,
            'last_provisioned_at' => 'datetime',
        ];
    }

    /**
     * Flush and re-warm the host-keyed early-branding cache (used by
     * PanelProviders) whenever this tenant record is saved or deleted
     * from the Sudo panel.
     *
     * The InvalidatesResolverCache trait (booted above) handles the native
     * DomainTenantResolver cache automatically; this hook covers the
     * additional application-level branding cache.
     */
    protected static function booted(): void
    {
        $handler = static function (self $tenant): void {
            try {
                $service = app(TenantBrandingService::class);
                $service->invalidate($tenant);
                $service->warm($tenant);
            } catch (\Throwable) {
                // During testing or early boot the service may not resolve.
                // Branding cache will self-heal on the next request.
            }
        };

        static::saved($handler);
        static::deleting(static function (self $tenant): void {
            try {
                app(TenantBrandingService::class)->invalidate($tenant);
            } catch (\Throwable) {
                // Silent — deleting only needs to invalidate, not warm.
            }
        });
    }

    // ──────────────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────────────

    public function provisionLogs(): HasMany
    {
        return $this->hasMany(TenantProvisionLog::class, 'tenant_id', 'id');
    }
}
