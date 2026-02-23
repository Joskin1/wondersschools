<?php

namespace App\Filament\Sudo\Resources\TenantAdminResource\Pages;

use App\Filament\Sudo\Resources\TenantAdminResource;
use App\Mail\TenantAdminCreated;
use App\Models\Tenant;
use App\Models\TenantAdminAssignment;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ManageTenantAdmins extends ManageRecords
{
    protected static string $resource = TenantAdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New School Admin')
                ->using(function (array $data): TenantAdminAssignment {
                    return static::provisionAdmin($data);
                }),
        ];
    }

    /**
     * Create the landlord assignment record, provision the user in the
     * tenant DB, and send credentials. Extracted so both Create and
     * Resend share the same logic without duplication.
     */
    public static function provisionAdmin(array $data, ?TenantAdminAssignment $existing = null): TenantAdminAssignment
    {
        // 1. Upsert the assignment record on the landlord DB.
        $assignment = $existing
            ? tap($existing)->update($data)
            : TenantAdminAssignment::create($data);

        // 2. Resolve tenant + login URL.
        $tenant    = Tenant::on('landlord')->find($data['tenant_id'] ?? $assignment->tenant_id);
        $domain    = $tenant?->domains->first()?->domain;
        $loginUrl  = $domain
            ? (app()->environment('local') ? "http://{$domain}" : "https://{$domain}") . '/admin/login'
            : null;

        // 3. Generate a secure random password (12 chars, letters + numbers, no symbols).
        $plainPassword = Str::password(12, letters: true, numbers: true, symbols: false);

        // 4. Create / reset the user in the tenant DB.
        tenancy()->initialize($tenant);

        $user = User::updateOrCreate(
            ['email' => $data['email'] ?? $assignment->email],
            [
                'name'              => $data['name'] ?? $assignment->name,
                'password'          => Hash::make($plainPassword),
                'role'              => $data['role'] ?? $assignment->role ?? 'admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        tenancy()->end();

        // 5. Email credentials — only when a domain is configured.
        if ($loginUrl) {
            Mail::to($user->email)->send(
                new TenantAdminCreated($user, $plainPassword, $loginUrl)
            );
            $assignment->update(['credentials_sent_at' => now()]);
        }

        return $assignment;
    }
}
