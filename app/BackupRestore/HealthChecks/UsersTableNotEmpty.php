<?php

declare(strict_types=1);

namespace App\BackupRestore\HealthChecks;

use Illuminate\Support\Facades\DB;
use Wnx\LaravelBackupRestore\HealthChecks\HealthCheck;
use Wnx\LaravelBackupRestore\HealthChecks\Result;
use Wnx\LaravelBackupRestore\PendingRestore;

class UsersTableNotEmpty extends HealthCheck
{
    public function run(PendingRestore $pendingRestore): Result
    {
        $result = Result::make($this);
        $connection = DB::connection($pendingRestore->connection);

        $hasUsersTable = $connection->getSchemaBuilder()->hasTable('users');
        if (! $hasUsersTable) {
            return $result->failed('The "users" table does not exist in restored database.');
        }

        $userCount = $connection->table('users')->count();
        if ($userCount === 0) {
            return $result->failed('The "users" table is empty after restore.');
        }

        return $result->ok();
    }
}
