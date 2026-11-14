<?php

namespace App\Filament\Student\Widgets;

use App\Filament\Student\Resources\LessonNoteResource;
use App\Models\LessonNote;
use Filament\Actions\Action as HeaderAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class StudentRecentLessonNotesWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Recent Approved Lesson Notes';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->role === 'student' && $user->student !== null;
    }

    protected function getTableQuery(): Builder
    {
        $student = auth()->user()?->student;
        if (! $student) {
            return LessonNote::query()->whereRaw('1 = 0');
        }

        $enrollment = $student->currentEnrollment();
        if (! $enrollment) {
            return LessonNote::query()->whereRaw('1 = 0');
        }

        $query = LessonNote::query()
            ->where('classroom_id', $enrollment->classroom_id)
            ->approved()
            ->active()
            ->with(['subject', 'teacher', 'latestVersion']);

        $classroomSubjectIds = $enrollment->classroom?->subjects()->pluck('subjects.id')->toArray() ?? [];
        if (! empty($classroomSubjectIds)) {
            $query->whereIn('subject_id', $classroomSubjectIds);
        }

        return $query->latest('updated_at');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('week_number')
                    ->label('Week')
                    ->badge()
                    ->formatStateUsing(fn ($state) => "Week {$state}"),

                Tables\Columns\TextColumn::make('latestVersion.file_name')
                    ->label('Note / Topic')
                    ->formatStateUsing(function ($state, $record) {
                        $version = $record->latestVersion;
                        if ($version?->isWritten()) {
                            return '📝 ' . ($version->title ?: 'Written Note');
                        }
                        return '📄 ' . ($state ?: 'Document File');
                    })
                    ->limit(40),

                Tables\Columns\TextColumn::make('teacher.surname')
                    ->label('Teacher')
                    ->formatStateUsing(fn ($record) => $record->teacher?->full_name ?? '—'),

                Tables\Columns\TextColumn::make('latestVersion.reviewed_at')
                    ->label('Published')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('download_note')
                    ->label(fn (LessonNote $record) => $record->latestVersion?->isFile() ? 'Download File' : 'Download Note PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (LessonNote $record) => $record->latestVersion?->isFile()
                        ? route('tenant.lesson-note.file', ['version' => $record->latestVersion->id, 'download' => 1])
                        : route('student.lesson-note.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('download_plan')
                    ->label('Plan PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn (LessonNote $record) => route('student.lesson-plan.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (LessonNote $record): bool => $record->getPairedLessonPlan()?->status === 'approved'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('view_all')
                    ->label('View All Notes')
                    ->icon('heroicon-o-arrow-right')
                    ->url(fn () => LessonNoteResource::getUrl('index')),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('No approved lesson notes published yet')
            ->emptyStateDescription('When teachers submit lesson notes and they are approved by the administration, they will appear here.');
    }
}
