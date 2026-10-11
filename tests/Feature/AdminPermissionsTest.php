<?php

use App\Filament\Pages\ManageAdminPermissions;
use App\Filament\Pages\Settings;
use App\Filament\Teacher\Pages\EnterScores;
use App\Models\User;
use App\Services\AdminPermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Explicit primary admin
    $this->primaryAdmin = User::factory()->create([
        'name' => 'Principal Chizy',
        'email' => 'principal@wonders.test',
        'role' => 'admin',
        'is_primary_admin' => true,
        'admin_designation' => 'Principal',
        'is_active' => true,
    ]);

    // Secondary admin (e.g. Bursar)
    $this->secondaryAdmin = User::factory()->create([
        'name' => 'Bursar John',
        'email' => 'bursar@wonders.test',
        'role' => 'admin',
        'is_primary_admin' => false,
        'admin_designation' => 'Bursar',
        'allowed_admin_pages' => ['students', 'inquiries', 'contact_submissions'],
        'is_active' => true,
    ]);

    // Restricted secondary admin without permissions
    $this->restrictedAdmin = User::factory()->create([
        'name' => 'Restricted Admin',
        'email' => 'restricted@wonders.test',
        'role' => 'admin',
        'is_primary_admin' => false,
        'allowed_admin_pages' => [],
        'is_active' => true,
    ]);

    // Teacher
    $this->teacher = User::factory()->create([
        'name' => 'Teacher Oko',
        'email' => 'teacher@wonders.test',
        'role' => 'teacher',
        'is_active' => true,
    ]);

    // Sudo
    $this->sudo = User::factory()->create([
        'name' => 'Joskin Joseph',
        'email' => 'sudo@wonders.test',
        'role' => 'sudo',
        'is_active' => true,
    ]);
});

describe('User Model Admin Permissions', function () {
    it('grants full access to primary admin across all page keys', function () {
        expect($this->primaryAdmin->isPrimaryAdmin())->toBeTrue();
        expect($this->primaryAdmin->canAccessAdminPage('academic_sessions'))->toBeTrue();
        expect($this->primaryAdmin->canAccessAdminPage('settings'))->toBeTrue();
        expect($this->primaryAdmin->canAccessAdminPage('enter_scores'))->toBeTrue();
        expect($this->primaryAdmin->canAccessAdminPage('students'))->toBeTrue();
        expect($this->primaryAdmin->canAccessAdminPage('non_existent_key'))->toBeTrue();
    });

    it('grants full access to sudo user across all page keys', function () {
        expect($this->sudo->canAccessAdminPage('academic_sessions'))->toBeTrue();
        expect($this->sudo->canAccessAdminPage('settings'))->toBeTrue();
    });

    it('restricts secondary admin to only assigned pages', function () {
        expect($this->secondaryAdmin->isPrimaryAdmin())->toBeFalse();

        // Allowed pages
        expect($this->secondaryAdmin->canAccessAdminPage('students'))->toBeTrue();
        expect($this->secondaryAdmin->canAccessAdminPage('inquiries'))->toBeTrue();
        expect($this->secondaryAdmin->canAccessAdminPage('contact_submissions'))->toBeTrue();

        // Denied pages
        expect($this->secondaryAdmin->canAccessAdminPage('academic_sessions'))->toBeFalse();
        expect($this->secondaryAdmin->canAccessAdminPage('settings'))->toBeFalse();
        expect($this->secondaryAdmin->canAccessAdminPage('enter_scores'))->toBeFalse();
        expect($this->secondaryAdmin->canAccessAdminPage('process_class_results'))->toBeFalse();
    });

    it('denies all admin page access to secondary admin with empty permissions', function () {
        expect($this->restrictedAdmin->canAccessAdminPage('students'))->toBeFalse();
        expect($this->restrictedAdmin->canAccessAdminPage('settings'))->toBeFalse();
        expect($this->restrictedAdmin->canAccessAdminPage('academic_sessions'))->toBeFalse();
    });

    it('denies admin page access to non-admin roles', function () {
        expect($this->teacher->canAccessAdminPage('students'))->toBeFalse();
        expect($this->teacher->canAccessAdminPage('settings'))->toBeFalse();
    });

    it('scopes canManageAcademics to permitted academic modules for secondary admins', function () {
        // Bursar has only student and inquiry permissions -> cannot manage academics
        expect($this->secondaryAdmin->canManageAcademics())->toBeFalse();

        // Admin with academic_sessions permission -> can manage academics
        $this->secondaryAdmin->update(['allowed_admin_pages' => ['academic_sessions']]);
        expect($this->secondaryAdmin->fresh()->canManageAcademics())->toBeTrue();
    });
});

describe('Admin Permission Registry', function () {
    it('contains valid grouped modules and all keys', function () {
        $grouped = AdminPermissionRegistry::getPermissionsGrouped();
        $keys = AdminPermissionRegistry::getAllKeys();

        expect($grouped)->toHaveKeys([
            'Academic Management',
            'Results & Assessment',
            'Lessons & Curriculum',
            'User Management',
            'Website & Public Info',
            'System & Configuration',
        ]);

        expect($keys)->toContain('academic_sessions', 'terms', 'classrooms', 'students', 'settings', 'enter_scores');
    });

    it('provides presets with valid registered page keys', function () {
        $allKeys = AdminPermissionRegistry::getAllKeys();
        $presets = AdminPermissionRegistry::getPresets();

        expect($presets)->toHaveKeys(['exam_officer', 'bursar', 'vp_academic', 'front_desk']);

        foreach ($presets as $preset) {
            foreach ($preset['pages'] as $pageKey) {
                expect($allKeys)->toContain($pageKey);
            }
        }
    });
});

describe('Dedicated ManageAdminPermissions Page', function () {
    it('allows primary admin to access ManageAdminPermissions page', function () {
        $this->actingAs($this->primaryAdmin);

        Livewire::test(ManageAdminPermissions::class)
            ->assertSuccessful()
            ->assertSet('selectedUserId', $this->secondaryAdmin->id);
    });

    it('allows sudo user to access ManageAdminPermissions page', function () {
        $this->actingAs($this->sudo);

        Livewire::test(ManageAdminPermissions::class)
            ->assertSuccessful();
    });

    it('blocks secondary admin from accessing ManageAdminPermissions page with 403', function () {
        $this->actingAs($this->secondaryAdmin);

        Livewire::test(ManageAdminPermissions::class)
            ->assertForbidden();
    });

    it('blocks teachers from accessing ManageAdminPermissions page with 403', function () {
        $this->actingAs($this->teacher);

        Livewire::test(ManageAdminPermissions::class)
            ->assertForbidden();
    });

    it('allows primary admin to apply preset and save permissions for secondary admin', function () {
        $this->actingAs($this->primaryAdmin);

        Livewire::test(ManageAdminPermissions::class)
            ->set('selectedUserId', $this->secondaryAdmin->id)
            ->call('applyPreset', 'exam_officer')
            ->call('save')
            ->assertNotified('Permissions updated successfully');

        $this->secondaryAdmin->refresh();
        expect($this->secondaryAdmin->admin_designation)->toBe('Exam Officer');
        expect($this->secondaryAdmin->allowed_admin_pages)->toContain('enter_scores', 'process_class_results', 'score_heads');
        expect($this->secondaryAdmin->canAccessAdminPage('enter_scores'))->toBeTrue();
    });

    it('allows primary admin to toggle permissions individually and select all / deselect all', function () {
        $this->actingAs($this->primaryAdmin);

        $component = Livewire::test(ManageAdminPermissions::class)
            ->set('selectedUserId', $this->secondaryAdmin->id)
            ->call('deselectAll');

        expect($component->get('selectedPermissions'))->toBeEmpty();

        $component->call('selectAll');
        expect($component->get('selectedPermissions'))->toEqual(AdminPermissionRegistry::getAllKeys());

        $component->call('toggleGroup', 'User Management');
        // Since all were selected, toggling User Management should remove students and users
        expect($component->get('selectedPermissions'))->not->toContain('students');
        expect($component->get('selectedPermissions'))->not->toContain('users');
    });

    it('allows sudo user to select an admin email and grant them principal privilege, and revoke it', function () {
        // Initially, restrictedAdmin cannot access ManageAdminPermissions
        $this->actingAs($this->restrictedAdmin);
        expect(ManageAdminPermissions::canAccess())->toBeFalse();

        // Sudo logs in and assigns Principal privilege to restrictedAdmin
        $this->actingAs($this->sudo);
        Livewire::test(ManageAdminPermissions::class)
            ->set('principalUserId', $this->restrictedAdmin->id)
            ->call('assignPrincipal')
            ->assertNotified('Principal Privilege Granted');

        $this->restrictedAdmin->refresh();
        expect($this->restrictedAdmin->is_primary_admin)->toBeTrue();
        expect($this->restrictedAdmin->admin_designation)->toBe('Principal');

        // Now restrictedAdmin CAN access ManageAdminPermissions!
        $this->actingAs($this->restrictedAdmin);
        expect(ManageAdminPermissions::canAccess())->toBeTrue();
        Livewire::test(ManageAdminPermissions::class)->assertSuccessful();

        // Sudo revokes the privilege
        $this->actingAs($this->sudo);
        Livewire::test(ManageAdminPermissions::class)
            ->call('revokePrincipal', $this->restrictedAdmin->id)
            ->assertNotified('Principal Privilege Revoked');

        $this->restrictedAdmin->refresh();
        expect($this->restrictedAdmin->is_primary_admin)->toBeFalse();

        // Now restrictedAdmin CANNOT access ManageAdminPermissions anymore!
        $this->actingAs($this->restrictedAdmin);
        expect(ManageAdminPermissions::canAccess())->toBeFalse();
    });
});

describe('Filament Resource & Page Access Enforcement', function () {
    it('allows primary admin to access restricted settings and score entry pages', function () {
        $this->actingAs($this->primaryAdmin);

        expect(Settings::canAccess())->toBeTrue();
        expect(EnterScores::canAccess())->toBeTrue();
    });

    it('prevents secondary admin without permission from accessing settings and score entry', function () {
        $this->actingAs($this->secondaryAdmin); // Has only students, inquiries, contact_submissions

        expect(Settings::canAccess())->toBeFalse();
        expect(EnterScores::canAccess())->toBeFalse();
    });

    it('grants secondary admin access to settings once permission is granted', function () {
        $this->actingAs($this->secondaryAdmin);

        expect(Settings::canAccess())->toBeFalse();

        $this->secondaryAdmin->update([
            'allowed_admin_pages' => ['settings', 'students'],
        ]);

        $this->secondaryAdmin->refresh();
        expect(Settings::canAccess())->toBeTrue();
    });
});
