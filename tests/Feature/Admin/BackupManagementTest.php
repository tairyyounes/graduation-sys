<?php

use App\Jobs\CreateBackupJob;
use App\Models\BackupLog;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('backups');
    config(['backup.backup.name' => 'graduation-sys']);
    config(['backup.backup.destination.disks' => ['backups']]);
});

test('non-admin user cannot access backup routes and receives 403', function () {
    $student = User::factory()->create(['role' => 'student']);
    $deptMember = User::factory()->create(['role' => 'department_member']);

    $this->actingAs($student)->get('/admin/backups')->assertForbidden();
    $this->actingAs($student)->postJson('/admin/backups', ['type' => 'full'])->assertForbidden();

    $this->actingAs($deptMember)->get('/admin/backups')->assertForbidden();
    $this->actingAs($deptMember)->postJson('/admin/backups', ['type' => 'full'])->assertForbidden();
});

test('admin can access backup management page and receive Inertia props', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Seed a dummy backup in storage
    Storage::disk('backups')->put('graduation-sys/2026-10-15-12-00-00.zip', 'dummy-archive-content');

    $response = $this->actingAs($admin)->get('/admin/backups');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Backups/Index')
        ->has('backups')
        ->has('statusSummary')
        ->has('recentLogs')
        ->has('disks')
        ->where('canManageBackups', true)
    );
});

test('admin can fetch backups and status via JSON API', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Storage::disk('backups')->put('graduation-sys/2026-10-15-12-00-00.zip', 'dummy-archive-content');

    $response = $this->actingAs($admin)->getJson('/admin/backups');

    $response->assertOk();
    $response->assertJsonStructure([
        'backups' => ['data', 'total'],
        'statusSummary' => ['total_backups', 'selected_disk'],
        'recentLogs',
        'disks',
        'canManageBackups',
    ]);
});

test('admin can trigger a manual backup and it dispatches CreateBackupJob with pending BackupLog', function () {
    Queue::fake();

    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->postJson('/admin/backups', [
        'type' => 'db',
    ]);

    $response->assertStatus(202);
    $response->assertJson([
        'status' => 'pending',
        'type' => 'db',
    ]);

    $logId = $response->json('log_id');
    expect($logId)->not->toBeNull();

    $log = BackupLog::find($logId);
    expect($log)->not->toBeNull();
    expect($log->type)->toBe('backup');
    expect($log->status)->toBe('pending');
    expect($log->triggered_by)->toBe($admin->id);

    Queue::assertPushed(CreateBackupJob::class, function ($job) use ($logId) {
        return $job->logId === $logId && $job->type === 'db';
    });
});

test('status endpoint returns current job details', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $log = BackupLog::create([
        'type' => 'backup',
        'status' => 'running',
        'disk' => 'backups',
        'file_name' => 'archive.zip',
        'size' => 1048576,
        'triggered_by' => $admin->id,
        'started_at' => now(),
    ]);

    $response = $this->actingAs($admin)->getJson("/admin/backups/status/{$log->id}");

    $response->assertOk();
    $response->assertJson([
        'id' => $log->id,
        'type' => 'backup',
        'status' => 'running',
        'file_name' => 'archive.zip',
        'size' => 1048576,
    ]);
});

test('path traversal attempts are strictly rejected on download and delete', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Traversal on delete
    $deleteResponse = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->deleteJson('/admin/backups', [
            'file_name' => '../../etc/passwd.zip',
            'disk' => 'backups',
        ]);

    $deleteResponse->assertStatus(422);

    // Traversal on download
    $downloadResponse = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/admin/backups/download?file_name=../../secrets.zip&disk=backups');

    $downloadResponse->assertStatus(400);
});

test('admin can delete a backup file and it logs action in BackupLog', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Storage::disk('backups')->put('graduation-sys/delete-me.zip', 'content-to-delete');

    $response = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->deleteJson('/admin/backups', [
            'file_name' => 'delete-me.zip',
            'disk' => 'backups',
        ]);

    $response->assertOk();
    Storage::disk('backups')->assertMissing('graduation-sys/delete-me.zip');

    $log = BackupLog::where('type', 'delete')->where('file_name', 'delete-me.zip')->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe('success');
    expect($log->triggered_by)->toBe($admin->id);
});

test('admin can download a backup file with confirmed password', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Storage::disk('backups')->put('graduation-sys/downloadable.zip', 'sample-zip-data');

    $response = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/admin/backups/download?file_name=downloadable.zip&disk=backups');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/zip');

    $log = BackupLog::where('type', 'download')->where('file_name', 'downloadable.zip')->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe('success');
});
