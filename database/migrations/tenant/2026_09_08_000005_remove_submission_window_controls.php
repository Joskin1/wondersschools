<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_windows', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropIndex(['is_open']);
            $table->dropIndex('window_time_idx');
            $table->dropColumn([
                'opens_at',
                'closes_at',
                'is_open',
                'updated_by',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('submission_windows', function (Blueprint $table) {
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->boolean('is_open')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('submission_windows', function (Blueprint $table) {
            $table->index('is_open');
            $table->index(['opens_at', 'closes_at'], 'window_time_idx');
        });
    }
};
