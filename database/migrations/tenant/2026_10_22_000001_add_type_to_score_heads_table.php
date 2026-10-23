<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('score_heads', function (Blueprint $table) {
            $table->string('type', 20)->default('exam')->after('max_score')->index();
        });
    }

    public function down(): void
    {
        Schema::table('score_heads', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
