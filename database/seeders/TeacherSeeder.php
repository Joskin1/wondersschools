<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    /** Number of teachers to create */
    private const COUNT = 30;

    public function run(): void
    {
        for ($i = 1; $i <= self::COUNT; $i++) {
            User::firstOrCreate(
                ['email' => "teacher{$i}@wonders.test"],
                [
                    'name' => "Teacher {$i}",
                    'password' => bcrypt('password'),
                    'role' => 'teacher',
                    'is_active' => true,
                    'registration_completed_at' => now(),
                ]
            );
        }

        $this->command->info('👩‍🏫 30 teachers seeded (password: password).');
    }
}
