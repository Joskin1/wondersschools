<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log entry for tenant provisioning events.
 *
 * Stored on the landlord connection so that provisioning failures
 * (which may leave the tenant DB in an unusable state) are always
 * queryable from the central database.
 */
class TenantProvisionLog extends Model
{
    protected $connection = 'landlord';

    /**
     * Disable Eloquent's automatic updated_at management.
     * This table only has created_at (append-only audit log).
     */
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'initiated_by',
        'event_type',
        'status',
        'message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
