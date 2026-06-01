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
        Schema::connection('landlord')->table('tenants', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->index()->after('primary_color');
            $table->timestamp('last_provisioned_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('tenants', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'last_provisioned_at']);
        });
    }
};
