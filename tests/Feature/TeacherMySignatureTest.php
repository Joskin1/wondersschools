<?php

declare(strict_types=1);

use App\Filament\Teacher\Pages\MySignature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('teacher can access and mount MySignature page without 500 error', function () {
    $teacher = User::factory()->create([
        'role'             => 'teacher',
        'is_active'        => true,
        'is_primary_admin' => false,
    ]);

    $this->actingAs($teacher);

    Livewire::test(MySignature::class)
        ->assertSuccessful()
        ->assertFormExists();
});

test('teacher can upload signature on MySignature page', function () {
    $teacher = User::factory()->create([
        'role'             => 'teacher',
        'is_active'        => true,
        'is_primary_admin' => false,
    ]);

    $this->actingAs($teacher);

    Livewire::test(MySignature::class)
        ->fillForm([
            'signature' => ['signatures/teacher_sig.png'],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Signature Saved Successfully');

    expect($teacher->fresh()->signature)->toBe('signatures/teacher_sig.png');
});
