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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_primary_admin')->default(false)->after('role');
            $table->string('admin_designation')->nullable()->after('is_primary_admin');
            $table->json('allowed_admin_pages')->nullable()->after('admin_designation');
        });

        // Set the primary admin flag on the initial school admin (admin@wonders.test or first admin user)
        $primaryAdmin = DB::table('users')->where('email', 'admin@wonders.test')->first()
            ?? DB::table('users')->where('role', 'admin')->orderBy('id')->first();

        if ($primaryAdmin) {
            DB::table('users')->where('id', $primaryAdmin->id)->update([
                'is_primary_admin' => true,
                'admin_designation' => 'Principal',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_primary_admin', 'admin_designation', 'allowed_admin_pages']);
        });
    }
};
