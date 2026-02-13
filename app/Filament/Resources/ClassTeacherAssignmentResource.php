<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassTeacherAssignmentResource\Pages;
use App\Models\ClassTeacherAssignment;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\User;
use Closure;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClassTeacherAssignmentResource extends Resource
{
    protected static ?string $model = ClassTeacherAssignment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Class Teachers';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('teacher_id')
                    ->label('Teacher')
                    ->options(User::activeTeachers()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Only active teachers who have completed registration are shown')
                    ->rules([
                        fn (\Filament\Schemas\Components\Utilities\Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            $sessionId = $get('session_id');
                            if (! $value || ! $sessionId) {
                                return;
                            }

                            $existing = ClassTeacherAssignment::where('teacher_id', $value)
                                ->where('session_id', $sessionId)
                                ->with('classroom')
                                ->first();

                            if (! $existing) {
                                return;
                            }

                            // When editing, ignore the current record
                            $recordId = request()->route('record');
                            if ($recordId && (int) $existing->id === (int) $recordId) {
                                return;
                            }

                            $teacherName = User::find($value)?->name ?? 'This teacher';
                            $className = $existing->classroom?->name ?? 'another class';

                            $fail("{$teacherName} is already assigned as class teacher for {$className} in the selected session.");
                        },
                    ]),

                Select::make('class_id')
                    ->label('Class')
                    ->options(Classroom::active()->ordered()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->rules([
                        fn (\Filament\Schemas\Components\Utilities\Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            $sessionId = $get('session_id');
                            if (! $value || ! $sessionId) {
                                return;
                            }

                            $existing = ClassTeacherAssignment::where('class_id', $value)
                                ->where('session_id', $sessionId)
                                ->with('teacher')
                                ->first();

                            if (! $existing) {
                                return;
                            }

                            // When editing, ignore the current record
                            $recordId = request()->route('record');
                            if ($recordId && (int) $existing->id === (int) $recordId) {
                                return;
                            }

                            $className = Classroom::find($value)?->name ?? 'This class';
                            $teacherName = $existing->teacher?->name ?? 'another teacher';

                            $fail("{$className} already has a class teacher ({$teacherName}) for the selected session.");
                        },
                    ]),

                Select::make('session_id')
                    ->label('Academic Session')
                    ->options(Session::orderBy('start_year', 'desc')->pluck('name', 'id'))
                    ->required()
                    ->default(fn () => Session::active()->first()?->id)
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Class')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('session.name')
                    ->label('Session')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Assigned On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('session_id')
                    ->label('Session')
                    ->options(Session::orderBy('start_year', 'desc')->pluck('name', 'id'))
                    ->default(Session::active()->first()?->id),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Teacher')
                    ->options(User::where('role', 'teacher')->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                \STS\FilamentImpersonate\Actions\Impersonate::make()
                    ->impersonateRecord(fn ($record) => $record->teacher)
                    ->redirectTo('/teacher'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                // No bulk actions - preserve data integrity
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
            'index' => Pages\ListClassTeacherAssignments::route('/'),
            'create' => Pages\CreateClassTeacherAssignment::route('/create'),
            'edit' => Pages\EditClassTeacherAssignment::route('/{record}/edit'),
        ];
    }
}
