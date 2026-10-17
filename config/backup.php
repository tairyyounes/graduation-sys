<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

return [

    'backup' => [
        /*
         * The name of this application. backup:restore looks for backups
         * in a folder with that name on the target disk.
         */
        'name' => env('BACKUP_APP_NAME', env('APP_NAME', 'graduation-sys')),

        'source' => [
            'files' => [
                /*
                 * Include user uploads (storage/app/public, including supervisor approval
                 * documents and any other uploaded files).
                 */
                'include' => [
                    storage_path('app/public'),
                ],

                /*
                 * Exclude vendor, node_modules, and logs.
                 */
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    storage_path('logs'),
                    storage_path('framework'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => storage_path('app'),
            ],

            /*
             * Database connection to back up.
             */
            'databases' => [
                env('DB_CONNECTION', 'pgsql'),
            ],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => '',

            /*
             * Dedicated private backups disk (never under public/) plus optional offsite disk.
             */
            'disks' => array_values(array_filter([
                'backups',
                env('BACKUP_OFFSITE_ENABLED', false) ? env('BACKUP_OFFSITE_DISK', 'backup_offsite') : null,
            ])),

            'continue_on_failure' => false,
        ],

        'temporary_directory' => env('BACKUP_TEMP_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backup-temp'),

        /*
         * Encrypt archives with BACKUP_ARCHIVE_PASSWORD from env.
         */
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',
        'verify_backup' => false,
        'tries' => 1,
        'retry_delay' => 0,
    ],

    /*
     * Notifications configuration for backup & health check events.
     */
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
            BackupWasSuccessfulNotification::class => env('BACKUP_NOTIFY_ON_SUCCESS', false) ? ['mail'] : [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@ctc.ly')),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'system@ctc.ly'),
                'name' => env('MAIL_FROM_NAME', 'Graduation System Backup'),
            ],
        ],

        'slack' => [
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK_URL', ''),
            'channel' => env('BACKUP_SLACK_CHANNEL', null),
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    'log_channel' => null,

    /*
     * Monitored backup destination disks: max age (1 day) and max storage.
     */
    'monitor_backups' => [
        [
            'name' => env('BACKUP_APP_NAME', env('APP_NAME', 'graduation-sys')),
            'disks' => ['backups'],
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => (int) env('BACKUP_MAX_STORAGE_MB', 5000),
            ],
        ],
    ],

    /*
     * Retention policy: keep all 7 days, daily 16 days, weekly 8 weeks, monthly 4 months, plus max storage cap.
     */
    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 1,
            'delete_oldest_backups_when_using_more_megabytes_than' => (int) env('BACKUP_MAX_STORAGE_MB', 5000),
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],

];
