<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SplitFullNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'name:split-full-names
                            {--tenant=* : Specific tenant ID(s) or "landlord" to process}
                            {--all : Process landlord and all tenants}
                            {--dry-run : Preview changes without writing to database}
                            {--force : Force re-splitting and updating records even if already split}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Split existing full_name / legacy name fields into surname, first_name, and other_name for landlord and tenant databases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantOptions = array_filter((array) $this->option('tenant'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info('=====================================================');
        $this->info('  Starting Full Name Splitter & Cleanup Command');
        $this->info('  Dry Run: ' . ($dryRun ? 'YES (No changes will be saved)' : 'NO (Applying changes)'));
        $this->info('  Force:   ' . ($force ? 'YES' : 'NO'));
        $this->info('=====================================================');

        $shouldRunLandlord = empty($tenantOptions)
            || in_array('landlord', $tenantOptions, true)
            || (bool) $this->option('all');

        if ($shouldRunLandlord) {
            $this->processLandlord($dryRun, $force);
        }

        $this->processTenants($tenantOptions, $dryRun, $force);

        $this->newLine();
        $this->info('✓ Name split processing finished.');

        return Command::SUCCESS;
    }

    /**
     * Process Landlord (central) database users.
     */
    protected function processLandlord(bool $dryRun, bool $force): void
    {
        $this->newLine();
        $this->info('── Processing Central / Landlord Database ──');

        $landlordConn = config('tenancy.database.central_connection', 'landlord');

        try {
            $hasName = Schema::connection($landlordConn)->hasColumn('users', 'name');
            $hasSurname = Schema::connection($landlordConn)->hasColumn('users', 'surname');

            if (! $hasSurname && ! $hasName) {
                $this->warn('  [Landlord] Neither "name" nor "surname" column found on users table. Skipping.');
                return;
            }

            $users = DB::connection($landlordConn)->table('users')->orderBy('id')->get();
            $updatedCount = 0;

            foreach ($users as $user) {
                $rawName = $this->determineRawName($user, $hasName);
                if ($rawName === '') {
                    continue;
                }

                $parsed = self::parseFullName($rawName);

                // If not forcing, skip if already well-split (surname & first_name populated and surname has no comma/spaces)
                if (! $force && $hasSurname && ! empty($user->surname) && ! empty($user->first_name) && ! str_contains((string) $user->surname, ',') && ! str_contains(trim((string) $user->surname), ' ')) {
                    continue;
                }

                // Check if anything actually changes
                $currentSurname = (string) ($user->surname ?? '');
                $currentFirstName = (string) ($user->first_name ?? '');
                $currentOtherName = $user->other_name ?? null;

                if ($currentSurname === $parsed['surname'] && $currentFirstName === $parsed['first_name'] && $currentOtherName === $parsed['other_name']) {
                    continue;
                }

                $this->line(sprintf(
                    '  User #%d: "%s" -> Surname: "%s", First: "%s", Other: "%s"',
                    $user->id,
                    $rawName,
                    $parsed['surname'],
                    $parsed['first_name'],
                    $parsed['other_name'] ?? ''
                ));

                if (! $dryRun && $hasSurname) {
                    DB::connection($landlordConn)->table('users')->where('id', $user->id)->update([
                        'surname' => $parsed['surname'],
                        'first_name' => $parsed['first_name'],
                        'other_name' => $parsed['other_name'],
                        'updated_at' => now(),
                    ]);
                }

                $updatedCount++;
            }

            $this->info("  [Landlord] {$updatedCount} central user(s) " . ($dryRun ? 'identified for update' : 'updated') . '.');
        } catch (\Throwable $e) {
            $this->error('  [Landlord] Failed: ' . $e->getMessage());
        }
    }

    /**
     * Process Tenant databases.
     *
     * @param array<int, string> $tenantOptions
     */
    protected function processTenants(array $tenantOptions, bool $dryRun, bool $force): void
    {
        $tenantsQuery = Tenant::query();

        // Filter by specific tenants if requested (excluding the literal 'landlord' keyword)
        $specificTenants = array_values(array_filter($tenantOptions, fn ($t) => $t !== 'landlord'));
        if (! empty($specificTenants)) {
            $tenantsQuery->whereIn('id', $specificTenants);
        }

        $tenants = $tenantsQuery->get();

        if ($tenants->isEmpty()) {
            if (! empty($specificTenants)) {
                $this->warn('No matching tenants found for: ' . implode(', ', $specificTenants));
            }
            return;
        }

        foreach ($tenants as $tenant) {
            $this->newLine();
            $this->info("── Processing Tenant [{$tenant->id}] ({$tenant->name}) ──");

            try {
                $tenant->run(function () use ($tenant, $dryRun, $force) {
                    $this->processCurrentTenantDatabase($tenant, $dryRun, $force);
                });
            } catch (\Throwable $e) {
                $this->error("  Failed processing tenant [{$tenant->id}]: " . $e->getMessage());
            }
        }
    }

    /**
     * Process the current tenant's database tables (users, staff, students).
     */
    protected function processCurrentTenantDatabase(Tenant $tenant, bool $dryRun, bool $force): void
    {
        // 1. Tenant Users
        if (Schema::hasTable('users')) {
            $hasName = Schema::hasColumn('users', 'name');
            $hasSurname = Schema::hasColumn('users', 'surname');

            if ($hasSurname || $hasName) {
                $users = DB::table('users')->orderBy('id')->get();
                $userUpdates = 0;

                foreach ($users as $user) {
                    $rawName = $this->determineRawName($user, $hasName);
                    if ($rawName === '') {
                        continue;
                    }

                    $parsed = self::parseFullName($rawName);

                    if (! $force && $hasSurname && ! empty($user->surname) && ! empty($user->first_name) && ! str_contains((string) $user->surname, ',') && ! str_contains(trim((string) $user->surname), ' ')) {
                        continue;
                    }

                    $currentSurname = (string) ($user->surname ?? '');
                    $currentFirstName = (string) ($user->first_name ?? '');
                    $currentOtherName = $user->other_name ?? null;

                    if ($currentSurname === $parsed['surname'] && $currentFirstName === $parsed['first_name'] && $currentOtherName === $parsed['other_name']) {
                        continue;
                    }

                    $this->line(sprintf(
                        '    User #%d (%s): "%s" -> Surname: "%s", First: "%s", Other: "%s"',
                        $user->id,
                        $user->role ?? 'user',
                        $rawName,
                        $parsed['surname'],
                        $parsed['first_name'],
                        $parsed['other_name'] ?? ''
                    ));

                    if (! $dryRun && $hasSurname) {
                        DB::table('users')->where('id', $user->id)->update([
                            'surname' => $parsed['surname'],
                            'first_name' => $parsed['first_name'],
                            'other_name' => $parsed['other_name'],
                            'updated_at' => now(),
                        ]);
                    }

                    $userUpdates++;
                }

                $this->info("    Users: {$userUpdates} record(s) " . ($dryRun ? 'identified for update' : 'updated') . '.');
            }
        }

        // 2. Tenant Staff
        if (Schema::hasTable('staff')) {
            $hasName = Schema::hasColumn('staff', 'name');
            $hasSurname = Schema::hasColumn('staff', 'surname');

            if ($hasSurname || $hasName) {
                $staffMembers = DB::table('staff')->orderBy('id')->get();
                $staffUpdates = 0;

                foreach ($staffMembers as $staff) {
                    $rawName = $this->determineRawName($staff, $hasName);
                    if ($rawName === '') {
                        continue;
                    }

                    $parsed = self::parseFullName($rawName);

                    if (! $force && $hasSurname && ! empty($staff->surname) && ! empty($staff->first_name) && ! str_contains((string) $staff->surname, ',') && ! str_contains(trim((string) $staff->surname), ' ')) {
                        continue;
                    }

                    $currentSurname = (string) ($staff->surname ?? '');
                    $currentFirstName = (string) ($staff->first_name ?? '');
                    $currentOtherName = $staff->other_name ?? null;

                    if ($currentSurname === $parsed['surname'] && $currentFirstName === $parsed['first_name'] && $currentOtherName === $parsed['other_name']) {
                        continue;
                    }

                    $this->line(sprintf(
                        '    Staff #%d: "%s" -> Surname: "%s", First: "%s", Other: "%s"',
                        $staff->id,
                        $rawName,
                        $parsed['surname'],
                        $parsed['first_name'],
                        $parsed['other_name'] ?? ''
                    ));

                    if (! $dryRun && $hasSurname) {
                        DB::table('staff')->where('id', $staff->id)->update([
                            'surname' => $parsed['surname'],
                            'first_name' => $parsed['first_name'],
                            'other_name' => $parsed['other_name'],
                            'updated_at' => now(),
                        ]);
                    }

                    $staffUpdates++;
                }

                $this->info("    Staff: {$staffUpdates} record(s) " . ($dryRun ? 'identified for update' : 'updated') . '.');
            }
        }

        // 3. Students without user or unlinked / full_name migration
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'full_name')) {
            $studentsWithFullName = DB::table('students')->whereNotNull('full_name')->where('full_name', '!=', '')->get();
            $studentUpdates = 0;

            foreach ($studentsWithFullName as $student) {
                if (! $student->user_id) {
                    continue;
                }

                $rawName = (string) $student->full_name;
                $parsed = self::parseFullName($rawName);

                $user = DB::table('users')->where('id', $student->user_id)->first();
                if (! $user) {
                    continue;
                }

                if (! $force && ! empty($user->surname) && ! empty($user->first_name) && ! str_contains((string) $user->surname, ',') && ! str_contains(trim((string) $user->surname), ' ')) {
                    continue;
                }

                $this->line(sprintf(
                    '    Student #%d (User #%d): "%s" -> Surname: "%s", First: "%s", Other: "%s"',
                    $student->id,
                    $student->user_id,
                    $rawName,
                    $parsed['surname'],
                    $parsed['first_name'],
                    $parsed['other_name'] ?? ''
                ));

                if (! $dryRun && Schema::hasColumn('users', 'surname')) {
                    DB::table('users')->where('id', $student->user_id)->update([
                        'surname' => $parsed['surname'],
                        'first_name' => $parsed['first_name'],
                        'other_name' => $parsed['other_name'],
                        'updated_at' => now(),
                    ]);
                }

                $studentUpdates++;
            }

            if ($studentUpdates > 0) {
                $this->info("    Students: {$studentUpdates} linked user(s) " . ($dryRun ? 'identified for update' : 'updated') . ' from student.full_name.');
            }
        }
    }

    /**
     * Determine raw name string from record.
     */
    protected function determineRawName(object $record, bool $hasName): string
    {
        if ($hasName && ! empty($record->name)) {
            return trim((string) $record->name);
        }

        // If surname exists and has spaces or commas, or if combined with first_name
        $parts = array_filter([
            $record->surname ?? null,
            $record->first_name ?? null,
            $record->other_name ?? null,
        ]);

        return trim(implode(' ', $parts));
    }

    /**
     * Parse full name into [surname, first_name, other_name].
     *
     * Supports formats:
     * - "Surname, FirstName OtherName..."
     * - "Surname FirstName OtherName..."
     * - "Surname FirstName"
     * - "Surname"
     *
     * @return array{surname: string, first_name: string, other_name: string|null}
     */
    public static function parseFullName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return [
                'surname' => '',
                'first_name' => '',
                'other_name' => null,
            ];
        }

        // Comma-separated: "Surname, First Other"
        if (str_contains($name, ',')) {
            $parts = explode(',', $name, 2);
            $surname = trim($parts[0]);
            $rest = trim($parts[1] ?? '');
            $restParts = array_values(array_filter(preg_split('/\s+/', $rest)));

            return [
                'surname' => $surname,
                'first_name' => $restParts[0] ?? '',
                'other_name' => count($restParts) > 1 ? implode(' ', array_slice($restParts, 1)) : null,
            ];
        }

        // Space-separated
        $parts = array_values(array_filter(preg_split('/\s+/', $name)));
        $count = count($parts);

        if ($count === 0) {
            return [
                'surname' => '',
                'first_name' => '',
                'other_name' => null,
            ];
        }

        if ($count === 1) {
            return [
                'surname' => $parts[0],
                'first_name' => '',
                'other_name' => null,
            ];
        }

        if ($count === 2) {
            return [
                'surname' => $parts[0],
                'first_name' => $parts[1],
                'other_name' => null,
            ];
        }

        return [
            'surname' => $parts[0],
            'first_name' => $parts[1],
            'other_name' => implode(' ', array_slice($parts, 2)),
        ];
    }
}
