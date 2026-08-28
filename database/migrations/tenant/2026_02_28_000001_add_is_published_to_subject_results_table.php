<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_results', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('subject_results', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }
};
