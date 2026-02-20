<?php

namespace App\TenantDatabaseManagers;

use Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager;

/**
 * A MySQLDatabaseManager that always uses the 'privileged' DB connection
 * regardless of what Stancl passes via setConnection().
 *
 * The default 'central' connection uses the 'db' user which cannot CREATE
 * or DROP databases/users. The 'privileged' connection uses root (or another
 * user with global DDL privileges).
 */
class PrivilegedMySQLDatabaseManager extends MySQLDatabaseManager
{
    public function setConnection(string $connection): void
    {
        parent::setConnection('privileged');
    }
}
