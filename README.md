<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 🛡️ Backup & Restore System (Automated + Manual)

The project includes an enterprise-grade automated & manual backup and restore system powered by `spatie/laravel-backup` and `wnx/laravel-backup-restore`, integrated with an administrative Vue 3 UI (Inertia.js, plain JavaScript) and complete audit logging.

### 1. Key Features
- **Scope**: Backs up both PostgreSQL database dump and user uploaded files (`storage/app/public`, supervisor approvals, student attachments), excluding `vendor/`, `node_modules/`, and logs.
- **Storage Disks**: Dedicated private `backups` disk (`storage/app/private/backups`, never exposed via public URL) and optional offsite disk (S3 / SFTP).
- **Encryption**: AES-256 archive encryption using `BACKUP_ARCHIVE_PASSWORD`.
- **Retention**: Cleanups keep all backups for 7 days, daily for 16 days, weekly for 8 weeks, monthly for 4 months, plus max storage capping.
- **Defensive Restore**:
  1. Super admin authorization + password verification + explicit `RESTORE` confirmation phrase.
  2. Concurrency lock via `Cache::lock('backup-restore-operation')`.
  3. Automatic pre-restore safety database backup.
  4. Automatic maintenance mode (`artisan down` / `artisan up`).
  5. Post-restore health checks: `DatabaseHasTables`, `UsersTableNotEmpty`, `AdminUserExists`.
  6. Defensive extraction of uploaded files with path traversal rejection (`..`).
  7. Automatic rollback to safety backup on failure.
  8. Graceful session invalidation.

---

### 2. Environment Variables (`.env`)

Add the following variables to `.env`:

```env
# Backup & Restore Configuration
BACKUP_APP_NAME=graduation-sys
BACKUP_ARCHIVE_PASSWORD=your-super-strong-encryption-password-here
DB_DUMP_PATH="C:\Program Files\PostgreSQL\16\bin" # Windows / Herd or /usr/bin on Linux
BACKUP_NOTIFICATION_EMAIL=admin@ctc.ly
BACKUP_NOTIFY_ON_SUCCESS=false
BACKUP_MAX_STORAGE_MB=5000

# Offsite Storage (Optional)
BACKUP_OFFSITE_ENABLED=false
BACKUP_OFFSITE_DISK=backup_offsite
BACKUP_OFFSITE_DRIVER=s3
BACKUP_OFFSITE_BUCKET=your-bucket-name
```

---

### 3. PostgreSQL `pg_dump` & `psql` Compatibility

#### Version Rule:
> **Important:** The `pg_dump` binary version **must be greater than or equal to** the PostgreSQL database server version. A `pg_dump` from PostgreSQL 16 can back up PostgreSQL 14, 15, and 16 servers, but a PostgreSQL 14 `pg_dump` cannot back up a PostgreSQL 16 server.

#### Windows (with Laravel Herd):
1. If PostgreSQL was installed through an installer (e.g. EnterpriseDB), find your bin directory:
   `C:\Program Files\PostgreSQL\16\bin`
2. Set in `.env`:
   `DB_DUMP_PATH="C:\Program Files\PostgreSQL\16\bin"`
3. Verify in PowerShell:
   ```powershell
   & "C:\Program Files\PostgreSQL\16\bin\pg_dump.exe" --version
   & "C:\Program Files\PostgreSQL\16\bin\psql.exe" --version
   ```

#### Linux (Ubuntu / Debian):
```bash
sudo apt update
sudo apt install -y postgresql-client-16
which pg_dump # usually /usr/bin/pg_dump
```
Set in `.env`:
```env
DB_DUMP_PATH=/usr/bin
```

---

### 4. Automated Scheduling

The backup schedule is declared in `routes/console.php`:
- `backup:clean` daily at 01:00
- `backup:run` daily at 01:30
- `backup:monitor` daily at 03:00

#### Linux Cron Entry:
Add a single cron entry for Laravel's scheduler:
```bash
* * * * * cd /path/to/graduation-sys && php artisan schedule:run >> /dev/null 2>&1
```

#### Windows Local Testing (Laravel Herd / Task Scheduler):
In PowerShell while testing locally:
```powershell
php artisan schedule:work
```
Or create a scheduled task in Windows Task Scheduler running `php.exe artisan schedule:run` every minute.

---

### 5. Manual CLI Restore Runbook

To restore via CLI without the web UI:

```bash
# 1. Restore the latest backup from the default backups disk
php artisan backup:restore --backup=latest --reset

# 2. Restore a specific archive
php artisan backup:restore --disk=backups --backup=graduation-sys/2026-10-15-12-00-00.zip --connection=pgsql --reset

# 3. Monthly staging test restore
php artisan backup:restore --disk=backups --backup=latest --connection=pgsql --reset --no-interaction
```

---

### 6. Testing

Run both backend and frontend test suites:

```bash
# Backend Feature & Unit Tests (Pest)
php artisan test

# Frontend Unit Tests (Vitest)
npm run test:frontend
```

