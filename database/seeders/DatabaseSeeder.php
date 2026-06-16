<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Models\Domain;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the central (landlord) database and bootstrap one dev tenant.
     *
     * Central DB tables: tenants, domains, tenant_admin_assignments, users,
     * password_reset_tokens, sessions.
     *
     * All per-school data (settings, staff, posts, gallery, lesson notes …)
     * lives in each tenant's own database and is seeded by TenantDatabaseSeeder
     * which is called automatically via `php artisan tenants:seed`.
     */
    public function run(): void
    {
        // ── 1. Sudo user (lives in the central DB) ───────────────────────────
        $this->call(SudoUserSeeder::class);

        // ── 2. Create the development tenant + domain ────────────────────────
        $this->command->info('Creating dev tenant...');

        // Drop leftover tenant DB from a previous migrate:fresh (central tables
        // were wiped but MySQL tenant DBs survive across fresh migrations).
        $prefix    = config('tenancy.database.prefix', 'tenant_');
        $tenantDbName = $prefix . 'wonders';
        DB::connection('landlord')->statement("DROP DATABASE IF EXISTS \"{$tenantDbName}\"");

        /** @var Tenant $tenant */
        $tenant = Tenant::firstOrCreate(
            ['id' => 'wonders'],
            ['name' => 'Wonders Kiddies Foundation Schools']
        );

        Domain::firstOrCreate(
            ['domain' => env('DEV_TENANT_DOMAIN', 'school.wonders.test')],
            ['tenant_id' => $tenant->id]
        );

        // The TenantCreated event automatically dispatches CreateDatabase,
        // MigrateDatabase, and SeedDatabase jobs (queue driver = sync), so
        // no explicit tenants:migrate / tenants:seed calls are needed here.
        $this->command->info("Tenant '{$tenant->id}' created — migrations and seed data applied automatically.");

        $this->command->info('Done! Dev environment ready.');
        $this->command->info("Sudo panel : http://wonders.test/sudo  (joskinjoseph1@gmail.com / password)");
        $this->command->info("Admin panel: http://" . env('DEV_TENANT_DOMAIN', 'school.wonders.test') . "/admin  (admin@wonders.test / password)");
    }
}
