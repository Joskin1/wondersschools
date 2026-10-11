<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $defaults = [
            ['name' => 'Science', 'code' => 'SCI', 'description' => 'Pure and applied sciences stream (Physics, Chemistry, Biology, etc.)', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Commercial', 'code' => 'COMM', 'description' => 'Business and commercial studies stream (Accounting, Commerce, Economics, etc.)', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Arts / Humanities', 'code' => 'ARTS', 'description' => 'Humanities, literature, and social sciences stream (Literature, Government, CRS/IRS, etc.)', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($defaults as $dept) {
            DB::table('departments')->updateOrInsert(
                ['name' => $dept['name']],
                $dept
            );
        }
    }

    public function down(): void
    {
        // Don't accidentally wipe user-customized departments on rollback unless desired
    }
};
