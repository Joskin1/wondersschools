<?php

namespace App\Services;

use App\Models\Central\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantProvisioner
{
    /**
     * Provision a new tenant: create database, user, run migrations, and seed admin.
     *
     * @throws \Exception on any provisioning failure
     */
    public function provision(School $school): void
    {
        $dbName = $school->database_name;
        $dbUser = $school->database_username;
        $dbPass = $school->database_password;

        Log::info("Provisioning tenant database", ['school_id' => $school->id, 'database' => $dbName]);

        try {
            // 1. Create the database
            $this->createDatabase($dbName);

            // 2. Create the database user with limited privileges
            $this->createDatabaseUser($dbUser, $dbPass, $dbName);

            // 3. Configure the tenant connection and run migrations
            $school->configureTenantConnection();
            $this->runMigrations();

            // 4. Seed the default admin user
            $this->seedDefaultAdmin($school);

            Log::info("Tenant provisioned successfully", ['school_id' => $school->id]);
        } catch (\Exception $e) {
            Log::critical("Tenant provisioning failed", [
                'school_id' => $school->id,
                'database' => $dbName,
                'error' => $e->getMessage(),
            ]);
            // Attempt cleanup on failure
            $this->cleanup($dbName, $dbUser);
            throw $e;
        }
    }

    /**
     * Suspend a school (set status to suspended).
     */
    public function suspend(School $school): void
    {
        $school->update(['status' => 'suspended']);
        Log::warning("Tenant suspended", ['school_id' => $school->id]);
    }

    /**
     * Activate a school (set status to active).
     */
    public function activate(School $school): void
    {
        $school->update(['status' => 'active']);
        Log::info("Tenant activated", ['school_id' => $school->id]);
    }

    /**
     * Delete a tenant: drop database, drop user, delete school record.
     *
     * @throws \Exception on critical failures
     */
    public function delete(School $school): void
    {
        $dbName = $school->database_name;
        $dbUser = $school->database_username;

        Log::warning("Deleting tenant", ['school_id' => $school->id, 'database' => $dbName]);

        try {
            $this->cleanup($dbName, $dbUser);
            $school->domains()->delete();
            $school->delete();
            Log::info("Tenant deleted successfully", ['school_id' => $school->id]);
        } catch (\Exception $e) {
            Log::critical("Tenant deletion failed", [
                'school_id' => $school->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reset the admin password for a tenant.
     */
    public function resetAdminPassword(School $school, string $newPassword): void
    {
        $school->configureTenantConnection();

        $admin = DB::connection('tenant')
            ->table('users')
            ->where('role', 'admin')
            ->first();

        if ($admin) {
            DB::connection('tenant')
                ->table('users')
                ->where('id', $admin->id)
                ->update(['password' => Hash::make($newPassword)]);

            Log::info("Tenant admin password reset", ['school_id' => $school->id]);
        }
    }

    /**
     * Generate a unique database name for a new tenant.
     */
    public static function generateDatabaseName(string $schoolName): string
    {
        $prefix = config('tenancy.database_prefix', 'tenant_');
        $slug = Str::slug($schoolName, '_');
        $slug = substr($slug, 0, 32); // Limit length
        $uniqueId = Str::random(6);

        return $prefix . $slug . '_' . $uniqueId;
    }

    /**
     * Generate a unique database username for a new tenant.
     */
    public static function generateDatabaseUsername(string $schoolName): string
    {
        $slug = Str::slug($schoolName, '_');
        $slug = substr($slug, 0, 10); // MySQL username limit
        $uniqueId = Str::random(4);

        return 'tn_' . $slug . '_' . $uniqueId;
    }

    /**
     * Generate a secure random database password.
     */
    public static function generateDatabasePassword(): string
    {
        return Str::random(32);
    }

    /**
     * Create a new MySQL database.
     */
    private function createDatabase(string $dbName): void
    {
        // Sanitize database name to prevent SQL injection
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);

        $adminConnection = $this->getAdminConnection();
        DB::connection($adminConnection)->statement(
            "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    /**
     * Create a MySQL user with limited privileges on the tenant database.
     */
    private function createDatabaseUser(string $username, string $password, string $dbName): void
    {
        // Sanitize inputs
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);

        $adminConnection = $this->getAdminConnection();

        // Create user
        DB::connection($adminConnection)->statement(
            "CREATE USER IF NOT EXISTS '{$username}'@'%' IDENTIFIED BY " . DB::connection($adminConnection)->getPdo()->quote($password)
        );

        // Grant limited privileges (no GRANT, no DROP DATABASE, no CREATE USER)
        DB::connection($adminConnection)->statement(
            "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON `{$dbName}`.* TO '{$username}'@'%'"
        );

        DB::connection($adminConnection)->statement("FLUSH PRIVILEGES");
    }

    /**
     * Run tenant migrations on the currently configured tenant connection.
     */
    private function runMigrations(): void
    {
        $migrationPath = config('tenancy.tenant_migration_path');

        \Illuminate\Support\Facades\Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => str_replace(base_path() . '/', '', $migrationPath),
            '--force' => true,
        ]);
    }

    /**
     * Seed the default admin user in the tenant database.
     */
    private function seedDefaultAdmin(School $school): void
    {
        DB::connection('tenant')->table('users')->insert([
            'name' => config('tenancy.default_admin_name', 'School Admin'),
            'email' => config('tenancy.default_admin_email', 'admin@school.com'),
            'password' => Hash::make('password'), // Must be changed on first login
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Cleanup: drop database and user on provisioning failure.
     */
    private function cleanup(string $dbName, string $dbUser): void
    {
        try {
            $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
            $dbUser = preg_replace('/[^a-zA-Z0-9_]/', '', $dbUser);

            $adminConnection = $this->getAdminConnection();
            DB::connection($adminConnection)->statement("DROP DATABASE IF EXISTS `{$dbName}`");
            DB::connection($adminConnection)->statement("DROP USER IF EXISTS '{$dbUser}'@'%'");
            DB::connection($adminConnection)->statement("FLUSH PRIVILEGES");
        } catch (\Exception $e) {
            Log::error("Tenant cleanup failed", [
                'database' => $dbName,
                'user' => $dbUser,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get or create an admin DB connection for provisioning operations.
     * Uses the admin credentials from config/tenancy.php which have GRANT privileges.
     */
    private function getAdminConnection(): string
    {
        $connectionName = 'tenant_admin';

        if (!array_key_exists($connectionName, config('database.connections', []))) {
            config(["database.connections.{$connectionName}" => [
                'driver' => 'mysql',
                'host' => config('tenancy.database_host'),
                'port' => config('tenancy.database_port'),
                'database' => '', // No specific database needed for admin operations
                'username' => config('tenancy.admin_username'),
                'password' => config('tenancy.admin_password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ]]);
        }

        return $connectionName;
    }
}
