<?php

namespace App\Jobs\Tenancy;

use App\Models\Central\School;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Events\TenantCreated;

class CreateTenantDatabaseUser implements ShouldQueue
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
        $dbName = $tenant->database()->getName();
        
        // Generate credentials if not already set by Filament form
        $username = $tenant->database_username ?? $this->generateDatabaseUsername($tenant->name);
        $password = $tenant->database_password ?? $this->generateDatabasePassword();

        // Sanitize
        $safeUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
        $safeDbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);

        // Use the privileged connection (root) for DDL — the 'central' user lacks CREATE USER privilege
        $adminConnection = 'privileged';

        if (DB::connection($adminConnection)->getDriverName() !== 'mysql') {
            // Avoid running raw MySQL commands in SQLite testing environment
            $tenant->database_username = $username;
            $tenant->database_password = $password;
            $tenant->saveQuietly();
            return;
        }
        
        // Create user
        DB::connection($adminConnection)->statement(
            "CREATE USER IF NOT EXISTS '{$safeUsername}'@'%' IDENTIFIED BY " . DB::connection($adminConnection)->getPdo()->quote($password)
        );

        // Grant limited privileges (no GRANT, DROP DATABASE, etc.)
        DB::connection($adminConnection)->statement(
            "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON `{$safeDbName}`.* TO '{$safeUsername}'@'%'"
        );

        DB::connection($adminConnection)->statement("FLUSH PRIVILEGES");

        // Save back to tenant
        // Use withoutEvents to prevent recursion if saving triggers another TenantEvent
        $tenant->database_username = $safeUsername;
        $tenant->database_password = $password; // Mutator will encrypt it
        $tenant->saveQuietly();
    }

    private function generateDatabaseUsername(?string $name): string
    {
        $slug = Str::slug($name ?? 'tenant', '_');
        $slug = substr($slug, 0, 10);
        return 'tn_' . $slug . '_' . Str::random(4);
    }

    private function generateDatabasePassword(): string
    {
        return Str::random(32);
    }
}
