<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $connection = 'landlord';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Only 'id' is a real column on the tenants table.
     * Every other attribute (name, primary_color, …) is stored automatically
     * in the 'data' JSON column by stancl's overridden setAttribute/getAttribute,
     * so they are still accessible as first-class properties ($tenant->name).
     */
    public static function getCustomColumns(): array
    {
        return ['id'];
    }
}
