<?php

namespace App\Jobs\Tenancy;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function handle(): void
    {
        $this->tenant->database()->makeCredentials();

        try {
            $this->tenant->database()->manager()->createDatabase($this->tenant);
        } catch (QueryException $e) {
            // HY000 / 1007 — database already exists; treat as a no-op so
            // re-seeding (migrate:fresh --seed) and idempotent tenant creation
            // don't blow up.
            if ($e->getCode() !== 'HY000' || ! str_contains($e->getMessage(), 'database exists')) {
                throw $e;
            }
        }
    }
}
