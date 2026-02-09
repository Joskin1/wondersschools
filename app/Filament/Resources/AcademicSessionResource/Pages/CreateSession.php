<?php

namespace App\Filament\Resources\AcademicSessionResource\Pages;

use App\Filament\Resources\AcademicSessionResource;
use App\Models\Session;
use Filament\Resources\Pages\CreateRecord;

class CreateSession extends CreateRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-create the three terms when creating a session
        return $data;
    }

    protected function afterCreate(): void
    {
        // Create three terms for this session
        $session = $this->record;
        
        foreach (['First Term' => 1, 'Second Term' => 2, 'Third Term' => 3] as $name => $order) {
            \App\Models\Term::create([
                'session_id' => $session->id,
                'name' => $name,
                'order' => $order,
                'is_active' => false,
            ]);
        }
    }
}
