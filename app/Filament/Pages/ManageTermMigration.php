<?php

namespace App\Filament\Pages;

use App\Models\AcademicSession;
use App\Models\SystemSetting;
use App\Models\Term;
use App\Services\MigrationService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use BackedEnum;

class ManageTermMigration extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Term Migration';
    protected static ?string $title = 'Term & Session Migration';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.manage-term-migration';

    public ?int $targetTermId = null;
    public ?string $currentSessionName = null;
    public ?string $currentTermName = null;
    public ?array $allowedTerms = [];

    public function mount(): void
    {
        $this->loadCurrentState();
    }

    protected function loadCurrentState(): void
    {
        $service = new MigrationService();
        $currentSessionId = $service->getCurrentSessionId();
        $currentTermId = $service->getCurrentTermId();

        if ($currentSessionId && $currentTermId) {
            $currentSession = AcademicSession::find($currentSessionId);
            $currentTerm = Term::find($currentTermId);

            $this->currentSessionName = $currentSession?->name ?? 'Not Set';
            $this->currentTermName = $currentTerm?->name ?? 'Not Set';

            // Determine allowed terms based on current term
            $this->allowedTerms = $this->getAllowedTerms($currentTerm?->name);
        } else {
            $this->currentSessionName = 'Not Set';
            $this->currentTermName = 'Not Set';
            $this->allowedTerms = [];
        }
    }

    protected function getAllowedTerms(?string $currentTermName): array
    {
        $transitions = [
            'First Term' => ['Second Term'],
            'Second Term' => ['Third Term'],
            'Third Term' => ['First Term'],
        ];

        $allowedNames = $transitions[$currentTermName] ?? [];
        
        return Term::whereIn('name', $allowedNames)
            ->pluck('name', 'id')
            ->toArray();
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                Select::make('targetTermId')
                    ->label('Migrate to Term')
                    ->options($this->allowedTerms)
                    ->required()
                    ->helperText('Only sequential term transitions are allowed.')
                    ->disabled(empty($this->allowedTerms)),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('migrate')
                ->label('Migrate Term')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Confirm Term Migration')
                ->modalDescription(function () {
                    if (!$this->targetTermId) {
                        return 'Please select a target term.';
                    }

                    $targetTerm = Term::find($this->targetTermId);
                    $message = "You are about to migrate from {$this->currentTermName} to {$targetTerm->name}.";

                    if ($this->currentTermName === 'Third Term' && $targetTerm->name === 'First Term') {
                        $message .= "\n\n⚠️ This will:\n- Increment the academic session\n- Promote all students to the next classroom\n\nThis action cannot be undone.";
                    }

                    return $message;
                })
                ->modalSubmitActionLabel('Confirm Migration')
                ->action(function () {
                    try {
                        $service = new MigrationService();
                        $service->migrateTerm($this->targetTermId);

                        Notification::make()
                            ->title('Migration Successful')
                            ->success()
                            ->body('Term and session have been migrated successfully.')
                            ->send();

                        // Reload current state
                        $this->loadCurrentState();
                        $this->targetTermId = null;
                    } catch (\Exception $e) {
                        \Log::error("Term Migration Failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                        Notification::make()
                            ->title('Migration Failed')
                            ->danger()
                            ->body('An error occurred during migration. Please check the application logs for details or contact support.')
                            ->send();
                    }
                })
                ->disabled(fn () => empty($this->allowedTerms)),
        ];
    }
}
