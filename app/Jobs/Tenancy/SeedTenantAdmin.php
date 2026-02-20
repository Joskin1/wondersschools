<?php

namespace App\Jobs\Tenancy;

use App\Models\Central\School;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedTenantAdmin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenant;

    public function __construct(School $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle()
    {
        // This job should run IN the tenant context (TenancyServiceProvider will execute JobPipeline inside tenant context?
        // Wait, Stancl's MigrateDatabase job switches to the tenant context automatically if configured.
        // Actually, JobPipeline for TenantCreated passes the Tenant event. We must switch manually if needed.
        
        $tenant = $this->tenant;

        // Stancl tenancy initialization helper
        tenancy()->initialize($tenant);

        try {
            DB::table('users')->insert([
                'name' => config('tenancy.default_admin_name', 'School Admin'),
                'email' => config('tenancy.default_admin_email', 'admin@school.test'), // Just a placeholder, admin will change it
                'password' => Hash::make('password'), // Required to change on first login
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            tenancy()->end();
        }
    }
}
