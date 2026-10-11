<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_results', function (Blueprint $table) {
            $table->unsignedSmallInteger('arm_position')->nullable()->after('overall_position');
            $table->unsignedSmallInteger('arm_population')->nullable()->after('arm_position');
            $table->unsignedSmallInteger('department_position')->nullable()->after('arm_population');
            $table->unsignedSmallInteger('department_population')->nullable()->after('department_position');
            $table->unsignedSmallInteger('class_population')->nullable()->after('department_population');
        });
    }

    public function down(): void
    {
        Schema::table('term_results', function (Blueprint $table) {
            $table->dropColumn([
                'arm_position',
                'arm_population',
                'department_position',
                'department_population',
                'class_population',
            ]);
        });
    }
};
