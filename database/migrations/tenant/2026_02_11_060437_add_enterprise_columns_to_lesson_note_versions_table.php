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
            // Security & Validation
            $table->string('virus_scan_status')->nullable()->after('file_hash');
            $table->string('mime_type')->nullable()->after('virus_scan_status');
            $table->boolean('is_duplicate')->default(false)->after('mime_type');
            
            // Metadata & Versioning
            $table->json('metadata')->nullable()->after('is_duplicate');
            $table->timestamp('file_modified_at')->nullable()->after('metadata');
            $table->string('original_filename')->nullable()->after('file_modified_at');
            
            // CDN & Storage
            $table->boolean('cdn_available')->default(true)->after('original_filename');
            $table->string('thumbnail_path')->nullable()->after('cdn_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_note_versions', function (Blueprint $table) {
            $table->dropColumn([
                'virus_scan_status',
                'mime_type',
                'is_duplicate',
                'metadata',
                'file_modified_at',
                'original_filename',
                'cdn_available',
                'thumbnail_path',
            ]);
        });
    }
};
