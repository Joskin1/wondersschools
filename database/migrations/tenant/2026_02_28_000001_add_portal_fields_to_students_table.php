<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->timestamp('registration_completed_at')->nullable()->after('status');
            $table->boolean('is_portal_active')->default(false)->after('registration_completed_at');
            $table->timestamp('activated_at')->nullable()->after('is_portal_active');
            $table->foreignId('activated_by')->nullable()->after('activated_at');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'registration_completed_at',
                'is_portal_active',
                'activated_at',
                'activated_by',
            ]);
        });
    }
};
