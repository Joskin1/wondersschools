<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant;

return [
    'tenant_model' => \App\Models\Central\School::class,
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,
    'domain_model' => \App\Models\Central\Domain::class,
    'central_domains' => array_filter(array_map('trim', explode(',', env('CENTRAL_DOMAINS', 'wonders.test')))),
    
    'bootstrappers' => array_values(array_filter([
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Skip Redis bootstrapper when disabled (e.g. test env without Redis)
        env('TENANCY_REDIS_BOOTSTRAPPER', true)
            ? Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class
            : null,
    ])),

    'database' => [
        'central_connection' => 'central',
        'template_tenant_connection' => null,
        'prefix' => env('TENANT_DB_PREFIX', 'tenant_'),
        'suffix' => '',
        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => App\TenantDatabaseManagers\PrivilegedMySQLDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [
            'default',
        ],
    ],

    'features' => [
        Stancl\Tenancy\Features\UserImpersonation::class,
        Stancl\Tenancy\Features\TelescopeTags::class,
        Stancl\Tenancy\Features\UniversalRoutes::class,
        Stancl\Tenancy\Features\TenantConfig::class,
        Stancl\Tenancy\Features\CrossDomainRedirect::class,
        Stancl\Tenancy\Features\ViteBundler::class,
    ],

    'routes' => [
        'include' => [],
    ],

    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],
];
