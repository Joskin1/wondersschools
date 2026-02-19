<?php

namespace App\Filament\Sudo\Resources\SchoolResource\Pages;

use App\Filament\Sudo\Resources\SchoolResource;
use App\Services\TenantProvisioner;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

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
        $data['database_name'] = TenantProvisioner::generateDatabaseName($data['name']);
        $data['database_username'] = TenantProvisioner::generateDatabaseUsername($data['name']);
        $data['database_password'] = TenantProvisioner::generateDatabasePassword();
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }

    /**
     * After the school record is created in the central DB,
     * provision the tenant database.
     */
    protected function afterCreate(): void
    {
        $school = $this->record;

        // Provision the tenant database
        try {
            $provisioner = app(TenantProvisioner::class);
            $provisioner->provision($school);

            Notification::make()
                ->title('School Created & Provisioned')
                ->body("Database '{$school->database_name}' has been created and initialized with a default admin user.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            // Delete the school record if provisioning fails
            $school->delete();

            Notification::make()
                ->title('Provisioning Failed')
                ->body("Failed to create database: {$e->getMessage()}")
                ->danger()
                ->send();
        }
    }
}
