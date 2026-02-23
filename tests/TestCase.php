<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Include the landlord connection in DB transactions so the in-memory
     * SQLite landlord DB is properly wrapped and rolled back between tests.
     * The RefreshDatabase trait reads this property via property_exists().
     */
    protected array $connectionsToTransact = ['sqlite', 'landlord'];

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent tenancy middleware from running in tests — there is no
        // tenant domain in the test environment (requests go to "localhost").
        $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);
    }
}
