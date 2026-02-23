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
        Schema::create('lesson_note_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_note_id')->constrained('lesson_notes')->onDelete('cascade');
            $table->string('file_path'); // S3/R2/GCS path
            $table->string('file_name');
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('file_hash', 64); // SHA-256 for deduplication
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->text('admin_comment')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Index for version history queries
            $table->index('lesson_note_id');
            
            // Index for deduplication checks
            $table->index('file_hash');
            
            // Index for status filtering
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_note_versions');
    }
};
