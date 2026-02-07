<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Score;
use App\Models\ScoreHeader;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScoreImportService
{
    protected $scoreService;

    public function __construct(ScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }
    /**
     * Generate Excel template for score entry
     */
    public function generateTemplate(int $classroomId, string $session, int $term, ?User $user = null): array
    {
        // Validate teacher access if user is provided
        if ($user && $user->isTeacher()) {
            $this->validateTeacherAccess($user, $classroomId, null, $session);
        }

        $classroom = Classroom::findOrFail($classroomId);
        $students = $classroom->students()->orderBy('name')->get();
        $subjects = $this->getClassroomSubjects($classroomId, $user, $session);
        $scoreHeaders = ScoreHeader::getHeaders($classroomId, $session, $term);

        $template = [
            'headers' => $this->buildHeaders($scoreHeaders),
            'rows' => $this->buildRows($students, $subjects, $scoreHeaders, $session, $term),
            'metadata' => [
                'classroom_id' => $classroomId,
                'classroom_name' => $classroom->name,
                'session' => $session,
                'term' => $term,
                'generated_at' => now()->toDateTimeString(),
            ],
        ];

        return $template;
    }

    /**
     * Build template headers
     */
    private function buildHeaders(array $scoreHeaders): array
    {
        $headers = ['Student ID', 'Student Name', 'Subject ID', 'Subject Name'];
        
        foreach ($scoreHeaders as $header) {
            $headers[] = $header['name'] . ' (Max: ' . $header['max_score'] . ')';
        }
        
        $headers[] = 'Total';
        
        return $headers;
    }

    /**
     * Build template rows
     */
    private function buildRows(Collection $students, Collection $subjects, array $scoreHeaders, string $session, int $term): array
    {
        $rows = [];
        
        foreach ($students as $student) {
            foreach ($subjects as $subject) {
                $row = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->name,
                ];
                
                $total = 0;
                foreach ($scoreHeaders as $header) {
                    $score = Score::where('student_id', $student->id)
                        ->where('subject_id', $subject->id)
                        ->where('score_header_id', $header['id'])
                        ->where('session', $session)
                        ->where('term', $term)
                        ->first();
                    
                    $value = $score ? (float) $score->value : 0;
                    $row[$header['name']] = $value;
                    $total += $value;
                }
                
                $row['total'] = $total;
                $rows[] = $row;
            }
        }
        
        return $rows;
    }

    /**
     * Import scores from array data
     */
    public function importScores(array $data, int $classroomId, string $session, int $term, ?User $user = null): array
    {
        $scoreHeaders = ScoreHeader::getHeaders($classroomId, $session, $term);
        $errors = [];
        $imported = 0;
        $updated = 0;
        $unauthorized = 0;

        DB::beginTransaction();
        
        try {
            foreach ($data as $index => $row) {
                // Validate teacher access for each row
                if ($user && $user->isTeacher()) {
                    if (!$this->validateTeacherAccess($user, $classroomId, $row['subject_id'] ?? null, $session, false)) {
                        $errors[] = [
                            'row' => $index + 1,
                            'errors' => ['You are not authorized to enter scores for this subject-class combination'],
                        ];
                        $unauthorized++;
                        continue;
                    }
                }

                $validation = $this->validateRow($row, $scoreHeaders);
                
                if (!$validation['valid']) {
                    $errors[] = [
                        'row' => $index + 1,
                        'errors' => $validation['errors'],
                    ];
                    continue;
                }
                
                $result = $this->importRow($row, $scoreHeaders, $classroomId, $session, $term);
                
                if ($result['created']) {
                    $imported++;
                } else {
                    $updated++;
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'unauthorized' => $unauthorized,
                'errors' => $errors,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $errors,
            ];
        }
    }

    /**
     * Validate a row
     */
    private function validateRow(array $row, array $scoreHeaders): array
    {
        $rules = [
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
        ];
        
        foreach ($scoreHeaders as $header) {
            $rules[$header['name']] = 'required|numeric|min:0|max:' . $header['max_score'];
        }
        
        $validator = Validator::make($row, $rules);
        
        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->all(),
        ];
    }

    /**
     * Import a single row
     */
    private function importRow(array $row, array $scoreHeaders, int $classroomId, string $session, int $term): array
    {
        $created = false;
        
        foreach ($scoreHeaders as $header) {
            $score = Score::updateOrCreate(
                [
                    'student_id' => $row['student_id'],
                    'subject_id' => $row['subject_id'],
                    'classroom_id' => $classroomId,
                    'score_header_id' => $header['id'],
                    'session' => $session,
                    'term' => $term,
                ],
                [
                    'value' => $row[$header['name']] ?? 0,
                ]
            );
            
            if ($score->wasRecentlyCreated) {
                $created = true;
            }
        }
        
        return ['created' => $created];
    }

    /**
     * Export scores to array
     */
    public function exportScores(int $classroomId, string $session, int $term): array
    {
        return $this->generateTemplate($classroomId, $session, $term);
    }

    /**
     * Get subjects for a classroom
     */
    private function getClassroomSubjects(int $classroomId, ?User $user = null, ?string $session = null): Collection
    {
        $query = Subject::whereHas('classrooms', function ($query) use ($classroomId) {
            $query->where('classrooms.id', $classroomId);
        });

        // Filter by teacher assignments if user is a teacher
        if ($user && $user->isTeacher() && $user->staff && $session) {
            $query->whereHas('classrooms', function ($q) use ($user, $classroomId, $session) {
                $q->whereExists(function ($subQuery) use ($user, $classroomId, $session) {
                    $subQuery->select(DB::raw(1))
                        ->from('classroom_subject_teacher')
                        ->whereColumn('classroom_subject_teacher.subject_id', 'subjects.id')
                        ->where('classroom_subject_teacher.classroom_id', $classroomId)
                        ->where('classroom_subject_teacher.staff_id', $user->staff->id)
                        ->where('classroom_subject_teacher.session', $session);
                });
            });
        }

        return $query->get();
    }

    /**
     * Validate teacher access to classroom-subject combination
     */
    private function validateTeacherAccess(User $user, int $classroomId, ?int $subjectId, string $session, bool $throwException = true): bool
    {
        if (!$user->isTeacher() || !$user->staff) {
            if ($throwException) {
                throw new \Exception('User is not a teacher');
            }
            return false;
        }

        // If no subject specified, check if teacher has any assignment to this classroom
        if ($subjectId === null) {
            $hasAccess = DB::table('classroom_subject_teacher')
                ->where('staff_id', $user->staff->id)
                ->where('classroom_id', $classroomId)
                ->where('session', $session)
                ->exists();
        } else {
            // Check specific subject-classroom assignment
            $hasAccess = $this->scoreService->validateTeacherAssignment(
                $user,
                $subjectId,
                $classroomId,
                $session
            );
        }

        if (!$hasAccess && $throwException) {
            throw new \Exception('You are not authorized to access scores for this classroom and subject combination');
        }

        return $hasAccess;
    }

    /**
     * Validate import file structure
     */
    public function validateImportFile(array $data, int $classroomId, string $session, int $term): array
    {
        $scoreHeaders = ScoreHeader::getHeaders($classroomId, $session, $term);
        $requiredHeaders = $this->buildHeaders($scoreHeaders);
        
        if (empty($data)) {
            return [
                'valid' => false,
                'message' => 'Import file is empty',
            ];
        }
        
        $fileHeaders = array_keys($data[0]);
        $missingHeaders = array_diff($requiredHeaders, $fileHeaders);
        
        if (!empty($missingHeaders)) {
            return [
                'valid' => false,
                'message' => 'Missing required headers: ' . implode(', ', $missingHeaders),
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'File structure is valid',
        ];
    }
}
