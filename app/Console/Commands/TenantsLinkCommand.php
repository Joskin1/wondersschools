<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantsLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create symbolic links for tenant storage directories inside central public storage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $suffixBase = config('tenancy.filesystem.suffix_base', 'tenant');
        $tenants = Tenant::all();

        $this->info("Creating storage links for {$tenants->count()} tenants...");

        foreach ($tenants as $tenant) {
            $suffix = $suffixBase . $tenant->id;
            $target = storage_path("{$suffix}/app/public");
            $link = storage_path("app/public/{$suffix}");

            $this->comment("Processing tenant: {$tenant->id}");

            // Ensure target directory exists
            if (! is_dir($target)) {
                if (! mkdir($target, 0755, true) && ! is_dir($target)) {
                    $this->error("  × Failed to create target directory: {$target}");
                    continue;
                }
                $this->info("  ✓ Created target storage directory: {$target}");
            }

            // Ensure central public directory exists
            if (! is_dir(storage_path('app/public'))) {
                mkdir(storage_path('app/public'), 0755, true);
            }

            // Handle existing link/directory
            if (file_exists($link) || is_link($link)) {
                if (is_link($link)) {
                    $existingTarget = readlink($link);
                    if ($existingTarget === $target) {
                        $this->info("  ✓ Link already correct.");
                        continue;
                    }
                    unlink($link);
                } else {
                    $this->warn("  ! Directory already exists at {$link}, skipping...");
                    continue;
                }
            }

            // Create symlink
            if (symlink($target, $link)) {
                $this->info("  ✓ Created symbolic link: {$link} -> {$target}");
            } else {
                $this->error("  × Failed to create symbolic link: {$link}");
            }
        }

        $this->info('Tenant links processing complete.');

        return 0;
    }
}
