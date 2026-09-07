<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        if (Schema::connection('landlord')->hasTable('users') && !Schema::connection('landlord')->hasColumn('users', 'deleted_at')) {
            Schema::connection('landlord')->table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('landlord')->hasTable('users') && Schema::connection('landlord')->hasColumn('users', 'deleted_at')) {
            Schema::connection('landlord')->table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
