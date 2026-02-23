<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the users, password_reset_tokens, and sessions tables in the
 * central (landlord) database so that the sudo panel at wonders.test/sudo
 * can authenticate without initialising any tenant.
 *
 * Tenant DBs have their own identical users table (via tenant migrations).
 */
return new class extends Migration
{
    /**
     * Run on the landlord (central) connection only.
     * This keeps the central users table isolated from the tenant users tables
     * that are created by database/migrations/tenant/0001_01_01_000000_create_users_table.php.
     * In tests both migration paths are loaded but they target different
     * connections ('landlord' SQLite vs default SQLite), so there is no conflict.
     */
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::connection('landlord')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('role', ['sudo', 'admin', 'teacher', 'student'])->default('student');
            $table->boolean('is_active')->default(true);
            $table->timestamp('registration_completed_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::connection('landlord')->create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::connection('landlord')->create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('sessions');
        Schema::connection('landlord')->dropIfExists('password_reset_tokens');
        Schema::connection('landlord')->dropIfExists('users');
    }
};
