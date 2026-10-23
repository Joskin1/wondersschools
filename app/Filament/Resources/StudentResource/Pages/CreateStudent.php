<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\StudentEnrollment;
use App\Services\StudentAccountService;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected ?string $surname = null;
    protected ?string $firstName = null;
    protected ?string $otherName = null;
    protected ?int $sessionId = null;
    protected ?int $classroomId = null;
    protected ?int $classArmId = null;
    protected ?int $departmentId = null;
    protected ?string $initialPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->surname = $data['surname'] ?? null;
        $this->firstName = $data['first_name'] ?? null;
        $this->otherName = $data['other_name'] ?? null;
        $this->sessionId = $data['session_id'] ?? null;
        $this->classroomId = $data['classroom_id'] ?? null;
        $this->classArmId = $data['class_arm_id'] ?? null;
        $this->departmentId = $data['department_id'] ?? null;
        $this->initialPassword = $data['initial_password'] ?? null;

        unset(
            $data['surname'],
            $data['first_name'],
            $data['other_name'],
            $data['session_id'],
            $data['classroom_id'],
            $data['class_arm_id'],
            $data['department_id'],
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
                'student_id' => $this->record->id,
                'classroom_id' => $this->classroomId,
                'class_arm_id' => $this->classArmId,
                'department_id' => $this->departmentId,
                'session_id' => $this->sessionId,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
