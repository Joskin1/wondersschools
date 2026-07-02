<?php

namespace App\Filament\Student\Resources\StudentAssignmentResource\Pages;

use App\Filament\Student\Resources\StudentAssignmentResource;
use App\Models\AssignmentSubmission;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Radio;
use Filament\Forms\Form;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class TakeAssignment extends Page implements HasForms
{
    use InteractsWithRecord, InteractsWithForms;

    protected static string $resource = StudentAssignmentResource::class;

    protected string $view = 'filament.student.resources.assignment-resource.pages.take-assignment';

    public ?array $data = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $studentId = auth()->user()?->student?->id;

        if (!$studentId) {
            abort(403, 'Only students can take assignments.');
        }

        if ($this->record->submissions()->where('student_id', $studentId)->exists()) {
            Notification::make()
                ->title('Already Completed')
                ->body('You have already taken this assignment.')
                ->warning()
                ->send();

            $this->redirect($this->getResource()::getUrl('result', ['record' => $this->record]));
            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $schemaFields = [];

        foreach ($this->record->questions as $index => $question) {
            $options = [];
            foreach ($question->options as $key => $val) {
                $options[$key] = $val;
            }

            $schemaFields[] = Section::make("Question " . ($index + 1))
                ->description($question->question_text)
                ->schema([
                    Radio::make("answers.{$question->id}")
                        ->label('Select your answer')
                        ->options($options)
                        ->required(),
                ]);
        }

        return $schema
            ->components($schemaFields)
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $studentAnswers = $data['answers'] ?? [];

        $studentId = auth()->user()->student->id;

        // Double check
        if ($this->record->submissions()->where('student_id', $studentId)->exists()) {
            return;
        }

        $gradeResult = AssignmentSubmission::grade($this->record->questions, $studentAnswers);

        AssignmentSubmission::create([
            'student_id' => $studentId,
            'assignment_id' => $this->record->id,
            'answers' => $studentAnswers,
            'score' => $gradeResult['score'],
            'total_points' => $gradeResult['total_points'],
            'completed_at' => now(),
        ]);

        Notification::make()
            ->title('Assignment Submitted')
            ->body('Your assignment has been marked automatically.')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('result', ['record' => $this->record]));
    }
}
