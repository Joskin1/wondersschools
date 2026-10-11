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
        if (!Schema::hasTable('class_arms')) {
            Schema::create('class_arms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
                $table->string('name', 100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['classroom_id', 'name']);
            });
        }

        if (Schema::hasTable('student_enrollments')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                if (!Schema::hasColumn('student_enrollments', 'class_arm_id')) {
                    $table->foreignId('class_arm_id')->nullable()->constrained('class_arms')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('class_teacher_assignments')) {
            Schema::table('class_teacher_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('class_teacher_assignments', 'class_arm_id')) {
                    $table->foreignId('class_arm_id')->nullable()->constrained('class_arms')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('class_teacher_assignments')) {
            Schema::table('class_teacher_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('class_teacher_assignments', 'class_arm_id')) {
                    $table->dropForeign(['class_arm_id']);
                    $table->dropColumn('class_arm_id');
                }
            });
        }

        if (Schema::hasTable('student_enrollments')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                if (Schema::hasColumn('student_enrollments', 'class_arm_id')) {
                    $table->dropForeign(['class_arm_id']);
                    $table->dropColumn('class_arm_id');
                }
            });
        }

        Schema::dropIfExists('class_arms');
    }
};
