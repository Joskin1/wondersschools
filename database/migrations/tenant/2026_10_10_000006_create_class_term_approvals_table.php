<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_term_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->unsignedSmallInteger('times_school_opened')->nullable();
            $table->string('next_term_begins')->nullable();
            $table->string('status', 20)->default('pending'); // 'pending', 'approved'
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['classroom_id', 'session_id', 'term_id'],
                'unique_class_term_approval'
            );

            $table->index(['session_id', 'term_id'], 'class_term_approvals_session_term_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_term_approvals');
    }
};
