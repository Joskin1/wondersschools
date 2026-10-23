<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_scoring_snapshots', function (Blueprint $table) {
            if (! Schema::hasColumn('term_scoring_snapshots', 'absent_policy')) {
                $table->string('absent_policy', 30)->default('count_as_zero')->after('calculation_method');
            }
            if (! Schema::hasColumn('term_scoring_snapshots', 'result_decimal_places')) {
                $table->unsignedTinyInteger('result_decimal_places')->default(2)->after('absent_policy');
            }
            if (! Schema::hasColumn('term_scoring_snapshots', 'result_rounding_rule')) {
                $table->string('result_rounding_rule', 30)->default('half_up')->after('result_decimal_places');
            }
        });
    }

    public function down(): void
    {
        Schema::table('term_scoring_snapshots', function (Blueprint $table) {
            $table->dropColumn(['absent_policy', 'result_decimal_places', 'result_rounding_rule']);
        });
    }
};
