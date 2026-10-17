<?php

namespace App\Listeners;

use App\Models\BackupLog;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\CleanupHasFailed;
use Spatie\Backup\Events\CleanupWasSuccessful;

class BackupEventSubscriber
{
    /**
     * Handle backup successful event.
     */
    public function handleBackupWasSuccessful(BackupWasSuccessful $event): void
    {
        // Try to find the most recent running manual backup log, or create an automated one
        $log = BackupLog::where('type', 'backup')
            ->where('status', 'running')
            ->latest()
            ->first();

        if (! $log) {
            $log = new BackupLog([
                'type' => 'backup',
                'triggered_by' => null, // Scheduled / System
                'started_at' => now(),
            ]);
        }

        // Try to find the latest created file on the disk
        $disk = $event->diskName ?: 'backups';
        $log->disk = $disk;
        $log->status = 'success';
        $log->finished_at = now();

        try {
            $backupName = $event->backupName ?: config('backup.backup.name');
            $files = Storage::disk($disk)->allFiles($backupName);
            if (! empty($files)) {
                $latestFile = end($files);
                $log->file_name = basename($latestFile);
                $log->size = Storage::disk($disk)->size($latestFile);
            }
        } catch (\Throwable) {
            // Ignore filesystem errors when querying size
        }

        $log->save();
    }

    /**
     * Handle backup failed event.
     */
    public function handleBackupHasFailed(BackupHasFailed $event): void
    {
        $log = BackupLog::where('type', 'backup')
            ->where('status', 'running')
            ->latest()
            ->first();

        if (! $log) {
            $log = new BackupLog([
                'type' => 'backup',
                'triggered_by' => null, // Scheduled / System
                'started_at' => now(),
            ]);
        }

        $log->status = 'failed';
        $log->disk = $event->diskName ?: 'backups';
        $log->error_message = $event->exception->getMessage();
        $log->finished_at = now();
        $log->save();
    }

    /**
     * Handle cleanup successful event.
     */
    public function handleCleanupWasSuccessful(CleanupWasSuccessful $event): void
    {
        BackupLog::create([
            'type' => 'cleanup',
            'status' => 'success',
            'disk' => $event->diskName ?: 'backups',
            'triggered_by' => null,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    /**
     * Handle cleanup failed event.
     */
    public function handleCleanupHasFailed(CleanupHasFailed $event): void
    {
        BackupLog::create([
            'type' => 'cleanup',
            'status' => 'failed',
            'disk' => $event->diskName ?: 'backups',
            'error_message' => $event->exception->getMessage(),
            'triggered_by' => null,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            BackupWasSuccessful::class => 'handleBackupWasSuccessful',
            BackupHasFailed::class => 'handleBackupHasFailed',
            CleanupWasSuccessful::class => 'handleCleanupWasSuccessful',
            CleanupHasFailed::class => 'handleCleanupHasFailed',
        ];
    }
}
