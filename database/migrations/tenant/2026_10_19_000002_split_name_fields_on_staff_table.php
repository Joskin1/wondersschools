<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Split the single `name` column on the staff table into
 * `surname`, `first_name`, and `other_name`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('surname')->after('id')->default('');
            $table->string('first_name')->after('surname')->default('');
            $table->string('other_name')->nullable()->after('first_name');
        });

        // Migrate existing data
        DB::table('staff')->orderBy('id')->each(function ($staff) {
            $parts = array_values(array_filter(preg_split('/\s+/', trim(str_replace(',', ' ', (string) $staff->name)))));

            $surname = $parts[0] ?? '';
            $firstName = $parts[1] ?? '';
            $otherName = count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : null;

            if (count($parts) === 1) {
                $firstName = '';
            }

            DB::table('staff')->where('id', $staff->id)->update([
                'surname' => $surname,
                'first_name' => $firstName,
                'other_name' => $otherName,
            ]);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('name')->after('id')->default('');
        });

        DB::table('staff')->orderBy('id')->each(function ($staff) {
            $name = trim(implode(' ', array_filter([$staff->surname, $staff->first_name, $staff->other_name])));

            DB::table('staff')->where('id', $staff->id)->update([
                'name' => $name,
            ]);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['surname', 'first_name', 'other_name']);
        });
    }
};
