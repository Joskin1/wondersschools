<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherAssignmentService
{
    /**
     * Assign a teacher to a subject-classroom combination for a session
     */
    public function assignTeacher(
        int $staffId,
        int $classroomId,
        int $subjectId,
        string $session
    ): bool {
        try {
            DB::table('classroom_subject_teacher')->insert([
                'staff_id' => $staffId,
                'classroom_id' => $classroomId,
                'subject_id' => $subjectId,
                'session' => $session,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return true;
        } catch (\Exception $e) {
            // Handle duplicate assignment error
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Remove a teacher assignment
     */
    public function removeAssignment(
        int $staffId,
        int $classroomId,
        int $subjectId,
        string $session
    ): bool {
        return DB::table('classroom_subject_teacher')
            ->where('staff_id', $staffId)
            ->where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->delete() > 0;
    }

    /**
     * Bulk assign a teacher to multiple subject-classroom combinations
     */
    public function bulkAssign(int $staffId, array $assignments, string $session): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($assignments as $assignment) {
                $success = $this->assignTeacher(
                    $staffId,
                    $assignment['classroom_id'],
                    $assignment['subject_id'],
                    $session
                );

                if ($success) {
                    $results['success'][] = $assignment;
                } else {
                    $results['failed'][] = [
                        'assignment' => $assignment,
                        'reason' => 'Duplicate assignment',
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Get all assignments for a teacher in a session
     */
    public function getTeacherAssignments(int $staffId, ?string $session = null): Collection
    {
        $query = DB::table('classroom_subject_teacher')
            ->join('classrooms', 'classroom_subject_teacher.classroom_id', '=', 'classrooms.id')
            ->join('subjects', 'classroom_subject_teacher.subject_id', '=', 'subjects.id')
            ->where('classroom_subject_teacher.staff_id', $staffId)
            ->select(
                'classroom_subject_teacher.*',
                'classrooms.name as classroom_name',
                'subjects.name as subject_name'
            );

        if ($session) {
            $query->where('classroom_subject_teacher.session', $session);
        }

        return $query->get();
    }

    /**
     * Get all teachers assigned to a classroom-subject combination
     */
    public function getAssignedTeachers(int $classroomId, int $subjectId, string $session): Collection
    {
        return DB::table('classroom_subject_teacher')
            ->join('staff', 'classroom_subject_teacher.staff_id', '=', 'staff.id')
            ->join('users', 'staff.user_id', '=', 'users.id')
            ->where('classroom_subject_teacher.classroom_id', $classroomId)
            ->where('classroom_subject_teacher.subject_id', $subjectId)
            ->where('classroom_subject_teacher.session', $session)
            ->select(
                'staff.*',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->get();
    }

    /**
     * Check if a teacher is assigned to a specific combination
     */
    public function isAssigned(
        int $staffId,
        int $classroomId,
        int $subjectId,
        string $session
    ): bool {
        return DB::table('classroom_subject_teacher')
            ->where('staff_id', $staffId)
            ->where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->exists();
    }

    /**
     * Get all classrooms a teacher is assigned to in a session
     */
    public function getTeacherClassrooms(int $staffId, string $session): Collection
    {
        return DB::table('classroom_subject_teacher')
            ->join('classrooms', 'classroom_subject_teacher.classroom_id', '=', 'classrooms.id')
            ->where('classroom_subject_teacher.staff_id', $staffId)
            ->where('classroom_subject_teacher.session', $session)
            ->select('classrooms.*')
            ->distinct()
            ->get();
    }

    /**
     * Get all subjects a teacher is assigned to in a session
     */
    public function getTeacherSubjects(int $staffId, string $session): Collection
    {
        return DB::table('classroom_subject_teacher')
            ->join('subjects', 'classroom_subject_teacher.subject_id', '=', 'subjects.id')
            ->where('classroom_subject_teacher.staff_id', $staffId)
            ->where('classroom_subject_teacher.session', $session)
            ->select('subjects.*')
            ->distinct()
            ->get();
    }

    /**
     * Copy assignments from one session to another
     */
    public function copyAssignmentsToNewSession(string $fromSession, string $toSession): int
    {
        $assignments = DB::table('classroom_subject_teacher')
            ->where('session', $fromSession)
            ->get();

        $count = 0;

        DB::beginTransaction();

        try {
            foreach ($assignments as $assignment) {
                $success = $this->assignTeacher(
                    $assignment->staff_id,
                    $assignment->classroom_id,
                    $assignment->subject_id,
                    $toSession
                );

                if ($success) {
                    $count++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $count;
    }

    /**
     * Remove all assignments for a session
     */
    public function clearSessionAssignments(string $session): int
    {
        return DB::table('classroom_subject_teacher')
            ->where('session', $session)
            ->delete();
    }

    /**
     * Get assignment statistics for a session
     */
    public function getSessionStatistics(string $session): array
    {
        $totalAssignments = DB::table('classroom_subject_teacher')
            ->where('session', $session)
            ->count();

        $uniqueTeachers = DB::table('classroom_subject_teacher')
            ->where('session', $session)
            ->distinct('staff_id')
            ->count('staff_id');

        $uniqueClassrooms = DB::table('classroom_subject_teacher')
            ->where('session', $session)
            ->distinct('classroom_id')
            ->count('classroom_id');

        $uniqueSubjects = DB::table('classroom_subject_teacher')
            ->where('session', $session)
            ->distinct('subject_id')
            ->count('subject_id');

        return [
            'total_assignments' => $totalAssignments,
            'unique_teachers' => $uniqueTeachers,
            'unique_classrooms' => $uniqueClassrooms,
            'unique_subjects' => $uniqueSubjects,
            'avg_assignments_per_teacher' => $uniqueTeachers > 0 
                ? round($totalAssignments / $uniqueTeachers, 2) 
                : 0,
        ];
    }

    /**
     * Validate assignment data
     */
    public function validateAssignment(array $data): array
    {
        $errors = [];

        // Check if staff exists
        if (!Staff::find($data['staff_id'])) {
            $errors[] = 'Staff member not found';
        }

        // Check if classroom exists
        if (!Classroom::find($data['classroom_id'])) {
            $errors[] = 'Classroom not found';
        }

        // Check if subject exists
        if (!Subject::find($data['subject_id'])) {
            $errors[] = 'Subject not found';
        }

        // Check if assignment already exists
        if ($this->isAssigned(
            $data['staff_id'],
            $data['classroom_id'],
            $data['subject_id'],
            $data['session']
        )) {
            $errors[] = 'This assignment already exists';
        }

        return $errors;
    }
}
