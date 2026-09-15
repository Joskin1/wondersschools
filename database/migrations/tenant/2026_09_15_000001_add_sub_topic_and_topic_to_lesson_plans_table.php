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
        Schema::table('lesson_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('lesson_plans', 'topic')) {
                $table->string('topic')->nullable()->after('title');
            }
            if (!Schema::hasColumn('lesson_plans', 'sub_topic')) {
                $table->string('sub_topic')->nullable()->after('topic');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_plans', 'sub_topic')) {
                $table->dropColumn('sub_topic');
            }
            if (Schema::hasColumn('lesson_plans', 'topic')) {
                $table->dropColumn('topic');
            }
        });
    }
};
