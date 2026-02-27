<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_score_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->unsignedSmallInteger('total_score')->default(0); // cached sum
            $table->boolean('locked')->default(false);
            $table->timestamps();

            $table->unique(['class_id', 'session_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_score_structures');
    }
};
