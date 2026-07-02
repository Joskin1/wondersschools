<?php

namespace App\Filament\Student\Resources\StudentAssignmentResource\Pages;

use App\Filament\Student\Resources\StudentAssignmentResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\RepeatableEntry;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\HtmlString;

class ViewAssignmentResult extends Page
{
    use InteractsWithRecord;

    protected static string $resource = StudentAssignmentResource::class;

    protected string $view = 'filament.student.resources.assignment-resource.pages.view-result';

    public $submission;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $studentId = auth()->user()?->student?->id;

        if (!$studentId) {
            abort(403, 'Only students can view assignment results.');
        }

        $this->submission = $this->record->submissions()->where('student_id', $studentId)->first();

        if (!$this->submission) {
            $this->redirect($this->getResource()::getUrl('take', ['record' => $this->record]));
            return;
        }
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->record)
            ->schema([
                Section::make('Your Result')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('score')
                                ->label('Score')
                                ->state(fn () => "{$this->submission->score} / {$this->submission->total_points}")
                                ->badge()
                                ->color(fn () => $this->submission->percentageScore() >= 50 ? 'success' : 'danger'),
                                
                            TextEntry::make('percentage')
                                ->label('Percentage')
                                ->state(fn () => $this->submission->percentageScore() . '%'),
                                
                            TextEntry::make('completed_at')
                                ->label('Completed On')
                                ->state(fn () => $this->submission->completed_at->format('M d, Y H:i')),
                        ]),
                    ]),

                Section::make('Corrections')
                    ->schema([
                        RepeatableEntry::make('questions')
                            ->schema([
                                TextEntry::make('question_text')
                                    ->label('Question')
                                    ->columnSpanFull(),
                                Grid::make(3)->schema([
                                    TextEntry::make('your_answer')
                                        ->label('Your Answer')
                                        ->state(function ($record) {
                                            $answerKey = $this->submission->answers[$record->id] ?? null;
                                            if (!$answerKey) return 'Not answered';
                                            return $answerKey . ': ' . ($record->options[$answerKey] ?? '');
                                        })
                                        ->color(function ($record) {
                                            $answerKey = $this->submission->answers[$record->id] ?? null;
                                            return $record->isCorrect($answerKey) ? 'success' : 'danger';
                                        }),
                                        
                                    TextEntry::make('correct_answer')
                                        ->label('Correct Answer')
                                        ->state(function ($record) {
                                            return $record->correct_option . ': ' . ($record->options[$record->correct_option] ?? '');
                                        })
                                        ->color('success'),
                                        
                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->state(function ($record) {
                                            $answerKey = $this->submission->answers[$record->id] ?? null;
                                            return $record->isCorrect($answerKey) ? 'Correct' : 'Incorrect';
                                        })
                                        ->color(function ($record) {
                                            $answerKey = $this->submission->answers[$record->id] ?? null;
                                            return $record->isCorrect($answerKey) ? 'success' : 'danger';
                                        }),
                                ]),
                            ])
                            ->columns(1),
                    ]),
            ]);
    }
}
