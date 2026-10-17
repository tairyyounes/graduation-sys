<?php

declare(strict_types=1);

use App\BackupRestore\HealthChecks\AdminUserExists;
use App\BackupRestore\HealthChecks\UsersTableNotEmpty;
use Wnx\LaravelBackupRestore\HealthChecks\Checks\DatabaseHasTables;

return [

    /**
     * Health checks are run after a given backup has been restored.
     * The restore command will fail if any health check fails.
     */
    'health-checks' => [
        DatabaseHasTables::class,
        UsersTableNotEmpty::class,
        AdminUserExists::class,
    ],
];
