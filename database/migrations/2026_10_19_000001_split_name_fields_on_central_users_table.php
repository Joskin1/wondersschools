<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Split the single `name` column on the central (landlord) users table into
 * `surname`, `first_name`, and `other_name`.
 */
return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::connection('landlord')->table('users', function (Blueprint $table) {
            $table->string('surname')->after('id')->default('');
            $table->string('first_name')->after('surname')->default('');
            $table->string('other_name')->nullable()->after('first_name');
        });

        // Migrate existing data
        DB::connection('landlord')->table('users')->orderBy('id')->each(function ($user) {
            $parts = array_values(array_filter(preg_split('/\s+/', trim(str_replace(',', ' ', (string) $user->name)))));

            $surname = $parts[0] ?? '';
            $firstName = $parts[1] ?? '';
            $otherName = count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : null;

            if (count($parts) === 1) {
                $firstName = '';
            }

            DB::connection('landlord')->table('users')->where('id', $user->id)->update([
                'surname' => $surname,
                'first_name' => $firstName,
                'other_name' => $otherName,
            ]);
        });

        Schema::connection('landlord')->table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('users', function (Blueprint $table) {
            $table->string('name')->after('id')->default('');
        });

        DB::connection('landlord')->table('users')->orderBy('id')->each(function ($user) {
            $name = trim(implode(' ', array_filter([$user->surname, $user->first_name, $user->other_name])));

            DB::connection('landlord')->table('users')->where('id', $user->id)->update([
                'name' => $name,
            ]);
        });

        Schema::connection('landlord')->table('users', function (Blueprint $table) {
            $table->dropColumn(['surname', 'first_name', 'other_name']);
        });
    }
};
