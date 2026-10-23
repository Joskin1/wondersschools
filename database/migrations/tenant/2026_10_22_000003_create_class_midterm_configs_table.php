<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_midterm_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete()->unique();
            $table->unsignedTinyInteger('midterm_weight');
            $table->string('calculation_method', 50)->default('scale_to_weight');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_midterm_configs');
    }
};
