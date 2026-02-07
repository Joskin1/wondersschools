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
        Schema::create('result_options', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name
            $table->string('key'); // Unique key for the option
            $table->text('value'); // The actual value (can be string, number, json, etc.)
            $table->enum('type', ['string', 'number', 'boolean', 'json'])->default('string');
            $table->string('scope')->nullable(); // e.g., 'general', 'printing', 'computation'
            $table->timestamps();
            
            $table->index(['key', 'scope']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_options');
    }
};
