<?php

use App\Mail\DatabaseBackupMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('determines scheduled targets and supports dry run', function () {
    $this->artisan('db:backup-email --dry-run --force')
        ->expectsOutputToContain('Dry Run: The following databases are queued for backup:')
        ->expectsOutputToContain('Landlord Central System')
        ->assertSuccessful();
});

it('generates compressed backup archive and sends email', function () {
    Mail::fake();

    $this->artisan('db:backup-email --tenant=landlord --recipient=admin@test.com --force')
        ->expectsOutputToContain('Generating compressed archive for Landlord DB')
        ->expectsOutputToContain('Backup archive generated successfully')
        ->expectsOutputToContain('Email delivered successfully')
        ->assertSuccessful();

    Mail::assertSent(DatabaseBackupMail::class, function ($mail) {
        return $mail->hasTo('admin@test.com')
            && count($mail->filesToAttach) === 1
            && str_ends_with($mail->filesToAttach[0]['name'], '.sql.gz');
    });
});

it('resolves recipient from landlord sudo user when not explicitly provided', function () {
    Mail::fake();

    User::create([
        'name' => 'Sudo Admin',
        'email' => 'sudo@wonderschools.test',
        'password' => bcrypt('secret'),
        'role' => 'sudo',
        'is_active' => true,
    ]);

    $this->artisan('db:backup-email --tenant=landlord --force')
        ->assertSuccessful();

    Mail::assertSent(DatabaseBackupMail::class, function ($mail) {
        return $mail->hasTo('sudo@wonderschools.test');
    });
});

it('backs up a specific tenant database when requested', function () {
    Mail::fake();
    \Illuminate\Support\Facades\Bus::fake();

    $tenant = \App\Models\Tenant::create([
        'id' => 'greenwood',
        'name' => 'Greenwood College',
    ]);

    $this->artisan('db:backup-email --tenant=greenwood --recipient=owner@greenwood.test --force')
        ->expectsOutputToContain('Processing Backup: [tenant] Greenwood College')
        ->expectsOutputToContain('Email delivered successfully')
        ->assertSuccessful();

    Mail::assertSent(DatabaseBackupMail::class, function ($mail) {
        return $mail->hasTo('owner@greenwood.test')
            && $mail->tenantName === 'Greenwood College'
            && str_ends_with($mail->filesToAttach[0]['name'], '.sql.gz');
    });
});

