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
        Schema::table('classrooms', function (Blueprint $table) {
            $table->unsignedInteger('class_order')->after('name');
            $table->boolean('is_active')->default(true)->after('class_order');
            $table->softDeletes();

            $table->dropIndex(['name']);
            $table->dropColumn(['level', 'section']);

            $table->unique('name');
            $table->unique('class_order');
            $table->index('is_active');
        });

        // Change foreign key constraints from cascade to restrict
        // This prevents classroom deletion when child records exist
        Schema::table('teacher_subject_assignments', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->foreign('classroom_id')
                  ->references('id')->on('classrooms')
                  ->onDelete('restrict');
        });

        Schema::table('lesson_notes', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->foreign('classroom_id')
                  ->references('id')->on('classrooms')
                  ->onDelete('restrict');
        });

        Schema::table('class_teacher_assignments', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->foreign('class_id')
                  ->references('id')->on('classrooms')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert foreign key constraints back to cascade
        Schema::table('class_teacher_assignments', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->foreign('class_id')
                  ->references('id')->on('classrooms')
                  ->onDelete('cascade');
        });

        Schema::table('lesson_notes', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->foreign('classroom_id')
                  ->references('id')->on('classrooms')
                  ->onDelete('cascade');
        });

        Schema::table('teacher_subject_assignments', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->foreign('classroom_id')
                  ->references('id')->on('classrooms')
                  ->onDelete('cascade');
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropUnique(['name']);
            $table->dropUnique(['class_order']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['class_order', 'is_active']);

            $table->string('level')->nullable();
            $table->string('section')->nullable();
            $table->index('name');
        });
    }
};
