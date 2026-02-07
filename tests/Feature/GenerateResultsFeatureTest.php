<?php

namespace Tests\Feature;

use App\Jobs\GenerateResultJob;
use App\Models\Classroom;
use App\Models\Grading;
use App\Models\Result;
use App\Models\Score;
use App\Models\ScoreHeader;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateResultsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_results_for_classroom(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $students = Student::factory()->count(10)->create(['classroom_id' => $classroom->id]);
        $subjects = Subject::factory()->count(5)->create();
        
        $headers = ScoreHeader::factory()
            ->standard($classroom->id, '2023/2024', 1)
            ->count(3)
            ->create();
        
        foreach ($students as $student) {
            foreach ($subjects as $subject) {
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
        }
        
        Grading::factory()->standard()->create();

        // Act
        $job = new GenerateResultJob(
            $classroom->id,
            '2023/2024',
            1
        );
        $job->handle(
            app(\App\Services\ResultComputationService::class),
            app(\App\Services\ResultCacheService::class)
        );

        // Assert
        $results = Result::where('classroom_id', $classroom->id)
            ->where('session', '2023/2024')
            ->where('term', 1)
            ->get();

        $this->assertCount(10, $results);
        
        // Check all results have required fields
        foreach ($results as $result) {
            $this->assertNotNull($result->cache_key);
            $this->assertNotNull($result->result_data);
            $this->assertNotNull($result->position);
            $this->assertGreaterThan(0, $result->average_score);
        }
    }

    public function test_dispatches_generate_result_job(): void
    {
        // Arrange
        Queue::fake();
        $classroom = Classroom::factory()->create();

        // Act
        GenerateResultJob::dispatch(
            $classroom->id,
            '2023/2024',
            1
        );

        // Assert
        Queue::assertPushed(GenerateResultJob::class, function ($job) use ($classroom) {
            return $job->classroomId === $classroom->id
                && $job->session === '2023/2024'
                && $job->term === 1;
        });
    }

    public function test_results_have_correct_positions(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $students = Student::factory()->count(5)->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        
        $headers = ScoreHeader::factory()
            ->standard($classroom->id, '2023/2024', 1)
            ->count(3)
            ->create();
        
        // Create scores with known values for predictable positions
        $scores = [90, 80, 70, 60, 50];
        foreach ($students as $index => $student) {
            foreach ($headers as $header) {
                Score::factory()->create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'score_header_id' => $header->id,
                    'session' => '2023/2024',
                    'term' => 1,
                    'value' => $scores[$index] / 3, // Divide by 3 headers
                ]);
            }
        }
        
        Grading::factory()->standard()->create();

        // Act
        $job = new GenerateResultJob(
            $classroom->id,
            '2023/2024',
            1
        );
        $job->handle(
            app(\App\Services\ResultComputationService::class),
            app(\App\Services\ResultCacheService::class)
        );

        // Assert
        $results = Result::where('classroom_id', $classroom->id)
            ->orderBy('position')
            ->get();

        $this->assertEquals(1, $results[0]->position);
        $this->assertEquals(2, $results[1]->position);
        $this->assertEquals(3, $results[2]->position);
        $this->assertEquals(4, $results[3]->position);
        $this->assertEquals(5, $results[4]->position);
    }

    public function test_result_data_contains_subject_scores(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        
        $headers = ScoreHeader::factory()
            ->standard($classroom->id, '2023/2024', 1)
            ->count(3)
            ->create();
        
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
        
        Grading::factory()->standard()->create();

        // Act
        $job = new GenerateResultJob(
            $classroom->id,
            '2023/2024',
            1
        );
        $job->handle(
            app(\App\Services\ResultComputationService::class),
            app(\App\Services\ResultCacheService::class)
        );

        // Assert
        $result = Result::where('student_id', $student->id)->first();
        $this->assertNotNull($result->result_data);
        $this->assertArrayHasKey($subject->id, $result->result_data);
        $this->assertArrayHasKey('scores', $result->result_data[$subject->id]);
        $this->assertArrayHasKey('total', $result->result_data[$subject->id]);
        $this->assertEquals(60, $result->result_data[$subject->id]['total']);
    }
}
