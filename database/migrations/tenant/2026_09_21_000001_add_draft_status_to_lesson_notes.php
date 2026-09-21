<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE lesson_notes DROP CONSTRAINT IF EXISTS lesson_notes_status_check;");
            DB::statement("ALTER TABLE lesson_notes ADD CONSTRAINT lesson_notes_status_check CHECK (status IN ('draft', 'pending', 'approved', 'rejected'));");
            DB::statement("ALTER TABLE lesson_notes ALTER COLUMN status SET DEFAULT 'draft';");

            DB::statement("ALTER TABLE lesson_note_versions DROP CONSTRAINT IF EXISTS lesson_note_versions_status_check;");
            DB::statement("ALTER TABLE lesson_note_versions ADD CONSTRAINT lesson_note_versions_status_check CHECK (status IN ('draft', 'pending', 'approved', 'rejected'));");
            DB::statement("ALTER TABLE lesson_note_versions ALTER COLUMN status SET DEFAULT 'draft';");
        } elseif ($driver === 'sqlite') {
            Schema::table('lesson_notes', function (Blueprint $table) {
                $table->string('status')->default('draft')->change();
            });
            Schema::table('lesson_note_versions', function (Blueprint $table) {
                $table->string('status')->default('draft')->change();
            });
        } else {
            Schema::table('lesson_notes', function (Blueprint $table) {
                $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft')->change();
            });
            Schema::table('lesson_note_versions', function (Blueprint $table) {
                $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE lesson_notes DROP CONSTRAINT IF EXISTS lesson_notes_status_check;");
            DB::statement("ALTER TABLE lesson_notes ADD CONSTRAINT lesson_notes_status_check CHECK (status IN ('pending', 'approved', 'rejected'));");
            DB::statement("ALTER TABLE lesson_notes ALTER COLUMN status SET DEFAULT 'pending';");

            DB::statement("ALTER TABLE lesson_note_versions DROP CONSTRAINT IF EXISTS lesson_note_versions_status_check;");
            DB::statement("ALTER TABLE lesson_note_versions ADD CONSTRAINT lesson_note_versions_status_check CHECK (status IN ('pending', 'approved', 'rejected'));");
            DB::statement("ALTER TABLE lesson_note_versions ALTER COLUMN status SET DEFAULT 'pending';");
        } elseif ($driver === 'sqlite') {
            Schema::table('lesson_notes', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
            Schema::table('lesson_note_versions', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        } else {
            Schema::table('lesson_notes', function (Blueprint $table) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
            });
            Schema::table('lesson_note_versions', function (Blueprint $table) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
            });
        }
    }
};
