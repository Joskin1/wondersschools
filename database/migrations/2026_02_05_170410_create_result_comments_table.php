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
        Schema::create('result_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('comment_authority_scope_id')->nullable(); // Reference to school_authorities
            $table->text('comment_text');
            $table->enum('comment_type', ['teacher', 'principal', 'custom'])->default('teacher');
            $table->timestamps();
            
            $table->index(['result_id', 'comment_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_comments');
    }
};
