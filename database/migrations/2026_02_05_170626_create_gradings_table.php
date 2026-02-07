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
        Schema::create('gradings', function (Blueprint $table) {
            $table->id();
            $table->string('letter'); // 'A', 'B', 'C', etc.
            $table->decimal('lower_bound', 5, 2); // 70.00
            $table->decimal('upper_bound', 5, 2); // 100.00
            $table->string('remark')->nullable(); // 'Excellent', 'Very Good', etc.
            $table->foreignId('subject_id')->nullable()->constrained()->cascadeOnDelete(); // null for global grading
            $table->string('session')->nullable(); // '2023/2024' or null for all sessions
            $table->timestamps();
            
            $table->index(['lower_bound', 'upper_bound']);
            $table->index(['subject_id', 'session']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gradings');
    }
};
