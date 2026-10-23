<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Split the single `name` column on the tenant users table into
 * `surname`, `first_name`, and `other_name`.
 *
 * Data migration: first word → surname, second word → first_name,
 * remaining words → other_name (Nigerian naming convention).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->after('id')->default('');
            $table->string('first_name')->after('surname')->default('');
            $table->string('other_name')->nullable()->after('first_name');
        });

        // Migrate existing data
        DB::table('users')->orderBy('id')->each(function ($user) {
            $parts = array_values(array_filter(preg_split('/\s+/', trim(str_replace(',', ' ', (string) $user->name)))));

            $surname = $parts[0] ?? '';
            $firstName = $parts[1] ?? '';
            $otherName = count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : null;

            if (count($parts) === 1) {
                $firstName = '';
            }

            DB::table('users')->where('id', $user->id)->update([
                'surname' => $surname,
                'first_name' => $firstName,
                'other_name' => $otherName,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id')->default('');
        });

        DB::table('users')->orderBy('id')->each(function ($user) {
            $name = trim(implode(' ', array_filter([$user->surname, $user->first_name, $user->other_name])));

            DB::table('users')->where('id', $user->id)->update([
                'name' => $name,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['surname', 'first_name', 'other_name']);
        });
    }
};
