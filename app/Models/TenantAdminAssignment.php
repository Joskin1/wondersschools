<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantAdminAssignment extends Model
{
    protected $connection = 'landlord';
    protected $table = 'tenant_admin_assignments';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'role',
        'credentials_sent_at',
    ];

    protected $casts = [
        'credentials_sent_at' => 'datetime',
    ];
}
