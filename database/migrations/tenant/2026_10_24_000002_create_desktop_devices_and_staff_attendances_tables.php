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
        Schema::create('desktop_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('device_token_hash', 64)->unique();
            $table->string('device_token_prefix', 16)->index();
            $table->foreignId('paired_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('last_ip', 45)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->time('arrival_time')->nullable();
            $table->time('departure_time')->nullable();
            $table->string('remark', 255)->nullable();
            $table->foreignId('recorded_by_device_id')->nullable()->constrained('desktop_devices')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'date'], 'unique_staff_daily_attendance');
            $table->index(['session_id', 'term_id', 'date'], 'staff_attendance_term_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('desktop_devices');
    }
};
