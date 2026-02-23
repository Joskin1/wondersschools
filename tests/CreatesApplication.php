<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Register tenant migration path so migrate:fresh picks it up.
        // All domain-level tables live in database/migrations/tenant/;
        // landlord migrations (with $connection='landlord') stay in database/migrations/.
        if ($app->environment('testing')) {
            $app->make('migrator')->path(database_path('migrations/tenant'));
        }

        return $app;
    }
}
