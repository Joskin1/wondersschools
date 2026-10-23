<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_midterm_config_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_midterm_config_id')->constrained('class_midterm_configs')->cascadeOnDelete();
            $table->foreignId('score_head_id')->constrained('score_heads')->restrictOnDelete();
            $table->unsignedTinyInteger('max_score_override')->nullable();
            $table->timestamps();

            $table->unique(['class_midterm_config_id', 'score_head_id'], 'unique_class_midterm_head');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_midterm_config_heads');
    }
};
