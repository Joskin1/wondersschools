<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH', 'description' => 'Mathematics and Numeracy'],
            ['name' => 'English Language', 'code' => 'ENG', 'description' => 'English Language and Literature'],
            ['name' => 'Physics', 'code' => 'PHY', 'description' => 'Physics and Physical Sciences'],
            ['name' => 'Chemistry', 'code' => 'CHEM', 'description' => 'Chemistry and Chemical Sciences'],
            ['name' => 'Biology', 'code' => 'BIO', 'description' => 'Biology and Life Sciences'],
            ['name' => 'Economics', 'code' => 'ECON', 'description' => 'Economics and Business Studies'],
            ['name' => 'Government', 'code' => 'GOV', 'description' => 'Government and Civics'],
            ['name' => 'Geography', 'code' => 'GEO', 'description' => 'Geography and Environmental Studies'],
            ['name' => 'History', 'code' => 'HIST', 'description' => 'History and Social Studies'],
            ['name' => 'Computer Science', 'code' => 'CS', 'description' => 'Computer Science and ICT'],
            ['name' => 'Agricultural Science', 'code' => 'AGRIC', 'description' => 'Agricultural Science'],
            ['name' => 'Civic Education', 'code' => 'CIVIC', 'description' => 'Civic Education'],
            ['name' => 'Christian Religious Studies', 'code' => 'CRS', 'description' => 'Christian Religious Studies'],
            ['name' => 'Islamic Religious Studies', 'code' => 'IRS', 'description' => 'Islamic Religious Studies'],
            ['name' => 'French', 'code' => 'FRE', 'description' => 'French Language'],
            ['name' => 'Yoruba', 'code' => 'YOR', 'description' => 'Yoruba Language'],
            ['name' => 'Igbo', 'code' => 'IGB', 'description' => 'Igbo Language'],
            ['name' => 'Hausa', 'code' => 'HAU', 'description' => 'Hausa Language'],
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate(
                ['code' => $subject['code']],
                $subject
            );
        }

        $this->command->info('Subjects seeded successfully!');
    }
}
