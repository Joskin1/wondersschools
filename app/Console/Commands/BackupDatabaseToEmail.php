<?php

namespace App\Console\Commands;

use App\Mail\DatabaseBackupMail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class BackupDatabaseToEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup-email
                            {--tenant= : Specific tenant ID, "landlord", or "all"}
                            {--recipient=* : Specific email address(es) to receive the backup}
                            {--force : Force backup execution even if today is not the scheduled day}
                            {--dry-run : Check which database is scheduled for today without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a compressed database backup (.sql.gz) for landlord or tenant and email it to administrators';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $target = $this->option('tenant');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $recipients = $this->resolveRecipients();

        if (empty($recipients)) {
            $this->error('No valid recipient email addresses configured or provided.');
            Log::error('[DB Backup] Failed: No recipient email address could be resolved.');
            return Command::FAILURE;
        }

        // Determine targets to run
        $targetsToRun = $this->determineTargetsToRun($target, $force);

        if (empty($targetsToRun)) {
            $dayOfMonth = (int) now()->format('j');
            $this->info("No database backup scheduled for today (Day {$dayOfMonth} of month). Use --force or specify --tenant to run immediately.");
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->info('Dry Run: The following databases are queued for backup:');
            foreach ($targetsToRun as $item) {
                $this->line(" - [{$item['type']}] {$item['name']} (DB: {$item['database']}) on Day {$item['day']}");
            }
            $this->info('Recipients: ' . implode(', ', $recipients));
            return Command::SUCCESS;
        }

        $allSuccessful = true;

        foreach ($targetsToRun as $item) {
            $this->newLine();
            $this->info("==================================================");
            $this->info("Processing Backup: [{$item['type']}] {$item['name']}");
            $this->info("==================================================");

            $success = $this->executeBackupForTarget($item, $recipients);
            if (!$success) {
                $allSuccessful = false;
            }
        }

        return $allSuccessful ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Determine which databases should run today.
     *
     * Staggering Schedule:
     * - Day 1: Landlord Central Database
     * - Day 5: Tenant 1 (e.g. Living Spring)
     * - Day 10: Tenant 2 (e.g. Cathedral)
     * - Day 15: Tenant 3 (e.g. Beta)
     * - Day 20, 25: Future tenants (staggered every 5 days within 28 days)
     *
     * @return array<int, array{type: string, id: string|null, name: string, database: string, day: int}>
     */
    protected function determineTargetsToRun(?string $targetOption, bool $force): array
    {
        $dayOfMonth = (int) now()->format('j');
        $allTargets = [];

        // 1. Landlord Central DB (Scheduled on Day 1)
        $landlordConn = config('tenancy.database.central_connection', 'landlord');
        $landlordDb = (string) (config("database.connections.{$landlordConn}.database") ?? 'landlord_database');

        $allTargets[] = [
            'type' => 'landlord',
            'id' => 'landlord',
            'name' => 'Landlord Central System',
            'database' => $landlordDb,
            'connection' => $landlordConn,
            'day' => 1,
        ];

        // 2. Tenants (Scheduled staggered on days 5, 10, 15, 20, 25...)
        try {
            if (class_exists(Tenant::class)) {
                $tenants = Tenant::orderBy('created_at', 'asc')->get();
                $tenantIndex = 0;

                foreach ($tenants as $t) {
                    $day = min(28, 5 * ($tenantIndex + 1));
                    $allTargets[] = [
                        'type' => 'tenant',
                        'id' => $t->id,
                        'name' => $t->name ?? "School {$t->id}",
                        'database' => (string) ($t->tenancy_db_name ?? config('tenancy.database.prefix', 'tenant_') . $t->id),
                        'tenant_model' => $t,
                        'day' => $day,
                    ];
                    $tenantIndex++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[DB Backup] Could not list tenants from landlord DB: " . $e->getMessage());
        }

        // Specific tenant requested
        if ($targetOption !== null && $targetOption !== '' && $targetOption !== 'all') {
            $filtered = array_values(array_filter($allTargets, function ($item) use ($targetOption) {
                return strtolower((string) $item['id']) === strtolower($targetOption);
            }));

            if (empty($filtered)) {
                $this->warn("Requested target [{$targetOption}] was not found.");
            }

            return $filtered;
        }

        // Run all
        if ($targetOption === 'all' || $force) {
            return $allTargets;
        }

        // Staggered execution: only targets whose scheduled day is today
        return array_values(array_filter($allTargets, function ($item) use ($dayOfMonth) {
            return $item['day'] === $dayOfMonth;
        }));
    }

    /**
     * Run backup for a single target and email it.
     *
     * @param array{type: string, id: string|null, name: string, database: string, tenant_model?: Tenant, connection?: string} $target
     * @param array<int, string> $recipients
     */
    protected function executeBackupForTarget(array $target, array $recipients): bool
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $cleanName = preg_replace('/[^A-Za-z0-9_-]/', '_', $target['name']);
        $sqlFileName = "{$cleanName}_Backup_{$timestamp}.sql.gz";
        $sqlTempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $sqlFileName;

        try {
            $tableSummary = [];
            $dbDriver = 'unknown';

            if ($target['type'] === 'landlord') {
                $connName = $target['connection'] ?? 'landlord';
                $dbConfig = config("database.connections.{$connName}", []);
                $dbDriver = $dbConfig['driver'] ?? config('database.default');

                $tableSummary = $this->collectTableCounts($connName, ['tenants', 'users', 'domains', 'tenant_admin_assignments']);

                $this->info("Generating compressed archive for Landlord DB [{$target['database']}]...");
                $this->generateBackupArchive($dbConfig, $sqlTempPath);
            } else {
                /** @var Tenant $tenant */
                $tenant = $target['tenant_model'];

                $tenant->run(function () use ($tenant, &$tableSummary, &$dbDriver, &$dbConfig, $sqlTempPath) {
                    $defaultConn = DB::getDefaultConnection();
                    $dbConfig = config("database.connections.{$defaultConn}", []);
                    $dbDriver = $dbConfig['driver'] ?? 'pgsql';

                    $tableSummary = $this->collectTableCounts($defaultConn, [
                        'users', 'students', 'teachers', 'classrooms', 'subjects',
                        'lesson_notes', 'lesson_plans', 'scores', 'assignments'
                    ]);

                    $this->info("Generating compressed archive for Tenant [{$tenant->id}] DB [{$dbConfig['database']}]...");
                    $this->generateBackupArchive($dbConfig, $sqlTempPath);
                });
            }

            if (!file_exists($sqlTempPath) || filesize($sqlTempPath) === 0) {
                throw new \RuntimeException("Backup file generation produced an empty or missing archive.");
            }

            $fileSize = filesize($sqlTempPath);
            $humanSize = $this->formatBytes($fileSize);
            $this->info("Backup archive generated successfully ({$humanSize}).");

            // Check Gmail 20MB safe limit
            if ($fileSize > 20 * 1024 * 1024) {
                $this->warn("Archive size ({$humanSize}) exceeds the 20 MB email safe limit. Email attachment skipped.");
                Log::warning("[DB Backup] Archive {$sqlFileName} ({$humanSize}) exceeds email attachment threshold.");
                return false;
            }

            $attachments = [
                [
                    'path' => $sqlTempPath,
                    'name' => $sqlFileName,
                    'mime' => 'application/gzip',
                    'size' => $fileSize,
                ],
            ];

            $this->info("Sending backup snapshot to: " . implode(', ', $recipients) . "...");

            $mailable = new DatabaseBackupMail(
                filesToAttach: $attachments,
                tenantName: $target['name'],
                databaseName: $target['database'],
                driver: strtoupper($dbDriver),
                generatedAt: now()->toDayDateTimeString(),
                summarySize: $humanSize,
                tableSummary: $tableSummary
            );

            Mail::to($recipients)->send($mailable);

            $this->info("Email delivered successfully.");
            Log::info("[DB Backup] Backup for {$target['name']} successfully delivered to: " . implode(', ', $recipients));

            return true;
        } catch (\Throwable $e) {
            $this->error("Backup failed for {$target['name']}: " . $e->getMessage());
            Log::error("[DB Backup] Backup failed for {$target['name']}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        } finally {
            if (file_exists($sqlTempPath)) {
                @unlink($sqlTempPath);
            }
        }
    }

    /**
     * Generate the compressed .sql.gz backup archive for a given connection configuration.
     *
     * @param array<string, mixed> $dbConfig
     */
    protected function generateBackupArchive(array $dbConfig, string $tempPath): void
    {
        $driver = $dbConfig['driver'] ?? config('database.default');

        $gz = @gzopen($tempPath, 'w9');
        if (!$gz) {
            throw new \RuntimeException("Unable to open temp file for gzip writing: {$tempPath}");
        }

        try {
            if ($driver === 'pgsql') {
                $this->dumpPostgres($dbConfig, $gz);
            } elseif ($driver === 'sqlite') {
                $this->dumpSqlite($dbConfig, $gz);
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                $this->dumpMysql($dbConfig, $gz);
            } else {
                throw new \RuntimeException("Unsupported database driver [{$driver}] for automated backup.");
            }
        } finally {
            @gzclose($gz);
        }
    }

    /**
     * Dump PostgreSQL using pg_dump streamed into gzip.
     *
     * @param array<string, mixed> $dbConfig
     * @param resource $gz
     */
    protected function dumpPostgres(array $dbConfig, $gz): void
    {
        $host = (string) ($dbConfig['host'] ?? '127.0.0.1');
        $port = (string) ($dbConfig['port'] ?? '5432');
        $username = (string) ($dbConfig['username'] ?? 'postgres');
        $password = (string) ($dbConfig['password'] ?? '');
        $database = (string) ($dbConfig['database'] ?? 'postgres');

        $command = [
            'pg_dump',
            '--host=' . $host,
            '--port=' . $port,
            '--username=' . $username,
            '--no-owner',
            '--no-privileges',
            '--clean',
            '--if-exists',
            '--format=plain',
            $database,
        ];

        $env = array_merge($_ENV, [
            'PGPASSWORD' => $password,
        ]);

        $process = new Process($command, null, $env);
        $process->setTimeout(600); // 10 minutes timeout

        $errorOutput = '';
        $process->run(function ($type, $buffer) use ($gz, &$errorOutput) {
            if ($type === Process::OUT) {
                gzwrite($gz, $buffer);
            } else {
                $errorOutput .= $buffer;
            }
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("pg_dump failed (Exit Code {$process->getExitCode()}): " . trim($errorOutput));
        }
    }

    /**
     * Dump SQLite database by streaming file into gzip.
     *
     * @param array<string, mixed> $dbConfig
     * @param resource $gz
     */
    protected function dumpSqlite(array $dbConfig, $gz): void
    {
        $dbPath = (string) ($dbConfig['database'] ?? ':memory:');

        if ($dbPath !== ':memory:' && file_exists($dbPath)) {
            $handle = fopen($dbPath, 'rb');
            if ($handle) {
                while (!feof($handle)) {
                    $chunk = fread($handle, 65536);
                    if ($chunk !== false) {
                        gzwrite($gz, $chunk);
                    }
                }
                fclose($handle);
                return;
            }
        }

        // Fallback for in-memory SQLite (e.g. testing)
        $dump = "-- SQLite Automated Export\n";
        $dump .= "-- Generated at: " . now()->toIso8601String() . "\n";
        $tables = Schema::getTableListing();
        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            $dump .= "-- Table: {$table} (rows: {$count})\n";
        }

        gzwrite($gz, $dump);
    }

    /**
     * Dump MySQL database using mysqldump streaming into gzip.
     *
     * @param array<string, mixed> $dbConfig
     * @param resource $gz
     */
    protected function dumpMysql(array $dbConfig, $gz): void
    {
        $host = (string) ($dbConfig['host'] ?? '127.0.0.1');
        $port = (string) ($dbConfig['port'] ?? '3306');
        $username = (string) ($dbConfig['username'] ?? 'root');
        $password = (string) ($dbConfig['password'] ?? '');
        $database = (string) ($dbConfig['database'] ?? 'laravel');

        $command = [
            'mysqldump',
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--no-tablespaces',
            $database,
        ];

        $env = array_merge($_ENV, [
            'MYSQL_PWD' => $password,
        ]);

        $process = new Process($command, null, $env);
        $process->setTimeout(600);

        $errorOutput = '';
        $process->run(function ($type, $buffer) use ($gz, &$errorOutput) {
            if ($type === Process::OUT) {
                gzwrite($gz, $buffer);
            } else {
                $errorOutput .= $buffer;
            }
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("mysqldump failed (Exit Code {$process->getExitCode()}): " . trim($errorOutput));
        }
    }

    /**
     * Collect record counts for key tables.
     *
     * @param array<int, string> $tables
     * @return array<string, int>
     */
    protected function collectTableCounts(string $connection, array $tables): array
    {
        $summary = [];
        foreach ($tables as $table) {
            try {
                if (Schema::connection($connection)->hasTable($table)) {
                    $summary[$table] = DB::connection($connection)->table($table)->count();
                }
            } catch (\Throwable $e) {}
        }
        return $summary;
    }

    /**
     * Determine list of recipient email addresses.
     *
     * @return array<int, string>
     */
    protected function resolveRecipients(): array
    {
        // 1. CLI option: --recipient=...
        $options = $this->option('recipient');
        if (!empty($options)) {
            $list = is_array($options) ? $options : [$options];
            $valid = $this->filterValidEmails($list);
            if (!empty($valid)) {
                return $valid;
            }
        }

        // 2. .env configuration (BACKUP_MAIL_RECIPIENT)
        $configured = config('mail.backup_recipient');
        if (!empty($configured)) {
            $list = is_array($configured) ? $configured : explode(',', (string) $configured);
            $valid = $this->filterValidEmails($list);
            if (!empty($valid)) {
                return $valid;
            }
        }

        // 3. Super admin / sudo user in Landlord DB (or default connection)
        try {
            $landlordConn = config('tenancy.database.central_connection', 'landlord');
            $sudoUser = null;
            try {
                $sudoUser = User::on($landlordConn)
                    ->where('role', 'sudo')
                    ->whereNotNull('email')
                    ->orderBy('id', 'asc')
                    ->first();
            } catch (\Throwable $e) {}

            if (!$sudoUser) {
                $sudoUser = User::where('role', 'sudo')
                    ->whereNotNull('email')
                    ->orderBy('id', 'asc')
                    ->first();
            }

            if ($sudoUser && filter_var($sudoUser->email, FILTER_VALIDATE_EMAIL)) {
                return [$sudoUser->email];
            }
        } catch (\Throwable $e) {
            Log::warning("[DB Backup] Could not resolve sudo user from Landlord DB: " . $e->getMessage());
        }

        // 4. Fallback to system from address
        $defaultFrom = config('mail.from.address');
        if ($defaultFrom && filter_var($defaultFrom, FILTER_VALIDATE_EMAIL)) {
            return [$defaultFrom];
        }

        return [];
    }

    /**
     * Filter and sanitize a list of email addresses.
     *
     * @param array<int|string, mixed> $emails
     * @return array<int, string>
     */
    protected function filterValidEmails(array $emails): array
    {
        $valid = [];
        foreach ($emails as $email) {
            $trimmed = trim((string) $email);
            if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                $valid[] = $trimmed;
            }
        }
        return array_values(array_unique($valid));
    }

    /**
     * Format bytes into human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
