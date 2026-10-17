<?php

declare(strict_types=1);

namespace App\BackupRestore\HealthChecks;

use Illuminate\Support\Facades\DB;
use Wnx\LaravelBackupRestore\HealthChecks\HealthCheck;
use Wnx\LaravelBackupRestore\HealthChecks\Result;
use Wnx\LaravelBackupRestore\PendingRestore;

class AdminUserExists extends HealthCheck
{
    public function run(PendingRestore $pendingRestore): Result
    {
        $result = Result::make($this);
        $connection = DB::connection($pendingRestore->connection);

        $hasUsersTable = $connection->getSchemaBuilder()->hasTable('users');
        if (! $hasUsersTable) {
            return $result->failed('The "users" table does not exist in restored database.');
        }

        $adminExists = $connection->table('users')->where('role', 'admin')->exists();
        if (! $adminExists) {
            return $result->failed('No admin user exists in the restored database.');
        }

        return $result->ok();
    }
}
