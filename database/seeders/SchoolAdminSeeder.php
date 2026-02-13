<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update school admin
        User::updateOrCreate(
            ['email' => 'admin@wonders.test'],
            [
                'name' => 'School Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('School Admin created/updated successfully!');
        $this->command->info('Email: admin@wonders.test');
        $this->command->info('Password: password');
        $this->command->info('Role: admin');
        $this->command->info('Access: http://wonders.test/admin');
    }
}
