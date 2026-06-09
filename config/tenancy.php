<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

return [
    'tenant_model'  => \App\Models\Tenant::class,
    'id_generator'  => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model'  => Domain::class,

    /**
     * The "central" domains — requests to these domains are handled by the
     * central application (sudo panel). Tenant routes are not registered here.
     */
    'central_domains' => array_filter([
        env('SUDO_DOMAIN', 'wonders.test'),
        env('CLOUD_DOMAIN'),  // explicit override (optional)
        parse_url(env('APP_URL', ''), PHP_URL_HOST),  // auto-detect from APP_URL
        '127.0.0.1',
        'localhost',
    ]),

    /**
     * Tenancy bootstrappers run on TenancyInitialized event (in order).
     * DatabaseTenancyBootstrapper must come before ConfigBootstrapper so the
     * tenant DB is switched before we read the `school_name` from settings.
     */
    'bootstrappers' => array_filter([
        (getenv('APP_ENV') === 'testing' || defined('PHPUNIT_COMPOSER_INSTALL')) ? null : Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        App\Tenancy\ConfigBootstrapper::class,
    ]),

    'database' => [
        'central_connection' => 'landlord',

        /**
         * Connection used by the Tenant model itself (landlord DB).
         */
        'landlord_connection' => 'landlord',

        /**
         * Template connection used for creating tenant connections.
         * Tenant DB name: tenant_<slug>
         */
        'template_tenant_connection' => null,

        /**
         * Tenant DB name prefix.
         */
        'prefix' => env('TENANT_DB_PREFIX', 'tenant_'),
        'suffix' => '',

        'managers' => [
            'mysql'    => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb'  => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql'    => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
            'sqlite'   => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
        ],
    ],

    'cache' => [
        'tag' => 'tenancy',
    ],

    'filesystem' => [
        /**
         * These disks are suffixed with the tenant key (e.g. /storage/tenantacme/).
         * Every disk listed here MUST have a root_override.
         */
        'suffix_base'  => 'tenant',

        // Filament and Vite assets live in public/ and are shared across all tenants.
        // Disabling this prevents FilesystemTenancyBootstrapper from overriding the
        // asset() URL to /tenancy/assets/… which breaks Filament panel CSS/JS.
        'asset_helper_tenancy' => false,

        'disks'        => [
            'local',
            'public',
            'lesson_notes',
        ],

        'root_override' => [
            'local'        => '%storage_path%/app/private/',
            'public'       => '%storage_path%/app/public/',
            'lesson_notes' => '%storage_path%/app/private/lesson-notes/',
        ],

        'override_url' => [
            'public' => '/storage',
        ],
    ],

    'redis' => [
        'prefix_base'  => 'tenant',
        'prefixed_connections' => [],
    ],

    'features' => [
        Stancl\Tenancy\Features\UserImpersonation::class,
    ],

    'migration_parameters' => [
        '--path'         => [database_path('migrations/tenant')],
        '--realpath'     => true,
    ],

    'seeder_parameters' => [
        '--class' => Database\Seeders\TenantDatabaseSeeder::class,
    ],
];
