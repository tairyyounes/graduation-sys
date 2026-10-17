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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout for restore job in seconds (30 minutes).
     */
    public int $timeout = 1800;

    /**
     * Attempts count.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $logId,
        public string $backupPath,
        public string $disk = 'backups'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = BackupLog::find($this->logId);
        if (! $log) {
            Log::error("RestoreBackupJob: BackupLog with ID {$this->logId} not found.");
            return;
        }

        // 1. Concurrency Guard
        $lock = Cache::lock('backup-restore-operation', 1800);
        if (! $lock->get()) {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Another backup or restore operation is currently running. Operation aborted.',
                'finished_at' => now(),
            ]);
            return;
        }

        $log->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $inMaintenanceMode = false;
        $safetyBackupPath = null;
        $tempExtractDir = null;

        try {
            // 2. Validate archive path (ensure it is .zip and no traversal)
            if (! str_ends_with($this->backupPath, '.zip') || str_contains($this->backupPath, '..')) {
                throw new \InvalidArgumentException('Invalid backup archive path provided: ' . $this->backupPath);
            }

            // Ensure the file exists on the disk
            if (! Storage::disk($this->disk)->exists($this->backupPath)) {
                throw new \RuntimeException("Backup archive does not exist on disk [{$this->disk}]: {$this->backupPath}");
            }

            // 3. Create an automatic safety backup first. Abort if it fails.
            Log::info('RestoreBackupJob: Creating safety backup before restoration...');
            $safetyExit = Artisan::call('backup:run', [
                '--only-db' => true,
                '--filename-prefix' => 'safety-pre-restore-',
            ]);

            if ($safetyExit !== 0) {
                throw new \RuntimeException('Safety backup before restore failed. Aborting restore to protect data. Output: ' . Artisan::output());
            }

            // Locate safety backup file
            $backupName = config('backup.backup.name', 'graduation-sys');
            $safetyFiles = collect(Storage::disk($this->disk)->allFiles($backupName))
                ->filter(fn ($f) => str_contains($f, 'safety-pre-restore-') && str_ends_with($f, '.zip'))
                ->sortByDesc(fn ($f) => Storage::disk($this->disk)->lastModified($f));

            if ($safetyFiles->isNotEmpty()) {
                $safetyBackupPath = $safetyFiles->first();
                Log::info("RestoreBackupJob: Safety backup created successfully at: {$safetyBackupPath}");
            }

            // 4. Enable Maintenance Mode (artisan down)
            Log::info('RestoreBackupJob: Putting application in maintenance mode...');
            Artisan::call('down', [
                '--retry' => 30,
                '--refresh' => 15,
            ]);
            $inMaintenanceMode = true;

            // 5. Run Artisan backup:restore for the database
            Log::info("RestoreBackupJob: Restoring database from [{$this->disk}] {$this->backupPath}...");
            $connection = config('backup.backup.source.databases')[0] ?? env('DB_CONNECTION', 'pgsql');
            
            $restoreArgs = [
                '--disk' => $this->disk,
                '--backup' => $this->backupPath,
                '--connection' => $connection,
                '--reset' => true,
                '--no-interaction' => true,
            ];

            // Provide encryption password from config if present (never log secrets)
            $password = config('backup.backup.password');
            if (! empty($password)) {
                $restoreArgs['--password'] = $password;
            }

            $restoreExit = Artisan::call('backup:restore', $restoreArgs);
            $restoreOutput = Artisan::output();

            if ($restoreExit !== 0) {
                throw new \RuntimeException("Database restore failed with exit code [{$restoreExit}]: {$restoreOutput}");
            }

            // 6. Restore uploaded files separately (ZipArchive -> storage/app/public)
            Log::info('RestoreBackupJob: Restoring user uploaded files from archive...');
            $tempExtractDir = storage_path('app/private/restore-temp/' . uniqid('restore_', true));
            File::ensureDirectoryExists($tempExtractDir);

            $this->restoreUploadedFiles($this->disk, $this->backupPath, $password, $tempExtractDir);

            // 7. Success: Log results and invalidate user sessions gracefully
            Log::info('RestoreBackupJob: Restore completed successfully.');
            $log->update([
                'status' => 'success',
                'finished_at' => now(),
            ]);

            // Invalidate session records in database if session driver is database
            try {
                if (config('session.driver') === 'database' && \Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                    \Illuminate\Support\Facades\DB::table('sessions')->truncate();
                }
            } catch (\Throwable $e) {
                Log::warning('Could not truncate sessions table: ' . $e->getMessage());
            }

        } catch (\Throwable $e) {
            Log::error('RestoreBackupJob failed: ' . $e->getMessage(), ['exception' => $e]);

            // 8. Rollback if safety backup exists
            if ($safetyBackupPath && Storage::disk($this->disk)->exists($safetyBackupPath)) {
                Log::warning("RestoreBackupJob: Attempting rollback using safety backup [{$safetyBackupPath}]...");
                try {
                    $rollbackArgs = [
                        '--disk' => $this->disk,
                        '--backup' => $safetyBackupPath,
                        '--connection' => config('backup.backup.source.databases')[0] ?? env('DB_CONNECTION', 'pgsql'),
                        '--reset' => true,
                        '--no-interaction' => true,
                    ];
                    if (! empty($password)) {
                        $rollbackArgs['--password'] = $password;
                    }
                    Artisan::call('backup:restore', $rollbackArgs);
                    Log::info('RestoreBackupJob: Rollback to safety backup succeeded.');
                } catch (\Throwable $rollbackError) {
                    Log::critical('RestoreBackupJob: Rollback ALSO failed: ' . $rollbackError->getMessage());
                }
            }

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            // Notify admin about the failure
            $adminEmail = config('backup.notifications.mail.to');
            if ($adminEmail) {
                try {
                    Mail::raw("CRITICAL: Database restoration failed!\n\nError: {$e->getMessage()}\nTime: " . now()->toIso8601String(), function ($message) use ($adminEmail) {
                        $message->to($adminEmail)
                            ->subject('[ALERT] Restore Operation Failed - Graduation System');
                    });
                } catch (\Throwable $mailErr) {
                    Log::warning('Failed sending restore failure notification email: ' . $mailErr->getMessage());
                }
            }

            throw $e;
        } finally {
            // 9. Bring application back up if it was taken down
            if ($inMaintenanceMode) {
                Log::info('RestoreBackupJob: Bringing application back up...');
                Artisan::call('up');
            }

            // 10. Clear application & configuration caches
            try {
                Artisan::call('optimize:clear');
            } catch (\Throwable $e) {
                Log::warning('Could not clear caches after restore: ' . $e->getMessage());
            }

            // 11. Clean temp directories
            if ($tempExtractDir && File::isDirectory($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }

            $lock->release();
        }
    }

    /**
     * Extract storage/app/public contents defensively with path traversal rejection.
     */
    protected function restoreUploadedFiles(string $disk, string $archivePath, ?string $password, string $tempDir): void
    {
        // Get local copy of the zip file
        $localZipPath = $tempDir . '/archive.zip';
        file_put_contents($localZipPath, Storage::disk($disk)->get($archivePath));

        $zip = new ZipArchive();
        $openResult = $zip->open($localZipPath);

        if ($openResult !== true) {
            Log::warning("Could not open zip archive for file restoration: code {$openResult}");
            return;
        }

        if (! empty($password)) {
            $zip->setPassword($password);
        }

        $destDir = storage_path('app/public');
        File::ensureDirectoryExists($destDir);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);
            $entryName = $entry['name'] ?? '';

            // Reject paths containing directory traversal
            if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                Log::warning("Skipping suspicious zip entry in restore: {$entryName}");
                continue;
            }

            // Look for entries inside public/ or storage/app/public/
            $relPath = null;
            if (str_starts_with($entryName, 'public/')) {
                $relPath = substr($entryName, strlen('public/'));
            } elseif (str_starts_with($entryName, 'storage/app/public/')) {
                $relPath = substr($entryName, strlen('storage/app/public/'));
            }

            if ($relPath === null || $relPath === '' || str_ends_with($relPath, '/')) {
                continue;
            }

            $targetPath = $destDir . '/' . $relPath;
            File::ensureDirectoryExists(dirname($targetPath));

            $stream = $zip->getStream($entryName);
            if ($stream) {
                $targetFile = fopen($targetPath, 'wb');
                if ($targetFile) {
                    stream_copy_to_stream($stream, $targetFile);
                    fclose($targetFile);
                }
                fclose($stream);
            }
        }

        $zip->close();
    }
}
