<?php

namespace App\Filament\Pages;

use App\Models\AcademicSession;
use App\Models\Classroom;
use App\Models\Result;
use App\Models\Term;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class GenerateResultsPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected string $view = 'filament.pages.generate-results-page';
    
    protected static ?string $navigationLabel = 'Generate Results';
    
    protected static ?string $title = 'Generate Student Results';
    
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-chart-bar';

    public ?int $session_id = null;
    public ?int $term_id = null;
    public ?int $classroom_id = null;
    public bool $regenerate = false;

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
                    ->helperText('Select the academic session'),
                
                Select::make('term_id')
                    ->label('Term')
                    ->options(Term::pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->helperText('Select the term'),
                
                Select::make('classroom_id')
                    ->label('Classroom')
                    ->options(Classroom::pluck('name', 'id'))
                    ->required()
                    ->helperText('Select the classroom (leave empty to generate for all classrooms)'),
                
                Toggle::make('regenerate')
                    ->label('Regenerate Existing Results')
                    ->helperText('If enabled, existing results will be recalculated and updated')
                    ->default(false),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Results')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Confirm Result Generation')
                ->modalDescription(function () {
                    $session = AcademicSession::find($this->session_id);
                    $term = Term::find($this->term_id);
                    $classroom = $this->classroom_id ? Classroom::find($this->classroom_id) : null;
                    
                    $message = "You are about to generate results for:\n\n";
                    $message .= "Session: " . ($session?->name ?? 'Not selected') . "\n";
                    $message .= "Term: " . ($term?->name ?? 'Not selected') . "\n";
                    $message .= "Classroom: " . ($classroom?->name ?? 'All classrooms') . "\n\n";
                    
                    if ($this->regenerate) {
                        $message .= "⚠️ Existing results will be regenerated.";
                    }
                    
                    return $message;
                })
                ->modalSubmitActionLabel('Generate Results')
                ->action(function () {
                    try {
                        $this->validate([
                            'session_id' => 'required|exists:academic_sessions,id',
                            'term_id' => 'required|exists:terms,id',
                        ]);

                        $query = \App\Models\Student::query();
                        
                        if ($this->classroom_id) {
                            $query->whereHas('classrooms', function ($q) {
                                $q->where('classrooms.id', $this->classroom_id);
                            });
                        }
                        
                        $students = $query->get();
                        $generated = 0;
                        $updated = 0;

                        foreach ($students as $student) {
                            $classroom = $student->classrooms()->first();
                            if (!$classroom) continue;

                            $existing = Result::where('student_id', $student->id)
                                ->where('academic_session_id', $this->session_id)
                                ->where('term_id', $this->term_id)
                                ->first();

                            if ($existing && !$this->regenerate) {
                                continue;
                            }

                            // Calculate scores
                            $scores = \App\Models\Score::where('student_id', $student->id)
                                ->where('session', AcademicSession::find($this->session_id)->name)
                                ->where('term', $this->term_id)
                                ->where('classroom_id', $classroom->id)
                                ->get();

                            $totalScore = $scores->sum('value');
                            $averageScore = $scores->count() > 0 ? $totalScore / $scores->count() : 0;

                            if ($existing) {
                                $existing->update([
                                    'total_score' => $totalScore,
                                    'average_score' => $averageScore,
                                ]);
                                $updated++;
                            } else {
                                Result::create([
                                    'student_id' => $student->id,
                                    'academic_session_id' => $this->session_id,
                                    'term_id' => $this->term_id,
                                    'classroom_id' => $classroom->id,
                                    'total_score' => $totalScore,
                                    'average_score' => $averageScore,
                                ]);
                                $generated++;
                            }
                        }

                        Notification::make()
                            ->title('Results Generated Successfully')
                            ->success()
                            ->body("Generated: {$generated} new results, Updated: {$updated} existing results")
                            ->send();

                        $this->form->fill();
                    } catch (\Exception $e) {
                        \Log::error("Result Generation Failed: " . $e->getMessage());
                        Notification::make()
                            ->title('Generation Failed')
                            ->danger()
                            ->body('An error occurred. Please check logs.')
                            ->send();
                    }
                }),
        ];
    }
}
