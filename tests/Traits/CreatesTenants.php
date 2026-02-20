<?php

namespace Tests\Traits;

use App\Models\Central\School;

/**
 * Trait for tests that need to simulate a tenant context.
 *
 * Simulates a request resolving to a specific tenant domain.
 */
trait CreatesTenants
{
    protected ?School $tenant = null;

    /**
     * Create a tenant and initialize tenancy context.
     */
    protected function initializeTenancy(): School
    {
        $this->tenant = School::create([
            'name' => 'Test School ' . uniqid(),
            'database_name' => 'test_db_' . uniqid(),
            'database_username' => 'test_' . uniqid(),
            'database_password' => 'test1234',
            'status' => 'active',
        ]);

        tenancy()->initialize($this->tenant);

        return $this->tenant;
    }

    /**
     * End the tenancy context and clean up the database.
     */
    protected function endTenancy(): void
    {
        if ($this->tenant) {
            tenancy()->end();
            $this->tenant->delete(); // Triggers DropDatabase job
            $this->tenant = null;
        }
    }
}
