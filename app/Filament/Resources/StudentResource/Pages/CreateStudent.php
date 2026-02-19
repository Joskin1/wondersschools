<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\StudentEnrollment;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract session_id and classroom_id for enrollment
        $this->sessionId = $data['session_id'] ?? null;
        $this->classroomId = $data['classroom_id'] ?? null;

        // Remove them from student data
        unset($data['session_id'], $data['classroom_id']);

        // Set default status
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Create enrollment record
        if ($this->sessionId && $this->classroomId) {
            StudentEnrollment::create([
                'student_id' => $this->record->id,
                'classroom_id' => $this->classroomId,
                'session_id' => $this->sessionId,
            ]);
        }
    }

    protected ?int $sessionId = null;
    protected ?int $classroomId = null;
}
