<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('admission_number')->nullable()->unique()->after('id');
        });

        DB::table('students')
            ->whereNull('admission_number')
            ->orderBy('id')
            ->select('id')
            ->get()
            ->each(function ($student): void {
                DB::table('students')
                    ->where('id', $student->id)
                    ->update([
                        'admission_number' => 'ADM'.now()->format('Y').str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['admission_number']);
            $table->dropColumn('admission_number');
        });
    }
};
