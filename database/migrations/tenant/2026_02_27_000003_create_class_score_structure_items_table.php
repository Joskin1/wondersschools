<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_score_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_score_structure_id')
                  ->constrained('class_score_structures')
                  ->cascadeOnDelete();
            $table->foreignId('score_head_id')->constrained('score_heads')->cascadeOnDelete();
            $table->unsignedTinyInteger('max_score_override')->nullable();
            $table->timestamps();

            $table->unique(
                ['class_score_structure_id', 'score_head_id'],
                'unique_structure_item'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_score_structure_items');
    }
};
