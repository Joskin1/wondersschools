<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     *
     * Only seeds the *central* database (users, schools, domains).
     * Tenant-specific data (sessions, classrooms, subjects, lesson notes, etc.)
     * is seeded per-tenant by App\Jobs\Tenancy\SeedTenantAdmin when a school is
     * created, or by running `php artisan tenants:seed` for existing tenants.
     */
    public function run(): void
    {
        // Sudo super-admin (central database only)
        $this->call(SudoUserSeeder::class);
    }
}
