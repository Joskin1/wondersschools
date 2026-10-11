<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_teacher_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('class_teacher_assignments', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('class_arm_id')->constrained('departments')->nullOnDelete();
            }

            // Drop legacy 1-per-class constraint so different teachers can be assigned to different arms/departments within the same class level
            $table->dropUnique('unique_class_teacher_per_session');
        });
    }

    public function down(): void
    {
        Schema::table('class_teacher_assignments', function (Blueprint $table) {
            $table->unique(['class_id', 'session_id'], 'unique_class_teacher_per_session');

            if (Schema::hasColumn('class_teacher_assignments', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
        });
    }
};
