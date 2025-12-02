<?php

namespace App\Filament\Pages;

use App\Models\AcademicSession;
use App\Models\Term;
use App\Services\MigrationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class ManageSystemSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected string $view = 'filament.pages.manage-system-settings';

    public $currentSessionId;
    public $currentTermId;
    public $targetTermId;

    public function mount(MigrationService $migrationService)
    {
        $this->currentSessionId = $migrationService->getCurrentSessionId();
        $this->currentTermId = $migrationService->getCurrentTermId();
    }

    public function migrate()
    {
        $service = app(MigrationService::class);

        try {
            $service->migrateTerm($this->targetTermId);
            
            Notification::make()
                ->title('Migration Successful')
                ->success()
                ->send();

            $this->redirect(static::getUrl());
        } catch (\Exception $e) {
            Notification::make()
                ->title('Migration Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('migrate')
                ->label('Migrate Term')
                ->form([
                    Select::make('targetTermId')
                        ->label('Target Term')
                        ->options(Term::pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->targetTermId = $data['targetTermId'];
                    $this->migrate();
                }),
        ];
    }
}
