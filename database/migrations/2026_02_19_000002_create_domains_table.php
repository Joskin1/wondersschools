<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('domain')->unique();
            $table->string('tenant_id'); // Foreign key to schools.id (string)
            $table->boolean('is_primary')->default(false);
            
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('schools')->onUpdate('cascade')->onDelete('cascade');
            
            // Index for school-based domain lookups
            $table->index(['tenant_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
