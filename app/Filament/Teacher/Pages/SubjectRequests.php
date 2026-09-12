<?php

namespace App\Filament\Teacher\Pages;

use App\Filament\Resources\TeacherSubjectAssignmentResource;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubjectRequests extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-hand-raised';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Subject Selection';

    protected static ?string $title = 'Subject & Class Selection';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.teacher.pages.subject-requests';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->isTeacher();
    }

    public function mount(): void
    {
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        $this->form->fill([
            'session_id' => $activeSession?->id,
            'term_id' => $activeTerm?->id,
            'classroom_ids' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('subject_id')
                    ->label('Subject to Teach')
                    ->options(Subject::orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Select the subject you want to teach'),

                Select::make('classroom_ids')
                    ->label('Classes')
                    ->options(function () {
                        $classrooms = Classroom::active()->ordered()->with('classGroup')->get();
                        $hasGroups = $classrooms->contains(fn ($c) => $c->class_group_id !== null);
                        if ($hasGroups) {
                            $grouped = [];
                            foreach ($classrooms as $classroom) {
                                $groupName = $classroom->classGroup?->name ?? 'Ungrouped Classes';
                                $grouped[$groupName][$classroom->id] = $classroom->name;
                            }
                            return $grouped;
                        }
                        return $classrooms->pluck('name', 'id');
                    })
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Select all the classes you will teach this subject'),
            ]);
    }

    public function submitRequest(): void
    {
        $formData = $this->form->getState();

        $teacher = Auth::user();
        if (! $teacher || ! $teacher->isTeacher()) {
            Notification::make()->title('Unauthorized')->danger()->send();
            return;
        }

        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        if (! $activeSession || ! $activeTerm) {
            Notification::make()
                ->title('No Active Academic Session')
                ->body('Cannot submit requests because there is no active academic session or term configured.')
                ->danger()
                ->send();
            return;
        }

        $subjectId = (int) ($formData['subject_id'] ?? 0);
        $classroomIds = (array) ($formData['classroom_ids'] ?? []);

        if (! $subjectId) {
            Notification::make()
                ->title('Subject Required')
                ->body('Please select a subject.')
                ->danger()
                ->send();
            return;
        }

        if (empty($classroomIds)) {
            Notification::make()
                ->title('Classes Required')
                ->body('Please select at least one class.')
                ->danger()
                ->send();
            return;
        }

        $subject = Subject::find($subjectId);
        if (! $subject) {
            Notification::make()->title('Subject not found')->danger()->send();
            return;
        }

        $createdCount = 0;
        $skippedClasses = [];
        $createdClasses = [];

        foreach ($classroomIds as $classroomId) {
            $classroom = Classroom::find($classroomId);
            $className = $classroom?->name ?? "Class #{$classroomId}";

            // Check if there is an existing approved or pending assignment for this subject in this class
            $existing = TeacherSubjectAssignment::where('subject_id', $subjectId)
                ->where('classroom_id', $classroomId)
                ->where('session_id', $activeSession->id)
                ->where('term_id', $activeTerm->id)
                ->first();

            if ($existing) {
                if ($existing->teacher_id === $teacher->id) {
                    $statusText = match ($existing->status) {
                        'approved' => 'you already teach this',
                        'pending' => 'you already requested this',
                        'rejected' => 'your previous request was rejected',
                        default => 'request already exists',
                    };
                    $skippedClasses[] = "{$className} ({$statusText})";
                } else {
                    $skippedClasses[] = "{$className} (already assigned to {$existing->teacher?->name})";
                }
                continue;
            }

            // Create pending assignment request
            TeacherSubjectAssignment::create([
                'teacher_id' => $teacher->id,
                'subject_id' => $subjectId,
                'classroom_id' => $classroomId,
                'session_id' => $activeSession->id,
                'term_id' => $activeTerm->id,
                'status' => 'pending',
            ]);

            $createdCount++;
            $createdClasses[] = $className;
        }

        if ($createdCount > 0) {
            // Send notification to admins & sudo users
            $admins = User::whereIn('role', ['admin', 'sudo'])
                ->where('is_active', true)
                ->get();

            $classNamesList = implode(', ', $createdClasses);

            foreach ($admins as $admin) {
                Notification::make()
                    ->title('New Subject Assignment Request')
                    ->body("{$teacher->name} requested to teach {$subject->name} in: {$classNamesList}.")
                    ->icon('heroicon-o-academic-cap')
                    ->actions([
                        Action::make('review')
                            ->button()
                            ->url(TeacherSubjectAssignmentResource::getUrl('index', ['activeTab' => 'pending'])),
                    ])
                    ->sendToDatabase($admin);
            }

            Notification::make()
                ->title('Request Submitted Successfully')
                ->body("Your request to teach {$subject->name} for " . count($createdClasses) . " class(es) has been submitted for admin approval.")
                ->success()
                ->send();

            $this->form->fill([
                'subject_id' => null,
                'classroom_ids' => [],
            ]);
        }

        if (!empty($skippedClasses)) {
            Notification::make()
                ->title('Some Classes Skipped')
                ->body(implode('; ', $skippedClasses))
                ->warning()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        $teacher = Auth::user();

        return $table
            ->query(
                TeacherSubjectAssignment::query()
                    ->where('teacher_id', $teacher?->id)
                    ->with(['subject', 'classroom', 'session', 'term', 'approver'])
            )
            ->heading('My Subject & Class Requests')
            ->description('Overview of your subject selections and current admin approval status.')
            ->columns([
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('classroom.name')
                    ->label('Class')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Approved',
                        'pending' => 'Pending Approval',
                        'rejected' => 'Rejected',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('rejection_reason')
                    ->label('Admin Notes / Reason')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('session.name')
                    ->label('Session')
                    ->sortable(),

                TextColumn::make('term.name')
                    ->label('Term')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Submitted On')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('cancel')
                    ->label('Cancel Request')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Subject Request')
                    ->modalDescription('Are you sure you want to cancel this pending subject request?')
                    ->visible(fn (TeacherSubjectAssignment $record) => $record->status === 'pending')
                    ->action(function (TeacherSubjectAssignment $record) {
                        $record->delete();
                        Notification::make()
                            ->title('Request Cancelled')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
