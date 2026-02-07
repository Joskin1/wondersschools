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
        Schema::table('classroom_subject_teacher', function (Blueprint $table) {
            // Add session field if it doesn't exist
            if (!Schema::hasColumn('classroom_subject_teacher', 'session')) {
                $table->string('session')->after('staff_id')->default('2024/2025');
            }
        });
        
        Schema::table('classroom_subject_teacher', function (Blueprint $table) {
            // Add indexes for performance
            if (!Schema::hasIndex('classroom_subject_teacher', 'classroom_subject_teacher_staff_id_session_index')) {
                $table->index(['staff_id', 'session']);
            }
            if (!Schema::hasIndex('classroom_subject_teacher', 'classroom_subject_teacher_classroom_id_subject_id_index')) {
                $table->index(['classroom_id', 'subject_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classroom_subject_teacher', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['staff_id', 'session']);
            $table->dropIndex(['classroom_id', 'subject_id']);
            
            // Drop new unique constraint
            $table->dropUnique('unique_teacher_assignment');
            
            // Restore old unique constraint
            $table->unique(['classroom_id', 'subject_id', 'staff_id'], 'class_subject_teacher_unique');
            
            // Drop session column
            $table->dropColumn('session');
        });
    }
};
