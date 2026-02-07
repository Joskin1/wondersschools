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
        Schema::create('school_authorities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 'Principal', 'Vice Principal', etc.
            $table->string('title')->nullable(); // 'Dr.', 'Mr.', 'Mrs.', etc.
            $table->string('signature_path')->nullable(); // Path to signature image
            $table->integer('signature_top')->default(0); // Y coordinate for signature
            $table->integer('signature_left')->default(0); // X coordinate for signature
            $table->integer('comment_top')->default(0); // Y coordinate for comment
            $table->integer('comment_left')->default(0); // X coordinate for comment
            $table->integer('display_order')->default(0); // Order of display
            $table->timestamps();
            
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_authorities');
    }
};
