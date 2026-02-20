<?php

namespace App\Filament\Sudo\Resources\SchoolResource\Pages;

use App\Filament\Sudo\Resources\SchoolResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    /**
     * Before saving the school record, generate the database credentials.
     * This ensures database_name, database_username, and database_password
     * are populated before the INSERT statement.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $prefix = config('tenancy.database_prefix', 'tenant_');
        $slug   = Str::slug($data['name'], '_');
        $data['database_name']     = $prefix . substr($slug, 0, 32) . '_' . Str::random(6);
        $data['database_username'] = 'tn_' . substr($slug, 0, 10) . '_' . Str::random(4);
        $data['database_password'] = Str::random(32);
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }

    /**
     * After the school record is created in the central DB,
     * the TenancyServiceProvider JobPipeline will automatically
     * provision the database, user, migrations, and seed the admin.
     */
    protected function afterCreate(): void
    {
        $school = $this->record;

        Notification::make()
            ->title('School Created & Provisioned')
            ->body("Database '{$school->database_name}' has been created and initialized with a default admin user.")
            ->success()
            ->send();
    }
}
