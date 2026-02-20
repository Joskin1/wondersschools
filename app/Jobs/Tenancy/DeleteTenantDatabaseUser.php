<?php

namespace App\Jobs\Tenancy;

use App\Models\Central\School;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteTenantDatabaseUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenant;

    public function __construct(School $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle()
    {
        $tenant = $this->tenant;
        $username = $tenant->database_username;

        if (!$username) {
            return; // No user was recorded
        }

        // Sanitize
        $safeUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $username);

        // Use the privileged connection (root) for DDL — the 'central' user lacks DROP USER privilege
        $adminConnection = 'privileged';

        if (DB::connection($adminConnection)->getDriverName() !== 'mysql') {
            return; // Skip user deletion for non-MySQL environments
        }
        
        try {
            DB::connection($adminConnection)->statement("DROP USER IF EXISTS '{$safeUsername}'@'%'");
            DB::connection($adminConnection)->statement("FLUSH PRIVILEGES");
        } catch (\Exception $e) {
            Log::error("Failed to delete tenant database user", [
                'tenant_id' => $tenant->id,
                'user' => $safeUsername,
                'error' => $e->getMessage()
            ]);
        }
    }
}
