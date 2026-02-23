<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seeds a freshly-created tenant DB with default settings.
     * These provide sensible defaults; the admin can override them
     * via the Settings section in the admin panel.
     */
    public function run(): void
    {
        $defaults = [
            'school_name'    => tenant('name') ?? 'Wonders School',
            'school_address' => '123 School Lane, City',
            'school_phone'   => '+234 000 000 0000',
            'school_email'   => 'info@school.edu',
            'site_logo'      => null,
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
