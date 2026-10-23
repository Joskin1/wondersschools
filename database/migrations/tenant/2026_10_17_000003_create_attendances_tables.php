<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_weekly_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('class_arm_id')->nullable()->constrained('class_arms')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->unsignedTinyInteger('week_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('in_progress'); // in_progress, submitted, locked
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['classroom_id', 'class_arm_id', 'department_id', 'session_id', 'term_id', 'week_number'],
                'unique_weekly_class_attendance'
            );
            $table->index(['session_id', 'term_id', 'week_number'], 'weekly_attendance_term_week_idx');
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_weekly_attendance_id')->nullable()->constrained('class_weekly_attendances')->nullOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('class_arm_id')->nullable()->constrained('class_arms')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->date('date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['classroom_id', 'class_arm_id', 'department_id', 'session_id', 'term_id', 'date'],
                'unique_daily_class_attendance'
            );
            $table->index(['session_id', 'term_id', 'date'], 'attendance_term_date_idx');
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->string('remark', 255)->nullable();
            $table->time('arrival_time')->nullable();
            $table->timestamps();

            $table->unique(['attendance_id', 'student_id'], 'unique_attendance_student_record');
            $table->index(['student_id', 'status'], 'attendance_record_student_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('class_weekly_attendances');
    }
};
