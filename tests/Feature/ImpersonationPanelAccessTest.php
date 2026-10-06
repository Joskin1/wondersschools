<?php

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use STS\FilamentImpersonate\Facades\Impersonation;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sudoUser = User::factory()->create([
        'role' => 'sudo',
        'is_active' => true,
    ]);

    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->teacherUser = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
    ]);

    $this->studentUser = User::factory()->create([
        'role' => 'student',
        'is_active' => true,
    ]);

    $this->sudoPanel = Panel::make()->id('sudo');
    $this->adminPanel = Panel::make()->id('admin');
    $this->teacherPanel = Panel::make()->id('teacher');
    $this->studentPanel = Panel::make()->id('student');
});

describe('User canAccessPanel with and without Impersonation', function () {

    it('enforces normal role access when not impersonating', function () {
        expect($this->sudoUser->canAccessPanel($this->sudoPanel))->toBeTrue();
        expect($this->sudoUser->canAccessPanel($this->adminPanel))->toBeTrue();
        expect($this->sudoUser->canAccessPanel($this->teacherPanel))->toBeFalse();
        expect($this->sudoUser->canAccessPanel($this->studentPanel))->toBeFalse();

        expect($this->adminUser->canAccessPanel($this->sudoPanel))->toBeFalse();
        expect($this->adminUser->canAccessPanel($this->adminPanel))->toBeTrue();
        expect($this->adminUser->canAccessPanel($this->teacherPanel))->toBeFalse();
        expect($this->adminUser->canAccessPanel($this->studentPanel))->toBeFalse();

        expect($this->teacherUser->canAccessPanel($this->sudoPanel))->toBeFalse();
        expect($this->teacherUser->canAccessPanel($this->adminPanel))->toBeFalse();
        expect($this->teacherUser->canAccessPanel($this->teacherPanel))->toBeTrue();
        expect($this->teacherUser->canAccessPanel($this->studentPanel))->toBeFalse();

        expect($this->studentUser->canAccessPanel($this->sudoPanel))->toBeFalse();
        expect($this->studentUser->canAccessPanel($this->adminPanel))->toBeFalse();
        expect($this->studentUser->canAccessPanel($this->teacherPanel))->toBeFalse();
        expect($this->studentUser->canAccessPanel($this->studentPanel))->toBeTrue();
    });

    it('strictly forbids impersonated sessions from accessing sudo panel', function () {
        Impersonation::shouldReceive('isImpersonating')->andReturn(true);

        expect($this->teacherUser->canAccessPanel($this->sudoPanel))->toBeFalse();
        expect($this->studentUser->canAccessPanel($this->sudoPanel))->toBeFalse();
        expect($this->adminUser->canAccessPanel($this->sudoPanel))->toBeFalse();
        expect($this->sudoUser->canAccessPanel($this->sudoPanel))->toBeFalse();
    });

    it('allows impersonated users to access admin panel for leave-impersonation banner', function () {
        Impersonation::shouldReceive('isImpersonating')->andReturn(true);

        expect($this->teacherUser->canAccessPanel($this->adminPanel))->toBeTrue();
        expect($this->studentUser->canAccessPanel($this->adminPanel))->toBeTrue();
    });

    it('does not allow impersonated student to access teacher panel', function () {
        Impersonation::shouldReceive('isImpersonating')->andReturn(true);

        expect($this->studentUser->canAccessPanel($this->teacherPanel))->toBeFalse();
    });

    it('does not allow impersonated teacher to access student panel', function () {
        Impersonation::shouldReceive('isImpersonating')->andReturn(true);

        expect($this->teacherUser->canAccessPanel($this->studentPanel))->toBeFalse();
    });
});
