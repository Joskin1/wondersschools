<?php

namespace App\Filament\Teacher\Resources\AssignmentResource\Pages;

use App\Filament\Teacher\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\Session;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        if (!$activeSession || !$activeTerm) {
            Notification::make()
                ->title('No Active Session')
                ->body('There is no active academic session or term.')
                ->danger()
                ->send();
            $this->halt();
        }

        $data['teacher_id'] = auth()->id();
        $data['session_id'] = $activeSession->id;
        $data['term_id'] = $activeTerm->id;

        // Check for duplicate assignment
        $existing = Assignment::where('teacher_id', auth()->id())
            ->where('subject_id', $data['subject_id'])
            ->where('classroom_id', $data['classroom_id'])
            ->where('session_id', $activeSession->id)
            ->where('term_id', $activeTerm->id)
            ->where('week_number', $data['week_number'])
            ->first();

        if ($existing) {
            Notification::make()
                ->title('Assignment Exists')
                ->body('You have already created an assignment for this class, subject, and week.')
                ->warning()
                ->send();
            $this->halt();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
