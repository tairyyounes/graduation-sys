<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateBackupRequest;
use App\Http\Requests\Admin\DeleteBackupRequest;
use App\Http\Requests\Admin\RestoreBackupRequest;
use App\Jobs\CreateBackupJob;
use App\Jobs\RestoreBackupJob;
use App\Models\BackupLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Display a listing of backups, health status, and recent logs.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        Gate::authorize('manage-backups');

        $activeDisks = config('backup.backup.destination.disks', ['backups']);
        $selectedDisk = $request->input('disk', $activeDisks[0] ?? 'backups');

        if (! in_array($selectedDisk, $activeDisks, true)) {
            $selectedDisk = $activeDisks[0] ?? 'backups';
        }

        $appName = config('backup.backup.name', 'graduation-sys');
        $backupItems = [];

        try {
            $storage = Storage::disk($selectedDisk);
            $files = collect($storage->allFiles($appName))
                ->filter(fn ($path) => str_ends_with($path, '.zip') && ! str_contains($path, '..'));

            foreach ($files as $filePath) {
                $size = $storage->size($filePath);
                $lastModified = $storage->lastModified($filePath);
                $modDate = Carbon::createFromTimestamp($lastModified);

                $backupItems[] = [
                    'file_name' => basename($filePath),
                    'path' => $filePath,
                    'disk' => $selectedDisk,
                    'size' => $size,
                    'formatted_size' => $this->formatBytes($size),
                    'date' => $modDate->toIso8601String(),
                    'relative_date' => $modDate->diffForHumans(),
                    'timestamp' => $lastModified,
                    'age_hours' => round(now()->diffInMinutes($modDate) / 60, 1),
                ];
            }
        } catch (\Throwable) {
            $backupItems = [];
        }

        // Sort newest first
        usort($backupItems, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        // Manual pagination for the backup items array
        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);
        $total = count($backupItems);
        $offset = ($page - 1) * $perPage;
        $slicedItems = array_slice($backupItems, $offset, $perPage);

        $paginatedBackups = new LengthAwarePaginator(
            $slicedItems,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Status Summary calculation
        $latestBackup = $backupItems[0] ?? null;
        $lastBackupAgeHours = $latestBackup ? $latestBackup['age_hours'] : null;
        $isOlderThan25Hours = $latestBackup ? ($lastBackupAgeHours > 25) : true;
        $totalSizeBytes = array_sum(array_column($backupItems, 'size'));

        // Check for any currently active running job
        $runningJob = BackupLog::whereIn('status', ['pending', 'running'])
            ->latest()
            ->first();

        $statusSummary = [
            'total_backups' => $total,
            'total_size_bytes' => $totalSizeBytes,
            'formatted_total_size' => $this->formatBytes($totalSizeBytes),
            'last_backup_date' => $latestBackup ? $latestBackup['date'] : null,
            'last_backup_relative' => $latestBackup ? $latestBackup['relative_date'] : null,
            'last_backup_age_hours' => $lastBackupAgeHours,
            'is_older_than_25_hours' => $isOlderThan25Hours,
            'active_disks' => $activeDisks,
            'selected_disk' => $selectedDisk,
            'active_job' => $runningJob ? [
                'id' => $runningJob->id,
                'type' => $runningJob->type,
                'status' => $runningJob->status,
                'started_at' => $runningJob->started_at?->toIso8601String(),
            ] : null,
        ];

        // Recent Audit Logs
        $recentLogs = BackupLog::with('triggeredBy:id,full_name,email,role')
            ->latest()
            ->paginate(15);

        $payload = [
            'backups' => $paginatedBackups,
            'statusSummary' => $statusSummary,
            'recentLogs' => $recentLogs,
            'disks' => $activeDisks,
            'canManageBackups' => $request->user()?->can('manage-backups') ?? false,
        ];

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($payload);
        }

        return Inertia::render('Backups/Index', $payload);
    }

    /**
     * Dispatch a queued backup creation job.
     */
    public function create(CreateBackupRequest $request): JsonResponse
    {
        // Concurrency check before queueing
        $activeJob = BackupLog::whereIn('status', ['pending', 'running'])->exists();
        if ($activeJob) {
            return response()->json([
                'message' => __('A backup or restore job is already running or pending.'),
            ], 429);
        }

        $type = $request->getBackupType();
        $disk = config('backup.backup.destination.disks')[0] ?? 'backups';

        $log = BackupLog::create([
            'type' => 'backup',
            'status' => 'pending',
            'disk' => $disk,
            'triggered_by' => $request->user()->id,
            'started_at' => now(),
        ]);

        CreateBackupJob::dispatch($log->id, $type);

        if (function_exists('activity')) {
            activity()
                ->causedBy($request->user())
                ->performedOn($log)
                ->log("Admin triggered manual [{$type}] backup.");
        }

        return response()->json([
            'message' => __('Backup job queued successfully.'),
            'log_id' => $log->id,
            'status' => 'pending',
            'type' => $type,
        ], 202);
    }

    /**
     * Poll the status of a backup or restore job.
     */
    public function status(Request $request, BackupLog $log): JsonResponse
    {
        Gate::authorize('manage-backups');

        return response()->json([
            'id' => $log->id,
            'type' => $log->type,
            'status' => $log->status,
            'file_name' => $log->file_name,
            'size' => $log->size,
            'formatted_size' => $log->formatted_size,
            'error_message' => $log->error_message,
            'started_at' => $log->started_at?->toIso8601String(),
            'finished_at' => $log->finished_at?->toIso8601String(),
            'duration' => $log->duration_in_seconds,
        ]);
    }

    /**
     * Download a backup archive via streamed download (protected with password.confirm).
     */
    public function download(Request $request): StreamedResponse
    {
        Gate::authorize('manage-backups');

        $request->validate([
            'file_name' => ['required', 'string', 'ends_with:.zip'],
            'disk' => ['nullable', 'string'],
        ]);

        $fileName = $request->input('file_name');
        if (str_contains($fileName, '..') || str_starts_with($fileName, '/') || str_starts_with($fileName, '\\')) {
            abort(400, 'Invalid file path.');
        }

        $disk = $request->input('disk', 'backups');
        $appName = config('backup.backup.name', 'graduation-sys');

        $filePath = str_starts_with($fileName, $appName . '/')
            ? $fileName
            : $appName . '/' . ltrim($fileName, '/');

        if (! Storage::disk($disk)->exists($filePath)) {
            abort(404, 'Backup file not found on disk.');
        }

        // Log audit
        BackupLog::create([
            'type' => 'download',
            'status' => 'success',
            'disk' => $disk,
            'file_name' => basename($filePath),
            'size' => Storage::disk($disk)->size($filePath),
            'triggered_by' => $request->user()->id,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        if (function_exists('activity')) {
            activity()
                ->causedBy($request->user())
                ->log("Admin downloaded backup archive: {$fileName}");
        }

        return Storage::disk($disk)->download($filePath, basename($filePath), [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Delete a backup file.
     */
    public function destroy(DeleteBackupRequest $request): JsonResponse
    {
        $fileName = $request->validated('file_name');
        $disk = $request->validated('disk') ?? 'backups';
        $appName = config('backup.backup.name', 'graduation-sys');

        $filePath = str_starts_with($fileName, $appName . '/')
            ? $fileName
            : $appName . '/' . ltrim($fileName, '/');

        if (! Storage::disk($disk)->exists($filePath)) {
            return response()->json(['message' => __('Backup file not found on disk.')], 404);
        }

        $size = Storage::disk($disk)->size($filePath);
        Storage::disk($disk)->delete($filePath);

        BackupLog::create([
            'type' => 'delete',
            'status' => 'success',
            'disk' => $disk,
            'file_name' => basename($filePath),
            'size' => $size,
            'triggered_by' => $request->user()->id,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        if (function_exists('activity')) {
            activity()
                ->causedBy($request->user())
                ->log("Admin deleted backup archive: {$fileName}");
        }

        return response()->json([
            'message' => __('Backup file deleted successfully.'),
            'file_name' => basename($filePath),
        ]);
    }

    /**
     * Initiate a restore operation for the database & user uploads.
     */
    public function restore(RestoreBackupRequest $request): JsonResponse
    {
        // Concurrency guard
        $activeJob = BackupLog::whereIn('status', ['pending', 'running'])->exists();
        if ($activeJob) {
            return response()->json([
                'message' => __('Another backup or restore operation is already in progress.'),
            ], 429);
        }

        $fileName = $request->validated('file_name');
        $disk = $request->validated('disk') ?? 'backups';
        $appName = config('backup.backup.name', 'graduation-sys');

        $filePath = str_starts_with($fileName, $appName . '/')
            ? $fileName
            : $appName . '/' . ltrim($fileName, '/');

        if (! Storage::disk($disk)->exists($filePath)) {
            return response()->json(['message' => __('The selected backup file was not found on disk.')], 404);
        }

        $log = BackupLog::create([
            'type' => 'restore',
            'status' => 'pending',
            'disk' => $disk,
            'file_name' => basename($filePath),
            'size' => Storage::disk($disk)->size($filePath),
            'triggered_by' => $request->user()->id,
            'started_at' => now(),
        ]);

        RestoreBackupJob::dispatch($log->id, $filePath, $disk);

        if (function_exists('activity')) {
            activity()
                ->causedBy($request->user())
                ->performedOn($log)
                ->log("Admin initiated database restore from backup: {$fileName}");
        }

        return response()->json([
            'message' => __('Restore process started in the background. The system will enter maintenance mode during restore.'),
            'log_id' => $log->id,
            'status' => 'pending',
        ], 202);
    }

    /**
     * Helper to format bytes into readable units.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        return round($bytes / (1024 ** $power), 2) . ' ' . ($units[$power] ?? 'B');
    }
}
