<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    |
    | Domains that should NOT trigger tenant resolution. These are domains
    | where the central/sudo panel is accessible. Requests to these domains
    | will use the central database connection.
    |
    */

    'central_domains' => array_filter(array_map('trim', explode(',', env('CENTRAL_DOMAINS', 'wonders.test')))),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Prefix
    |--------------------------------------------------------------------------
    |
    | When provisioning a new tenant, the database name will be prefixed
    | with this value to avoid naming collisions.
    |
    */

    'database_prefix' => env('TENANT_DB_PREFIX', 'tenant_'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Host
    |--------------------------------------------------------------------------
    |
    | The MySQL host used for creating tenant databases. This should be the
    | same host where the central database resides, or a dedicated DB server.
    |
    */

    'database_host' => env('TENANT_DB_HOST', env('DB_HOST', '127.0.0.1')),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Port
    |--------------------------------------------------------------------------
    */

    'database_port' => env('TENANT_DB_PORT', env('DB_PORT', '3306')),

    /*
    |--------------------------------------------------------------------------
    | Admin Provisioner
    |--------------------------------------------------------------------------
    |
    | The MySQL credentials used to CREATE databases and users for tenants.
    | This user must have GRANT privileges.
    |
    */

    'admin_username' => env('TENANT_ADMIN_USERNAME', env('DB_USERNAME', 'root')),
    'admin_password' => env('TENANT_ADMIN_PASSWORD', env('DB_PASSWORD', '')),

    /*
    |--------------------------------------------------------------------------
    | Migration Paths
    |--------------------------------------------------------------------------
    */

    'central_migration_path' => database_path('migrations/central'),
    'tenant_migration_path' => database_path('migrations/tenant'),

    /*
    |--------------------------------------------------------------------------
    | Default Tenant Admin
    |--------------------------------------------------------------------------
    |
    | When provisioning a new school, a default admin user is created
    | with these credentials. The password should be changed after first login.
    |
    */

    'default_admin_name' => env('TENANT_DEFAULT_ADMIN_NAME', 'School Admin'),
    'default_admin_email' => env('TENANT_DEFAULT_ADMIN_EMAIL', 'admin@school.com'),

];
