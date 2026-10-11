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
        if (Schema::hasTable('classrooms')) {
            Schema::table('classrooms', function (Blueprint $table) {
                if (!Schema::hasColumn('classrooms', 'arm')) {
                    $table->string('arm', 50)->nullable()->after('name');
                }

                if (!Schema::hasColumn('classrooms', 'level')) {
                    $table->string('level', 50)->nullable()->after('arm');
                }
            });

            // Drop unique constraint on class_order to allow shared academic levels across arms
            try {
                Schema::table('classrooms', function (Blueprint $table) {
                    $table->dropUnique('classrooms_class_order_unique');
                });
            } catch (\Throwable) {
                try {
                    Schema::table('classrooms', function (Blueprint $table) {
                        $table->dropUnique(['class_order']);
                    });
                } catch (\Throwable) {
                    // Index might not be unique or might already have been dropped
                }
            }

            try {
                Schema::table('classrooms', function (Blueprint $table) {
                    $table->index('class_order');
                });
            } catch (\Throwable) {
                // Index might already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('classrooms')) {
            Schema::table('classrooms', function (Blueprint $table) {
                if (Schema::hasColumn('classrooms', 'arm')) {
                    $table->dropColumn('arm');
                }

                if (Schema::hasColumn('classrooms', 'level')) {
                    $table->dropColumn('level');
                }
            });
        }
    }
};
