<?php

namespace App\Jobs;

use App\Models\BackupLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout for the backup job in seconds (15 minutes).
     */
    public int $timeout = 900;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $logId,
        public string $type = 'full'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = BackupLog::find($this->logId);
        if (! $log) {
            Log::error("CreateBackupJob: BackupLog with ID {$this->logId} not found.");
            return;
        }

        $lock = Cache::lock('backup-restore-operation', 900);
        if (! $lock->get()) {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Another backup or restore operation is already in progress.',
                'finished_at' => now(),
            ]);
            return;
        }

        $log->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $options = [];
            if ($this->type === 'db') {
                $options['--only-db'] = true;
            } elseif ($this->type === 'files') {
                $options['--only-files'] = true;
            }

            $exitCode = Artisan::call('backup:run', $options);
            $output = Artisan::output();

            if ($exitCode !== 0) {
                throw new \RuntimeException('Backup command failed with exit code ' . $exitCode . ': ' . $output);
            }

            // Find the most recently generated backup file on the disk
            $disk = $log->disk ?: 'backups';
            $backupName = config('backup.backup.name', 'graduation-sys');

            $fileName = null;
            $fileSize = null;

            try {
                $files = collect(Storage::disk($disk)->allFiles($backupName))
                    ->filter(fn ($file) => str_ends_with($file, '.zip'))
                    ->sortByDesc(fn ($file) => Storage::disk($disk)->lastModified($file));

                if ($files->isNotEmpty()) {
                    $latestFile = $files->first();
                    $fileName = basename($latestFile);
                    $fileSize = Storage::disk($disk)->size($latestFile);
                }
            } catch (\Throwable $e) {
                Log::warning('Could not inspect generated backup file size: ' . $e->getMessage());
            }

            $log->update([
                'status' => 'success',
                'file_name' => $fileName ?? $log->file_name,
                'size' => $fileSize ?? $log->size,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('CreateBackupJob failed: ' . $e->getMessage(), ['exception' => $e]);
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);
        } finally {
            $lock->release();
        }
    }
}
