<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\LessonNote;
use App\Models\LessonNoteVersion;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class LessonNoteSeeder extends Seeder
{
    public function run(): void
    {
        $activeSession = Session::active()->first();

        if (!$activeSession || !$activeSession->activeTerm) {
            $this->command->error('No active session or term found. Run DatabaseSeeder first.');
            return;
        }

        $activeTerm = $activeSession->activeTerm;

        $assignments = TeacherSubjectAssignment::where('session_id', $activeSession->id)
            ->where('term_id', $activeTerm->id)
            ->with(['teacher', 'subject', 'classroom'])
            ->get();

        if ($assignments->isEmpty()) {
            $this->command->error('No teacher assignments found. Run TeacherSubjectAssignmentSeeder first.');
            return;
        }

        // Get admin for reviews
        $admin = User::whereIn('role', ['admin', 'sudo'])->first();

        $notesCreated = 0;
        $versionsCreated = 0;

        // Create lesson notes for weeks 1-4 to simulate realistic data
        foreach ($assignments->groupBy('teacher_id') as $teacherId => $teacherAssignments) {
            foreach ($teacherAssignments->take(3) as $assignment) {
                for ($week = 1; $week <= 4; $week++) {
                    // Not every teacher submits every week
                    if ($week === 4 && rand(0, 1) === 0) {
                        continue;
                    }

                    $status = match ($week) {
                        1 => 'approved',
                        2 => collect(['approved', 'rejected'])->random(),
                        3 => collect(['pending', 'approved'])->random(),
                        default => 'pending',
                    };

                    $lessonNote = LessonNote::create([
                        'teacher_id' => $assignment->teacher_id,
                        'subject_id' => $assignment->subject_id,
                        'classroom_id' => $assignment->classroom_id,
                        'session_id' => $activeSession->id,
                        'term_id' => $activeTerm->id,
                        'week_number' => $week,
                        'status' => $status,
                    ]);

                    $fileHash = hash('sha256', "{$assignment->teacher_id}-{$assignment->subject_id}-{$assignment->classroom_id}-week{$week}");
                    $extension = collect(['pdf', 'docx'])->random();
                    $filePath = LessonNoteVersion::buildStoragePath(
                        $activeSession->id,
                        $activeTerm->id,
                        $week,
                        $assignment->teacher_id,
                        $fileHash,
                        $extension
                    );

                    $subjectCode = $assignment->subject->code ?? 'SUB';
                    $fileName = strtolower("{$subjectCode}-{$assignment->classroom->name}-week{$week}.{$extension}");

                    $reviewedBy = null;
                    $reviewedAt = null;
                    $adminComment = null;

                    if ($status === 'approved') {
                        $reviewedBy = $admin?->id;
                        $reviewedAt = now()->subDays(rand(1, 3));
                        $adminComment = collect([
                            'Well structured. Approved.',
                            'Good coverage of the topic.',
                            'Meets all requirements.',
                            null,
                        ])->random();
                    } elseif ($status === 'rejected') {
                        $reviewedBy = $admin?->id;
                        $reviewedAt = now()->subDays(rand(1, 2));
                        $adminComment = collect([
                            'Please add more examples for the students.',
                            'Learning objectives are missing. Revise and resubmit.',
                            'The content does not match the scheme of work for this week.',
                        ])->random();
                    }

                    $version = LessonNoteVersion::create([
                        'lesson_note_id' => $lessonNote->id,
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'file_size' => rand(200_000, 5_000_000),
                        'file_hash' => $fileHash,
                        'uploaded_by' => $assignment->teacher_id,
                        'admin_comment' => $adminComment,
                        'status' => $status,
                        'reviewed_by' => $reviewedBy,
                        'reviewed_at' => $reviewedAt,
                    ]);

                    $lessonNote->update(['latest_version_id' => $version->id]);

                    $notesCreated++;
                    $versionsCreated++;

                    // Rejected notes get a second version (re-upload) sometimes
                    if ($status === 'rejected' && rand(0, 1) === 1) {
                        $newHash = hash('sha256', $fileHash . '-v2');
                        $newPath = LessonNoteVersion::buildStoragePath(
                            $activeSession->id,
                            $activeTerm->id,
                            $week,
                            $assignment->teacher_id,
                            $newHash,
                            $extension
                        );

                        $reuploadVersion = LessonNoteVersion::create([
                            'lesson_note_id' => $lessonNote->id,
                            'file_path' => $newPath,
                            'file_name' => str_replace(".{$extension}", "-v2.{$extension}", $fileName),
                            'file_size' => rand(200_000, 5_000_000),
                            'file_hash' => $newHash,
                            'uploaded_by' => $assignment->teacher_id,
                            'status' => 'pending',
                        ]);

                        $lessonNote->update([
                            'latest_version_id' => $reuploadVersion->id,
                            'status' => 'pending',
                        ]);

                        $versionsCreated++;
                    }
                }
            }
        }

        $this->command->info("Created {$notesCreated} lesson notes with {$versionsCreated} versions.");
    }
}
