<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SudoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update sudo user
        User::updateOrCreate(
            ['email' => 'joskinjoseph1@gmail.com'],
            [
                'name' => 'Joskin Joseph',
                'password' => Hash::make('password'),
                'role' => 'sudo',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Sudo user created/updated successfully!');
        $this->command->info('Email: joskinjoseph1@gmail.com');
        $this->command->info('Password: password');
        $this->command->info('Role: sudo');
        $this->command->info('Access: http://wonders.test/sudo');
    }
}
