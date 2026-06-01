<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Represents the lifecycle status of a tenant during and after provisioning.
 *
 * pending      → Tenant record created, provisioning job not yet started.
 * creating     → Legacy/backward-compat for tenants created before this enum existed.
 * provisioning → Database creation, migrations, and seeding are in progress.
 * active       → Fully provisioned and operational.
 * failed       → Provisioning encountered an unrecoverable error. Retryable.
 * suspended    → Manually disabled by a Sudo administrator.
 */
enum TenantStatus: string
{
    case Pending      = 'pending';
    case Creating     = 'creating';
    case Provisioning = 'provisioning';
    case Active       = 'active';
    case Failed       = 'failed';
    case Suspended    = 'suspended';

    /**
     * Human-readable label for Filament badges.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending      => 'Pending',
            self::Creating     => 'Creating',
            self::Provisioning => 'Provisioning',
            self::Active       => 'Active',
            self::Failed       => 'Failed',
            self::Suspended    => 'Suspended',
        };
    }

    /**
     * Filament badge color mapping.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending      => 'gray',
            self::Creating     => 'info',
            self::Provisioning => 'info',
            self::Active       => 'success',
            self::Failed       => 'danger',
            self::Suspended    => 'warning',
        };
    }

    /**
     * Whether this status represents a retryable/recoverable state.
     */
    public function isRetryable(): bool
    {
        return $this === self::Failed;
    }

    /**
     * Whether the tenant is fully operational.
     */
    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}
