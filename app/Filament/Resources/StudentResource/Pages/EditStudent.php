<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\ClassArm;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\FrontendLibrary;
use App\Services\StudentAccountService;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected ?int $originalEnrollmentId = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Student $student */
        $student = $this->record;
        $user = $student->user;
        $enrollment = $student->currentEnrollment()
            ?? $student->enrollments()->latest('id')->first();

        $this->originalEnrollmentId = $enrollment?->id;

        $data['surname'] = $user?->surname ?? $student->surname;
        $data['first_name'] = $user?->first_name ?? $student->first_name;
        $data['other_name'] = $user?->other_name ?? $student->other_name;
        $data['admission_number'] = $student->admission_number;
        $data['date_of_birth'] = $student->date_of_birth?->format('Y-m-d');
        $data['is_portal_active'] = (bool) $student->is_portal_active;
        $data['session_id'] = $enrollment?->session_id ?? Session::where('is_active', true)->value('id');
        $data['classroom_id'] = $enrollment?->classroom_id;
        $data['class_arm_id'] = $enrollment?->class_arm_id;
        $data['department_id'] = $enrollment?->department_id;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Student $record */
        $surname = trim((string) ($data['surname'] ?? ''));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $otherName = filled($data['other_name'] ?? null) ? trim((string) $data['other_name']) : null;
        $initialPassword = $data['initial_password'] ?? null;
        $sessionId = isset($data['session_id']) ? (int) $data['session_id'] : null;
        $classroomId = isset($data['classroom_id']) ? (int) $data['classroom_id'] : null;
        $classArmId = filled($data['class_arm_id'] ?? null) ? (int) $data['class_arm_id'] : null;
        $departmentId = filled($data['department_id'] ?? null) ? (int) $data['department_id'] : null;
        $isPortalActive = (bool) ($data['is_portal_active'] ?? $record->is_portal_active);

        unset(
            $data['surname'],
            $data['first_name'],
            $data['other_name'],
            $data['initial_password'],
            $data['session_id'],
            $data['classroom_id'],
            $data['class_arm_id'],
            $data['department_id'],
            $data['is_portal_active']
        );

        if (array_key_exists('admission_number', $data) && filled($data['admission_number'])) {
            $data['admission_number'] = trim((string) $data['admission_number']);
        }

        return DB::transaction(function () use (
            $record,
            $data,
            $surname,
            $firstName,
            $otherName,
            $initialPassword,
            $sessionId,
            $classroomId,
            $classArmId,
            $departmentId,
            $isPortalActive,
        ): Student {
            $record->update($data);

            /** @var Student $student */
            $student = $record->fresh(['user']);
            $accountService = app(StudentAccountService::class);

            $this->syncUserAccount($student, $accountService, $surname, $firstName, $otherName, $initialPassword);
            $this->syncEnrollment($student, $sessionId, $classroomId, $classArmId, $departmentId);
            $this->syncPortalAccess($student, $isPortalActive);

            return $student->fresh();
        });
    }

    protected function syncUserAccount(
        Student $student,
        StudentAccountService $accountService,
        string $surname,
        string $firstName,
        ?string $otherName,
        ?string $initialPassword,
    ): void {
        if (! $student->user) {
            $accountService->createOrLinkUser(
                $student,
                $surname ?: 'Student',
                $firstName ?: ('#' . $student->id),
                $otherName,
                filled($initialPassword) ? $initialPassword : null,
                auth()->id(),
            );

            return;
        }

        $user = $student->user;
        $userUpdates = [];

        if (Schema::hasColumn('users', 'surname')) {
            $userUpdates['surname'] = $surname;
            $userUpdates['first_name'] = $firstName;
            $userUpdates['other_name'] = $otherName;
        }
        if (Schema::hasColumn('users', 'name')) {
            $userUpdates['name'] = trim(implode(' ', array_filter([$surname, $firstName, $otherName])));
        }

        if (filled($initialPassword)) {
            $userUpdates['password'] = Hash::make($initialPassword);
            $userUpdates['email_verified_at'] = $user->email_verified_at ?? now();
            $userUpdates['registration_completed_at'] = $user->registration_completed_at ?? now();
        }

        if (str_ends_with((string) $user->email, '@student.local')) {
            $expectedEmail = $accountService->internalEmailFor($student);
            if ($expectedEmail !== $user->email && ! User::where('email', $expectedEmail)->whereKeyNot($user->id)->exists()) {
                $userUpdates['email'] = $expectedEmail;
            }
        }

        $user->update($userUpdates);
    }

    protected function syncEnrollment(
        Student $student,
        ?int $sessionId,
        ?int $classroomId,
        ?int $classArmId,
        ?int $departmentId,
    ): void {
        if (! $sessionId || ! $classroomId) {
            return;
        }

        if (! Classroom::where('id', $classroomId)->value('has_departments')) {
            $departmentId = null;
        }

        if (
            ! FrontendLibrary::getBooleanSetting('enable_class_arms') ||
            ($classArmId && ! ClassArm::where('id', $classArmId)->where('classroom_id', $classroomId)->exists())
        ) {
            $classArmId = null;
        }

        $existingForTarget = StudentEnrollment::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->first();

        if ($existingForTarget) {
            $existingForTarget->update([
                'classroom_id' => $classroomId,
                'class_arm_id' => $classArmId,
                'department_id' => $departmentId,
            ]);

            return;
        }

        if ($this->originalEnrollmentId) {
            $originalEnrollment = StudentEnrollment::where('student_id', $student->id)
                ->find($this->originalEnrollmentId);

            if ($originalEnrollment) {
                $originalEnrollment->update([
                    'session_id' => $sessionId,
                    'classroom_id' => $classroomId,
                    'class_arm_id' => $classArmId,
                    'department_id' => $departmentId,
                ]);

                return;
            }
        }

        StudentEnrollment::create([
            'student_id' => $student->id,
            'session_id' => $sessionId,
            'classroom_id' => $classroomId,
            'class_arm_id' => $classArmId,
            'department_id' => $departmentId,
        ]);
    }

    protected function syncPortalAccess(Student $student, bool $isPortalActive): void
    {
        $student->refresh();

        if ($isPortalActive) {
            if (! $student->is_portal_active || $student->status !== 'active' || ! $student->user?->is_active) {
                $student->activatePortal(auth()->id() ?? 0);
            }

            if (! $student->registration_completed_at) {
                $student->forceFill(['registration_completed_at' => now()])->saveQuietly();
            }

            return;
        }

        if ($student->is_portal_active || $student->user?->is_active) {
            $student->deactivatePortal();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Student updated successfully';
    }
}
