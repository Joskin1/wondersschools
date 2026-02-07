<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->string('cache_key')->nullable()->unique()->after('id');
            $table->string('session')->nullable()->after('academic_session_id');
            $table->integer('term')->nullable()->after('term_id');
            $table->string('settings_name')->nullable()->after('term');
            $table->json('result_data')->nullable()->after('settings_name');
            $table->integer('position_in_class')->nullable()->after('position');
            $table->decimal('overall_average', 5, 2)->nullable()->after('average_score');
            $table->timestamp('generated_at')->nullable()->after('updated_at');
            
            $table->index(['session', 'term', 'classroom_id']);
            $table->index('cache_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex(['session', 'term', 'classroom_id']);
            $table->dropIndex(['cache_key']);
            $table->dropColumn([
                'cache_key',
                'session',
                'term',
                'settings_name',
                'result_data',
                'position_in_class',
                'overall_average',
                'generated_at',
            ]);
        });
    }
};
