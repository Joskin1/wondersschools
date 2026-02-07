<?php

namespace Tests\Unit\Services;

use App\Models\Classroom;
use App\Models\Grading;
use App\Models\Result;
use App\Models\Score;
use App\Models\ScoreHeader;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ResultComputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultComputationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResultComputationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ResultComputationService::class);
    }

    public function test_computes_student_result_successfully(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        
        // Create score headers
        $headers = ScoreHeader::factory()
            ->standard($classroom->id, '2023/2024', 1)
            ->count(3)
            ->create();
        
        // Create scores
        foreach ($headers as $header) {
            Score::factory()->create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'score_header_id' => $header->id,
                'session' => '2023/2024',
                'term' => 1,
                'value' => 20,
            ]);
        }
        
        // Create grading scheme
        Grading::factory()->standard()->create();

        // Act
        $result = $this->service->computeStudentResult(
            $student->id,
            $classroom->id,
            '2023/2024',
            1
        );

        // Assert
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals($student->id, $result->student_id);
        $this->assertEquals($classroom->id, $result->classroom_id);
        $this->assertEquals('2023/2024', $result->session);
        $this->assertEquals(1, $result->term);
        $this->assertNotNull($result->cache_key);
        $this->assertNotNull($result->result_data);
    }

    public function test_computes_results_for_entire_classroom(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $students = Student::factory()->count(5)->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        
        $headers = ScoreHeader::factory()
            ->standard($classroom->id, '2023/2024', 1)
            ->count(3)
            ->create();
        
        foreach ($students as $student) {
            foreach ($headers as $header) {
                Score::factory()->create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'score_header_id' => $header->id,
                    'session' => '2023/2024',
                    'term' => 1,
                    'value' => fake()->numberBetween(10, 30),
                ]);
            }
        }
        
        Grading::factory()->standard()->create();

        // Act
        $results = $this->service->computeResults(
            $classroom->id,
            '2023/2024',
            1
        );

        // Assert
        $this->assertCount(5, $results);
        $this->assertTrue($results->every(fn($r) => $r instanceof Result));
        
        // Check positions are assigned
        $positions = $results->pluck('position')->sort()->values();
        $this->assertEquals([1, 2, 3, 4, 5], $positions->toArray());
    }

    public function test_injects_positions_with_tie_logic(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $students = Student::factory()->count(3)->create(['classroom_id' => $classroom->id]);
        
        // Create results with same scores for tie
        $results = collect([
            Result::factory()->create([
                'student_id' => $students[0]->id,
                'classroom_id' => $classroom->id,
                'average_score' => 85.5,
                'session' => '2023/2024',
                'term' => 1,
            ]),
            Result::factory()->create([
                'student_id' => $students[1]->id,
                'classroom_id' => $classroom->id,
                'average_score' => 85.5, // Same score - should tie
                'session' => '2023/2024',
                'term' => 1,
            ]),
            Result::factory()->create([
                'student_id' => $students[2]->id,
                'classroom_id' => $classroom->id,
                'average_score' => 75.0,
                'session' => '2023/2024',
                'term' => 1,
            ]),
        ]);

        // Act
        $sortedResults = $this->service->computeResults(
            $classroom->id,
            '2023/2024',
            1
        );

        // Assert
        $positions = $sortedResults->pluck('position');
        $this->assertEquals(1, $positions[0]); // First student
        $this->assertEquals(1, $positions[1]); // Tied student
        $this->assertEquals(3, $positions[2]); // Third student (position skips to 3)
    }
}
