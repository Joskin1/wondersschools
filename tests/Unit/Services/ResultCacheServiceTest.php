<?php

namespace Tests\Unit\Services;

use App\Models\Classroom;
use App\Models\Result;
use App\Models\Student;
use App\Services\ResultCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ResultCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResultCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ResultCacheService::class);
        Cache::flush();
    }

    public function test_caches_result_successfully(): void
    {
        // Arrange
        $result = Result::factory()->create([
            'cache_key' => 'test_key_123',
        ]);

        // Act
        $cached = $this->service->cacheResult($result);

        // Assert
        $this->assertTrue($cached);
        $this->assertTrue($this->service->isCached('test_key_123'));
    }

    public function test_retrieves_cached_result(): void
    {
        // Arrange
        $result = Result::factory()->create([
            'cache_key' => 'test_key_456',
        ]);
        $this->service->cacheResult($result);

        // Act
        $cachedData = $this->service->getCachedResult('test_key_456');

        // Assert
        $this->assertIsArray($cachedData);
        $this->assertEquals($result->id, $cachedData['id']);
    }

    public function test_invalidates_result_cache(): void
    {
        // Arrange
        $result = Result::factory()->create([
            'cache_key' => 'test_key_789',
        ]);
        $this->service->cacheResult($result);

        // Act
        $invalidated = $this->service->invalidateResult('test_key_789');

        // Assert
        $this->assertTrue($invalidated);
        $this->assertFalse($this->service->isCached('test_key_789'));
    }

    public function test_navigates_to_first_result(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $students = Student::factory()->count(3)->create(['classroom_id' => $classroom->id]);
        
        $results = collect();
        foreach ($students as $index => $student) {
            $results->push(Result::factory()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'session' => '2023/2024',
                'term' => 1,
                'position' => $index + 1,
                'cache_key' => 'key_' . ($index + 1),
            ]));
        }

        // Act
        $firstResult = $this->service->navigateResults(
            'key_2',
            'FIRST',
            $classroom->id,
            '2023/2024',
            1
        );

        // Assert
        $this->assertNotNull($firstResult);
        $this->assertEquals(1, $firstResult->position);
    }

    public function test_navigates_to_next_result(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $students = Student::factory()->count(3)->create(['classroom_id' => $classroom->id]);
        
        foreach ($students as $index => $student) {
            Result::factory()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'session' => '2023/2024',
                'term' => 1,
                'position' => $index + 1,
                'cache_key' => 'key_' . ($index + 1),
            ]);
        }

        // Act
        $nextResult = $this->service->navigateResults(
            'key_1',
            'NEXT',
            $classroom->id,
            '2023/2024',
            1
        );

        // Assert
        $this->assertNotNull($nextResult);
        $this->assertEquals(2, $nextResult->position);
    }

    public function test_navigates_to_previous_result(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $students = Student::factory()->count(3)->create(['classroom_id' => $classroom->id]);
        
        foreach ($students as $index => $student) {
            Result::factory()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'session' => '2023/2024',
                'term' => 1,
                'position' => $index + 1,
                'cache_key' => 'key_' . ($index + 1),
            ]);
        }

        // Act
        $prevResult = $this->service->navigateResults(
            'key_3',
            'PREV',
            $classroom->id,
            '2023/2024',
            1
        );

        // Assert
        $this->assertNotNull($prevResult);
        $this->assertEquals(2, $prevResult->position);
    }

    public function test_navigates_to_last_result(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $students = Student::factory()->count(3)->create(['classroom_id' => $classroom->id]);
        
        foreach ($students as $index => $student) {
            Result::factory()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'session' => '2023/2024',
                'term' => 1,
                'position' => $index + 1,
                'cache_key' => 'key_' . ($index + 1),
            ]);
        }

        // Act
        $lastResult = $this->service->navigateResults(
            'key_1',
            'LAST',
            $classroom->id,
            '2023/2024',
            1
        );

        // Assert
        $this->assertNotNull($lastResult);
        $this->assertEquals(3, $lastResult->position);
    }

    public function test_invalidates_classroom_results(): void
    {
        // Arrange
        $classroom = Classroom::factory()->create();
        $students = Student::factory()->count(3)->create(['classroom_id' => $classroom->id]);
        
        foreach ($students as $index => $student) {
            $result = Result::factory()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'session' => '2023/2024',
                'term' => 1,
                'cache_key' => 'key_' . ($index + 1),
            ]);
            $this->service->cacheResult($result);
        }

        // Act
        $count = $this->service->invalidateClassroomResults(
            $classroom->id,
            '2023/2024',
            1
        );

        // Assert
        $this->assertEquals(3, $count);
    }
}
