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
        Schema::table('term_results', function (Blueprint $table) {
            if (! Schema::hasColumn('term_results', 'affective_domain')) {
                $table->json('affective_domain')->nullable()->after('principal_comment');
            }
            if (! Schema::hasColumn('term_results', 'psychomotor_skills')) {
                $table->json('psychomotor_skills')->nullable()->after('affective_domain');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('term_results', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('term_results', 'affective_domain')) {
                $cols[] = 'affective_domain';
            }
            if (Schema::hasColumn('term_results', 'psychomotor_skills')) {
                $cols[] = 'psychomotor_skills';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
