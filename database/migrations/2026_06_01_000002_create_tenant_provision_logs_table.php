<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::connection('landlord')->create('tenant_provision_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->string('event_type', 50);      // database_creation, migration, seeding, validation, activation, chain_failed
            $table->string('status', 20);           // started, success, failed
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('tenant_provision_logs');
    }
};
