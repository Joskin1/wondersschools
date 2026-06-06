<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Session;
use App\Models\Term;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\ScoreHead;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\TeacherSubjectAssignment;
use App\Models\Score;
use App\Services\ResultCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedScoresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:scores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed scores and calculate results for 2 academic sessions and 3 terms across all tenants';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found in landlord database.');
            return 1;
        }

        $this->info("Found {$tenants->count()} tenants. Starting E2E scores seeding...");

        foreach ($tenants as $tenant) {
            $this->comment("=========================================");
            $this->info("Processing Tenant: {$tenant->id} ({$tenant->name})");
            $this->comment("=========================================");

            try {
                // Initialize tenancy
                tenancy()->initialize($tenant);

                // Run seeders if basic data is missing
                $this->ensureBasicData();

                // Get sessions: 2024-2025 and 2025-2026
                $sessions = $this->ensureSessions();

                $classrooms = Classroom::all();
                $subjects = Subject::all();

                if ($classrooms->isEmpty() || $subjects->isEmpty()) {
                    $this->error("No classrooms or subjects found for tenant: {$tenant->id}");
                    continue;
                }

                $teacher = User::where('role', 'teacher')->first();
                if (!$teacher) {
                    $teacher = User::create([
                        'name' => 'Seeded Teacher',
                        'email' => 'teacher.seeded@wonders.test',
                        'password' => bcrypt('password'),
                        'role' => 'teacher',
                        'is_active' => true,
                    ]);
                }

                // Loop through sessions and terms chronologically
                foreach ($sessions as $session) {
                    $this->comment("-----------------------------------------");
                    $this->info("Session: {$session->name}");
                    $this->comment("-----------------------------------------");

                    // Ensure students exist and enroll 5 students per classroom for this session
                    $studentSubset = $this->ensureEnrolledStudents($session, $classrooms);

                    // Fetch terms in chronological order
                    $terms = $session->terms()->orderBy('order')->get();

                    foreach ($terms as $term) {
                        $this->info("  Term: {$term->name} (Order: {$term->order})");

                        // 1. Prepare score structures and teacher assignments
                        foreach ($classrooms as $classroom) {
                            // Link 5 random subjects to classroom if not already set
                            if ($classroom->subjects()->count() < 5) {
                                $classroom->subjects()->syncWithoutDetaching(
                                    $subjects->random(min(5, $subjects->count()))->pluck('id')
                                );
                            }

                            // Ensure ClassScoreStructure exists and is locked
                            $structure = ClassScoreStructure::firstOrCreate([
                                'class_id' => $classroom->id,
                                'session_id' => $session->id,
                                'term_id' => $term->id,
                            ], [
                                'total_score' => 100,
                                'locked' => true,
                            ]);
                            $structure->update(['locked' => true]);

                            // Seed structure items override
                            $scoreHeads = ScoreHead::all();
                            foreach ($scoreHeads as $scoreHead) {
                                ClassScoreStructureItem::firstOrCreate([
                                    'class_score_structure_id' => $structure->id,
                                    'score_head_id' => $scoreHead->id,
                                ]);
                            }

                            // Assign teacher to subjects for this term/session
                            foreach ($classroom->subjects as $subject) {
                                TeacherSubjectAssignment::firstOrCreate([
                                    'subject_id' => $subject->id,
                                    'classroom_id' => $classroom->id,
                                    'session_id' => $session->id,
                                    'term_id' => $term->id,
                                ], [
                                    'teacher_id' => $teacher->id,
                                ]);
                            }
                        }

                        // 2. Seed Scores
                        $scoreCount = 0;
                        foreach ($classrooms as $classroom) {
                            $students = $studentSubset[$classroom->id] ?? [];
                            foreach ($students as $student) {
                                foreach ($classroom->subjects as $subject) {
                                    $scoresToSeed = [
                                        'Classwork' => rand(6, 10),
                                        'Test 1' => rand(5, 10),
                                        'Exam' => rand(40, 80),
                                    ];

                                    foreach ($scoresToSeed as $scoreHeadName => $value) {
                                        $scoreHead = ScoreHead::where('name', $scoreHeadName)->first();
                                        if ($scoreHead) {
                                            Score::updateOrCreate([
                                                'student_id' => $student->id,
                                                'classroom_id' => $classroom->id,
                                                'subject_id' => $subject->id,
                                                'score_head_id' => $scoreHead->id,
                                                'session_id' => $session->id,
                                                'term_id' => $term->id,
                                            ], [
                                                'teacher_id' => $teacher->id,
                                                'score' => $value,
                                            ]);
                                            $scoreCount++;
                                        }
                                    }
                                }
                            }
                        }

                        $this->info("    ✓ Seeded {$scoreCount} score items for {$classrooms->count()} classrooms.");

                        // 3. Calculate Results
                        $calcService = app(ResultCalculationService::class);
                        foreach ($classrooms as $classroom) {
                            $calcService->calculateForClass($classroom->id, $session->id, $term->id);
                        }

                        $this->info("    ✓ Calculated and finalized results.");
                    }
                }

                $this->info("✅ Successfully processed and seeded tenant: {$tenant->id}");

            } catch (\Throwable $e) {
                $this->error("Error seeding tenant {$tenant->id}: " . $e->getMessage());
                $this->error($e->getTraceAsString());
            } finally {
                // End tenant context
                tenancy()->end();
            }
        }

        $this->info('All tenant E2E score seeding completed successfully!');
        return 0;
    }

    /**
     * Ensure basic tables have default seeds.
     */
    private function ensureBasicData(): void
    {
        if (Classroom::count() === 0) {
            $this->call('db:seed', ['--class' => \Database\Seeders\ClassroomSeeder::class]);
        }
        if (Subject::count() === 0) {
            $this->call('db:seed', ['--class' => \Database\Seeders\SubjectSeeder::class]);
        }
        if (ScoreHead::count() === 0) {
            $this->call('db:seed', ['--class' => \Database\Seeders\ScoreHeadSeeder::class]);
        }
        if (Student::count() === 0) {
            $this->call('db:seed', ['--class' => \Database\Seeders\StudentSeeder::class]);
        }
    }

    /**
     * Ensure academic sessions 2024-2025 and 2025-2026 exist.
     *
     * @return array<Session>
     */
    private function ensureSessions(): array
    {
        $sessions = [];

        foreach ([2024, 2025] as $year) {
            $name = "{$year}-" . ($year + 1);
            $session = Session::where('name', $name)->first();
            if (!$session) {
                $session = Session::createWithTerms($year);
            }
            $sessions[] = $session;
        }

        return $sessions;
    }

    /**
     * Ensure 5 students are enrolled in each classroom for the given session.
     *
     * @return array<int, array<Student>> Map of classroom_id to Student array
     */
    private function ensureEnrolledStudents(Session $session, $classrooms): array
    {
        $classroomStudents = [];

        foreach ($classrooms as $classroom) {
            // Fetch students already enrolled
            $enrolled = StudentEnrollment::where('classroom_id', $classroom->id)
                ->where('session_id', $session->id)
                ->with('student')
                ->limit(5)
                ->get()
                ->map(fn($e) => $e->student)
                ->filter()
                ->all();

            if (count($enrolled) < 5) {
                $needed = 5 - count($enrolled);

                // Fetch students not enrolled in this session yet
                $pool = Student::whereNotIn('id', function ($query) use ($session) {
                    $query->select('student_id')
                        ->from('student_enrollments')
                        ->where('session_id', $session->id);
                })
                ->limit($needed)
                ->get();

                // If pool is empty, grab any students
                if ($pool->isEmpty()) {
                    $pool = Student::limit($needed)->get();
                }

                foreach ($pool as $student) {
                    StudentEnrollment::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'session_id' => $session->id,
                        ],
                        [
                            'classroom_id' => $classroom->id,
                        ]
                    );
                    $enrolled[] = $student;
                }
            }

            $classroomStudents[$classroom->id] = array_slice($enrolled, 0, 5);
        }

        return $classroomStudents;
    }
}
