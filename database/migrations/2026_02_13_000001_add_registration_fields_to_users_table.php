<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('role');
            $table->timestamp('registration_completed_at')->nullable()->after('is_active');
            
            // Index for fast filtering of active users
            $table->index('is_active');
        });

        // Activate existing teachers who already have passwords
        // This prevents disruption for teachers already in the system
        DB::table('users')
            ->where('role', 'teacher')
            ->whereNotNull('password')
            ->update([
                'is_active' => true,
                'registration_completed_at' => DB::raw('created_at')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'registration_completed_at']);
        });
    }
};
