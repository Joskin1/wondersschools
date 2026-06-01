<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TenantProvisionLog;

/**
 * Convenience wrapper for writing structured entries to the
 * tenant_provision_logs audit trail on the landlord connection.
 *
 * Usage:
 *   ProvisionLogger::log('royal-academy', 'database_creation', 'success');
 *   ProvisionLogger::log('royal-academy', 'migration', 'failed', $e->getMessage());
 */
class ProvisionLogger
{
    /**
     * Write a single audit log entry.
     *
     * @param string      $tenantId    The tenant slug/ID.
     * @param string      $eventType   One of: database_creation, migration, seeding, validation, activation, retry.
     * @param string      $status      One of: started, success, failed.
     * @param string|null $message     Optional human-readable detail or exception message.
     * @param int|null    $initiatedBy Optional user ID of the admin who triggered the action.
     */
    public static function log(
        string  $tenantId,
        string  $eventType,
        string  $status,
        ?string $message = null,
        ?int    $initiatedBy = null,
    ): void {
        try {
            TenantProvisionLog::create([
                'tenant_id'    => $tenantId,
                'initiated_by' => $initiatedBy,
                'event_type'   => $eventType,
                'status'       => $status,
                'message'      => $message ? mb_substr($message, 0, 5000) : null,
                'created_at'   => now(),
            ]);
        } catch (\Throwable) {
            // Never let audit logging break the provisioning pipeline.
            // If the landlord DB is unreachable, the log is silently lost.
            // The primary operation (provisioning) must always take priority.
        }
    }
}
