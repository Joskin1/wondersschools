<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherSubjectAssignmentResource\Pages;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Term;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class TeacherSubjectAssignmentResource extends Resource
{
    protected static ?string $model = TeacherSubjectAssignment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Subject Teachers';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        return $schema
            ->components([
                Select::make('teacher_id')
                    ->label('Teacher')
                    ->options(User::activeTeachers()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Only active teachers who have completed registration are shown'),

                Select::make('classroom_id')
                    ->label('Class')
                    ->options(Classroom::active()->ordered()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('subject_id', null)),

                Select::make('subject_id')
                    ->label('Subject')
                    ->options(Subject::orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('session_id')
                    ->label('Academic Session')
                    ->options(Session::orderBy('start_year', 'desc')->get()->pluck('name', 'id'))
                    ->default($activeSession?->id)
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('term_id', null)),

                Select::make('term_id')
                    ->label('Term')
                    ->options(function (callable $get) {
                        $sessionId = $get('session_id');
                        if (!$sessionId) {
                            return [];
                        }
                        return Term::where('session_id', $sessionId)
                            ->orderBy('order')
                            ->get()
                            ->pluck('name', 'id');
                    })
                    ->default($activeTerm?->id)
                    ->required()
                    ->searchable()
                    ->helperText('Select session first to see available terms'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('classroom.name')
                    ->label('Class')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('session.name')
                    ->label('Session')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'First Term' => 'primary',
                        'Second Term' => 'warning',
                        'Third Term' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Assigned On')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('teacher_id')
                    ->label('Teacher')
                    ->options(User::activeTeachers()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('classroom_id')
                    ->label('Class')
                    ->options(Classroom::ordered()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(Subject::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('session_id')
                    ->label('Session')
                    ->options(Session::orderBy('start_year', 'desc')->get()->pluck('name', 'id'))
                    ->default(Session::active()->first()?->id),

                SelectFilter::make('term_id')
                    ->label('Term')
                    ->options(Term::orderBy('order')->get()->pluck('name', 'id')),
            ])
            ->actions([
                \STS\FilamentImpersonate\Actions\Impersonate::make()
                    ->impersonateRecord(fn ($record) => $record->teacher)
                    ->redirectTo('/teacher'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherSubjectAssignments::route('/'),
            'create' => Pages\CreateTeacherSubjectAssignment::route('/create'),
            'edit' => Pages\EditTeacherSubjectAssignment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $activeSession = Session::active()->first();
        if (!$activeSession || !$activeSession->activeTerm) {
            return null;
        }

        return static::getModel()::where('session_id', $activeSession->id)
            ->where('term_id', $activeSession->activeTerm->id)
            ->count();
    }
}
