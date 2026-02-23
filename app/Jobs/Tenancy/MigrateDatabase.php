<?php

namespace App\Jobs\Tenancy;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MigrateDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function handle(): void
    {
        $this->tenant->run(function () {
            \Artisan::call('migrate', [
                '--path'     => config('tenancy.migration_parameters.--path'),
                '--realpath' => true,
                '--force'    => true,
            ]);
        });
    }
}
