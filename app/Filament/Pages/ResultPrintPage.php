<?php

namespace App\Filament\Pages;

use App\Models\AcademicSession;
use App\Models\Classroom;
use App\Models\Result;
use App\Models\Student;
use App\Models\Term;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ResultPrintPage extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected string $view = 'filament.pages.result-print-page';
    
    protected static ?string $navigationLabel = 'Print Results';
    
    protected static ?string $title = 'Print Student Results';
    
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-printer';

    public ?int $session_id = null;
    public ?int $term_id = null;
    public ?int $classroom_id = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('session_id')
                    ->label('Academic Session')
                    ->options(AcademicSession::pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable()),
                
                Select::make('term_id')
                    ->label('Term')
                    ->options(Term::pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable()),
                
                Select::make('classroom_id')
                    ->label('Classroom')
                    ->options(Classroom::pluck('name', 'id'))
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable())
                    ->helperText('Leave empty to show all classrooms'),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        $query = Result::query()
            ->with(['student', 'academicSession', 'term', 'classroom']);

        if ($this->session_id) {
            $query->where('academic_session_id', $this->session_id);
        }

        if ($this->term_id) {
            $query->where('term_id', $this->term_id);
        }

        if ($this->classroom_id) {
            $query->where('classroom_id', $this->classroom_id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($record) => $record->student->first_name . ' ' . $record->student->last_name)
                    ->searchable(['students.first_name', 'students.last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.admission_number')
                    ->label('Admission No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Classroom')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->label('Total Score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_score')
                    ->label('Average')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grade')
                    ->label('Grade')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'D', 'E' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Result $record): string => route('student.result.print', [
                        'student' => $record->student_id,
                        'session' => $record->academic_session_id,
                        'term' => $record->term_id,
                    ]))
                    ->openUrlInNewTab(),
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Result $record): string => route('filament.admin.resources.results.view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkAction::make('printAll')
                    ->label('Print Selected')
                    ->icon('heroicon-o-printer')
                    ->action(function ($records) {
                        // This would trigger a batch print job
                        \Filament\Notifications\Notification::make()
                            ->title('Print Job Started')
                            ->success()
                            ->body('Preparing ' . $records->count() . ' results for printing...')
                            ->send();
                    }),
            ])
            ->heading('Student Results')
            ->description('View and print student results. Use filters above to narrow down results.')
            ->defaultSort('student.first_name')
            ->paginated([10, 25, 50, 100]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printAll')
                ->label('Print All Filtered Results')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->disabled(fn () => !$this->session_id || !$this->term_id)
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Print Job Started')
                        ->success()
                        ->body('Preparing all filtered results for printing...')
                        ->send();
                }),
            Action::make('exportExcel')
                ->label('Export to Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->disabled(fn () => !$this->session_id || !$this->term_id)
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Export Started')
                        ->success()
                        ->body('Preparing Excel export...')
                        ->send();
                }),
        ];
    }
}
