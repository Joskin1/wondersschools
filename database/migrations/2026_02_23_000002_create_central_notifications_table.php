<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the notifications table in the central (landlord) database so that
 * the Filament sudo panel at wonders.test/sudo can use databaseNotifications()
 * without a missing-table error.
 *
 * Tenant DBs have their own identical notifications table created by the
 * standard Laravel notifications migration in database/migrations/tenant/.
 */
return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::connection('landlord')->create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('notifications');
    }
};
