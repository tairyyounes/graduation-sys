<?php

use App\BackupRestore\HealthChecks\AdminUserExists;
use App\BackupRestore\HealthChecks\UsersTableNotEmpty;
use App\Jobs\RestoreBackupJob;
use App\Models\BackupLog;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Wnx\LaravelBackupRestore\PendingRestore;

beforeEach(function () {
    Storage::fake('backups');
    config(['backup.backup.name' => 'graduation-sys']);
    config(['backup.backup.destination.disks' => ['backups']]);
});

test('non-admin cannot initiate restore endpoint', function () {
    $student = User::factory()->create(['role' => 'student']);

    $response = $this->actingAs($student)->postJson('/admin/backups/restore', [
        'file_name' => 'backup.zip',
        'confirm_phrase' => 'RESTORE',
    ]);

    $response->assertForbidden();
});

test('restore requires typing exact RESTORE confirmation phrase', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Storage::disk('backups')->put('graduation-sys/backup.zip', 'dummy');

    // Missing phrase
    $response1 = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->postJson('/admin/backups/restore', [
            'file_name' => 'backup.zip',
            'confirm_phrase' => '',
        ]);
    $response1->assertStatus(422);
    $response1->assertJsonValidationErrors('confirm_phrase');

    // Lowercase phrase
    $response2 = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->postJson('/admin/backups/restore', [
            'file_name' => 'backup.zip',
            'confirm_phrase' => 'restore',
        ]);
    $response2->assertStatus(422);
    $response2->assertJsonValidationErrors('confirm_phrase');
});

test('restore rejects path traversal in archive filename', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->postJson('/admin/backups/restore', [
            'file_name' => '../../secret.zip',
            'confirm_phrase' => 'RESTORE',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('file_name');
});

test('restore dispatches RestoreBackupJob when valid and authorized', function () {
    Queue::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    Storage::disk('backups')->put('graduation-sys/valid-backup.zip', 'dummy-data');

    $response = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->postJson('/admin/backups/restore', [
            'file_name' => 'valid-backup.zip',
            'disk' => 'backups',
            'confirm_phrase' => 'RESTORE',
        ]);

    $response->assertStatus(202);
    $response->assertJson([
        'status' => 'pending',
    ]);

    $logId = $response->json('log_id');
    expect($logId)->not->toBeNull();

    $log = BackupLog::find($logId);
    expect($log)->not->toBeNull();
    expect($log->type)->toBe('restore');
    expect($log->status)->toBe('pending');
    expect($log->triggered_by)->toBe($admin->id);

    Queue::assertPushed(RestoreBackupJob::class, function ($job) use ($logId) {
        return $job->logId === $logId && $job->disk === 'backups';
    });
});

test('health check UsersTableNotEmpty succeeds when users table has rows and fails when empty', function () {
    User::factory()->create(['role' => 'admin']);

    $pendingRestore = PendingRestore::make(
        disk: 'backups',
        backup: 'dummy.zip',
        connection: 'sqlite',
        backupPassword: null
    );

    $check = new UsersTableNotEmpty();
    $result = $check->run($pendingRestore);

    expect($result->status)->toBe(\Illuminate\Console\Command::SUCCESS);
});

test('health check AdminUserExists succeeds when admin exists and fails when no admin', function () {
    User::where('role', 'admin')->delete();

    $pendingRestore = PendingRestore::make(
        disk: 'backups',
        backup: 'dummy.zip',
        connection: 'sqlite',
        backupPassword: null
    );

    $check = new AdminUserExists();

    // With no admin, it should fail
    $resultWithoutAdmin = $check->run($pendingRestore);
    expect($resultWithoutAdmin->status)->toBe(\Illuminate\Console\Command::FAILURE);

    // Now create an admin
    User::factory()->create(['role' => 'admin']);
    $resultWithAdmin = $check->run($pendingRestore);
    expect($resultWithAdmin->status)->toBe(\Illuminate\Console\Command::SUCCESS);
});
