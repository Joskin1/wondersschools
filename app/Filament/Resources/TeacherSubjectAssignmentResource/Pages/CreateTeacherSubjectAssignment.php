<?php

namespace App\Filament\Resources\TeacherSubjectAssignmentResource\Pages;

use App\Filament\Resources\TeacherSubjectAssignmentResource;
use App\Models\Classroom;
use App\Models\TeacherSubjectAssignment;
use App\Services\LessonNoteCache;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTeacherSubjectAssignment extends CreateRecord
{
    protected static string $resource = TeacherSubjectAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $classroomIds = (array) ($data['classroom_ids'] ?? []);
        if (empty($classroomIds) && isset($data['classroom_id'])) {
            $classroomIds = [$data['classroom_id']];
        }

        if (empty($classroomIds)) {
            Notification::make()
                ->title('Validation Error')
                ->body('Please select at least one class.')
                ->danger()
                ->send();

            $this->halt();
        }

        unset($data['classroom_ids']);

        $created = [];
        $alreadyAssignedClasses = [];

        foreach ($classroomIds as $classroomId) {
            $existing = TeacherSubjectAssignment::where('subject_id', $data['subject_id'])
                ->where('classroom_id', $classroomId)
                ->where('session_id', $data['session_id'])
                ->where('term_id', $data['term_id'])
                ->first();

            if ($existing) {
                $className = Classroom::find($classroomId)?->name ?? "Class #{$classroomId}";
                $alreadyAssignedClasses[] = $className;
                continue;
            }

            $assignmentData = array_merge($data, [
                'classroom_id' => $classroomId,
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $assignment = static::getModel()::create($assignmentData);
            $created[] = $assignment;
        }

        if (empty($created) && !empty($alreadyAssignedClasses)) {
            Notification::make()
                ->title('Subject Already Assigned')
                ->body('All selected classes (' . implode(', ', $alreadyAssignedClasses) . ') already have a teacher assigned for this subject in the selected term.')
                ->danger()
                ->send();

            $this->halt();
        }

        if (!empty($alreadyAssignedClasses)) {
            Notification::make()
                ->title('Some Classes Skipped')
                ->body('The following classes already have an assignment for this subject: ' . implode(', ', $alreadyAssignedClasses))
                ->warning()
                ->send();
        }

        if (isset($data['teacher_id'])) {
            app(LessonNoteCache::class)->invalidateTeacherAssignments((int) $data['teacher_id']);
        }

        return $created[0];
    }
}

