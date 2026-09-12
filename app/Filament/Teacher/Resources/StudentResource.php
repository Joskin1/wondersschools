<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\StudentResource\Pages;
use App\Models\Classroom;
use App\Models\ClassTeacherAssignment;
use App\Models\Session;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Students';

    protected static ?int $navigationSort = 1;

    /**
     * Only class teachers can access student management in the Teacher Portal.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user || ! $user->isTeacher()) {
            return false;
        }

        return ClassTeacherAssignment::where('teacher_id', $user->id)->exists();
    }

    /**
     * Scope list strictly to students enrolled in the class teacher's assigned classroom(s).
     */
    public static function getEloquentQuery(): Builder
    {
        $teacherId = auth()->id();
        $activeSession = Session::where('is_active', true)->first();

        $assignedClassIds = ClassTeacherAssignment::where('teacher_id', $teacherId)
            ->when($activeSession, fn ($q) => $q->where('session_id', $activeSession->id))
            ->pluck('class_id')
            ->toArray();

        return parent::getEloquentQuery()
            ->whereHas('enrollments', function ($query) use ($assignedClassIds, $activeSession) {
                $query->whereIn('classroom_id', $assignedClassIds)
                    ->when($activeSession, fn ($q) => $q->where('session_id', $activeSession->id));
            })
            ->with(['enrollments.classroom', 'enrollments.session']);
    }

    public static function form(Schema $schema): Schema
    {
        $teacherId = auth()->id();
        $activeSession = Session::where('is_active', true)->first();

        $assignedClassrooms = Classroom::whereIn('id', function ($query) use ($teacherId, $activeSession) {
            $query->select('class_id')
                ->from('class_teacher_assignments')
                ->where('teacher_id', $teacherId)
                ->when($activeSession, fn ($q) => $q->where('session_id', $activeSession->id));
        })->pluck('name', 'id');

        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., John Doe'),

                TextInput::make('initial_password')
                    ->label('Initial Password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->dehydrated(fn ($state): bool => filled($state)),

                Select::make('classroom_id')
                    ->label('Classroom')
                    ->required()
                    ->options($assignedClassrooms)
                    ->default(fn () => $assignedClassrooms->keys()->first())
                    ->searchable()
                    ->preload()
                    ->helperText('Select your assigned classroom for this student.'),

                Select::make('session_id')
                    ->label('Academic Session')
                    ->required()
                    ->options(Session::pluck('name', 'id'))
                    ->default(fn () => $activeSession?->id)
                    ->searchable()
                    ->helperText('Select the academic session for enrollment.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_picture')
                    ->label('')
                    ->circular()
                    ->disk(config('filesystems.upload_disk', 'public'))
                    ->defaultImageUrl(fn (Student $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=6366f1&color=fff&size=40')
                    ->size(40)
                    ->grow(false),

                TextColumn::make('full_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('admission_number')
                    ->label('Adm No')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('registration_status')
                    ->label('Registration')
                    ->badge()
                    ->getStateUsing(function (Student $record): string {
                        if ($record->is_portal_active) {
                            return 'Active';
                        }
                        if ($record->isRegistrationCompleted()) {
                            return 'Awaiting Activation';
                        }

                        return 'Pending';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Awaiting Activation' => 'warning',
                        'Pending' => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Active' => 'heroicon-o-check-circle',
                        'Awaiting Activation' => 'heroicon-o-clock',
                        'Pending' => 'heroicon-o-ellipsis-horizontal-circle',
                    }),

                TextColumn::make('enrollments.classroom.name')
                    ->label('Classroom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Enrolled At')
                    ->date()
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('copy_registration_link')
                    ->label('Copy Registration Link')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->visible(fn (Student $record) => ! $record->is_portal_active)
                    ->modalHeading('Registration Link Information')
                    ->modalContent(function (Student $record) {
                        $rawToken = $record->createRegistrationLink();
                        $url = route('student.register', [
                            'slug' => $record->registration_slug,
                            'token' => $rawToken,
                        ]);
                        $expiresAt = $record->registration_expires_at->format('M d, Y H:i');

                        return view('filament.modals.generated-registration-link', [
                            'url' => $url,
                            'expiresAt' => $expiresAt,
                            'studentId' => $record->id,
                            'admissionNumber' => $record->admission_number,
                            'note' => 'A fresh link was generated. It expires in 3 days and can only be used once.',
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('lg'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view'   => Pages\ViewStudent::route('/{record}'),
        ];
    }
}
