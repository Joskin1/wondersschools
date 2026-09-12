<?php

namespace App\Filament\Teacher\Resources\TeacherLessonPlanResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonPlanResource;
use App\Models\LessonPlan;
use App\Models\Session;
use App\Models\TeacherSubjectAssignment;
use App\Services\LessonDocxParserService;
use App\Services\LessonNoteCache;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListTeacherLessonPlans extends ListRecords
{
    protected static string $resource = TeacherLessonPlanResource::class;

    protected ?string $heading = 'My Lesson Plans';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('teacher.template.lesson-plan'))
                ->openUrlInNewTab(),

            Actions\Action::make('upload_template')
                ->label('Upload from Template')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Upload Lesson Plan (.docx)')
                ->modalDescription('Upload your filled Lesson_Plan_Template.docx. The system will automatically extract and structure all 13 sections.')
                ->form([
                    Select::make('subject_id')
                        ->label('Subject')
                        ->options(fn () => app(LessonNoteCache::class)->getTeacherAssignments(auth()->id())->pluck('subject.name', 'subject_id')->unique())
                        ->required()
                        ->reactive(),

                    Select::make('classroom_id')
                        ->label('Class')
                        ->options(function (callable $get) {
                            $subjectId = $get('subject_id');
                            if (!$subjectId) return [];

                            return app(LessonNoteCache::class)->getTeacherAssignments(auth()->id())
                                ->where('subject_id', $subjectId)
                                ->pluck('classroom.name', 'classroom_id')
                                ->unique();
                        })
                        ->required()
                        ->reactive(),

                    Select::make('week_number')
                        ->label('Week')
                        ->options(array_combine(
                            range(1, config('academic.weeks_per_term')),
                            array_map(fn ($week) => "Week {$week}", range(1, config('academic.weeks_per_term')))
                        ))
                        ->required()
                        ->helperText('Select any week in the 14-week term.'),

                    FileUpload::make('template_file')
                        ->label('Filled Template (.docx)')
                        ->disk('public')
                        ->directory('lesson-plan-uploads/temp')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/msword',
                        ])
                        ->maxSize(10240)
                        ->required()
                        ->helperText('Only .docx template files are supported.')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $activeSession = Session::active()->first();
                    $activeTerm = $activeSession?->activeTerm;

                    if (!$activeSession || !$activeTerm) {
                        Notification::make()
                            ->title('No Active Session')
                            ->body('There is no active academic session or term.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $isAssigned = TeacherSubjectAssignment::isAssigned(
                        auth()->id(),
                        $data['subject_id'],
                        $data['classroom_id'],
                        $activeSession->id,
                        $activeTerm->id,
                    );

                    if (!$isAssigned) {
                        Notification::make()
                            ->title('Not Assigned')
                            ->body('You are not assigned to this subject/class combination.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $existing = LessonPlan::where('teacher_id', auth()->id())
                        ->where('subject_id', $data['subject_id'])
                        ->where('classroom_id', $data['classroom_id'])
                        ->where('session_id', $activeSession->id)
                        ->where('term_id', $activeTerm->id)
                        ->where('week_number', $data['week_number'])
                        ->first();

                    if ($existing) {
                        Notification::make()
                            ->title('Lesson Plan Already Exists')
                            ->body('You have already created a lesson plan for this combination. Please edit the existing one.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $uploadedPath = $data['template_file'];
                    $fullPath = Storage::disk('public')->path($uploadedPath);

                    try {
                        $parser = app(LessonDocxParserService::class);
                        $plan = $parser->createLessonPlanFromDocx(
                            teacherId: auth()->id(),
                            subjectId: (int) $data['subject_id'],
                            classroomId: (int) $data['classroom_id'],
                            sessionId: (int) $activeSession->id,
                            termId: (int) $activeTerm->id,
                            weekNumber: (int) $data['week_number'],
                            filePath: $fullPath
                        );

                        Storage::disk('public')->delete($uploadedPath);

                        Notification::make()
                            ->title('Lesson Plan Created')
                            ->body('Your lesson plan was extracted from the template and saved as draft.')
                            ->success()
                            ->send();

                        $this->redirect(TeacherLessonPlanResource::getUrl('view', ['record' => $plan]));
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Failed to parse template')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\CreateAction::make()->label('Write Lesson Plan Online'),
        ];
    }
}
