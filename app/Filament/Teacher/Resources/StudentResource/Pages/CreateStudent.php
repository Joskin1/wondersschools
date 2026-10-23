<?php

namespace App\Filament\Teacher\Resources\StudentResource\Pages;

use App\Filament\Teacher\Resources\StudentResource;
use App\Models\ClassTeacherAssignment;
use App\Models\StudentEnrollment;
use App\Services\StudentAccountService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected ?string $surname = null;
    protected ?string $firstName = null;
    protected ?string $otherName = null;
    protected ?int $sessionId = null;
    protected ?int $classroomId = null;
    protected ?string $initialPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $teacherId = auth()->id();
        $this->surname = $data['surname'] ?? null;
        $this->firstName = $data['first_name'] ?? null;
        $this->otherName = $data['other_name'] ?? null;
        $this->sessionId = $data['session_id'] ?? null;
        $this->classroomId = $data['classroom_id'] ?? null;
        $this->initialPassword = $data['initial_password'] ?? null;

        // Security validation: ensure teacher is assigned as class teacher for selected classroom
        $isAssigned = ClassTeacherAssignment::where('teacher_id', $teacherId)
            ->where('class_id', $this->classroomId)
            ->when($this->sessionId, fn ($q) => $q->where('session_id', $this->sessionId))
            ->exists();

        if (! $isAssigned) {
            throw ValidationException::withMessages([
                'classroom_id' => 'You are only permitted to enroll students into your assigned classroom.',
            ]);
        }

        unset(
            $data['surname'],
            $data['first_name'],
            $data['other_name'],
            $data['session_id'],
            $data['classroom_id'],
            $data['initial_password']
        );

        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        // 1. Create linked User for the student
        if ($this->surname && $this->firstName) {
            app(StudentAccountService::class)->createOrLinkUser(
                $this->record,
                $this->surname,
                $this->firstName,
                $this->otherName,
                $this->initialPassword,
                auth()->id(),
            );
        }

        // 2. Create enrollment record
        if ($this->sessionId && $this->classroomId) {
            StudentEnrollment::create([
                'student_id'   => $this->record->id,
                'classroom_id' => $this->classroomId,
                'session_id'   => $this->sessionId,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
