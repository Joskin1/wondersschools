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
        Schema::table('students', function (Blueprint $table) {
            $table->text('primary_biometric_template')->nullable()->after('profile_picture');
            $table->text('backup_biometric_template')->nullable()->after('primary_biometric_template');
            $table->timestamp('biometric_enrolled_at')->nullable()->after('backup_biometric_template');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('primary_biometric_template')->nullable()->after('email');
            $table->text('backup_biometric_template')->nullable()->after('primary_biometric_template');
            $table->timestamp('biometric_enrolled_at')->nullable()->after('backup_biometric_template');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->time('departure_time')->nullable()->after('arrival_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('departure_time');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'primary_biometric_template',
                'backup_biometric_template',
                'biometric_enrolled_at',
            ]);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'primary_biometric_template',
                'backup_biometric_template',
                'biometric_enrolled_at',
            ]);
        });
    }
};
