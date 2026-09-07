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
        Schema::table('lesson_note_versions', function (Blueprint $table) {
            $table->string('submission_type')->default('file')->after('lesson_note_id');
            $table->string('title')->nullable()->after('submission_type');
            $table->longText('content')->nullable()->after('title');
            $table->json('images')->nullable()->after('content');

            // Make file-specific columns nullable for written notes
            $table->string('file_path')->nullable()->change();
            $table->string('file_name')->nullable()->change();
            $table->unsignedBigInteger('file_size')->nullable()->change();
            $table->string('file_hash', 64)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_note_versions', function (Blueprint $table) {
            $table->dropColumn(['submission_type', 'title', 'content', 'images']);
            $table->string('file_path')->nullable(false)->change();
            $table->string('file_name')->nullable(false)->change();
            $table->unsignedBigInteger('file_size')->nullable(false)->change();
            $table->string('file_hash', 64)->nullable(false)->change();
        });
    }
};
