<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\StudentEnrollment;
use App\Services\StudentAccountService;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->sessionId = $data['session_id'] ?? null;
        $this->classroomId = $data['classroom_id'] ?? null;
        $this->studentEmail = $data['student_email'] ?? null;
        $this->initialPassword = $data['initial_password'] ?? null;

        unset($data['session_id'], $data['classroom_id'], $data['student_email'], $data['initial_password']);

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

        if ($this->studentEmail && $this->initialPassword) {
            app(StudentAccountService::class)->createOrUpdateLogin(
                $this->record,
                $this->studentEmail,
                $this->initialPassword,
                auth()->id(),
            );
        }
    }

    protected ?int $sessionId = null;

    protected ?int $classroomId = null;

    protected ?string $studentEmail = null;

    protected ?string $initialPassword = null;
}
