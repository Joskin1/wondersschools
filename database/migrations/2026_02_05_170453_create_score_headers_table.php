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
        Schema::create('score_headers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 'CA1', 'CA2', 'Exam', 'Project'
            $table->decimal('max_score', 5, 2); // 20, 20, 60, etc.
            $table->foreignId('school_class_id')->constrained('classrooms')->cascadeOnDelete();
            $table->string('session'); // '2023/2024'
            $table->integer('term'); // 1, 2, or 3
            $table->integer('display_order')->default(0); // For rendering order
            $table->timestamps();
            
            $table->unique(['name', 'school_class_id', 'session', 'term'], 'unique_score_header');
            $table->index(['school_class_id', 'session', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_headers');
    }
};
