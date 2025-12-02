<?php

use App\Models\Staff;
use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses()->group('feature', 'models');

test('creates a staff member with valid data', function () {
    $staff = Staff::factory()->create([
        'name' => 'Dr. Jane Smith',
        'role' => 'Head of School',
        'bio' => 'Dr. Smith has over 20 years of experience in education.',
    ]);

    expect($staff->name)->toBe('Dr. Jane Smith')
        ->and($staff->role)->toBe('Head of School')
        ->and($staff->bio)->toContain('20 years')
        ->and($staff->exists)->toBeTrue();
});

test('can have an image', function () {
    $staff = Staff::factory()->create([
        'image' => 'staff/jane-smith.jpg',
    ]);

    expect($staff->image)->toBe('staff/jane-smith.jpg');
});

test('has an order field for sorting', function () {
    $staff = Staff::factory()->create(['order' => 1]);

    expect($staff->order)->toBe(1);
});

test('orders staff by order field', function () {
    $staff1 = Staff::factory()->create(['order' => 2]);
    $staff2 = Staff::factory()->create(['order' => 1]);
    $staff3 = Staff::factory()->create(['order' => 3]);

    $orderedStaff = Staff::orderBy('order')->get();

    expect($orderedStaff->first()->id)->toBe($staff2->id)
        ->and($orderedStaff->last()->id)->toBe($staff3->id);
});

test('allows nullable bio', function () {
    $staff = Staff::factory()->create(['bio' => null]);

    expect($staff->bio)->toBeNull();
});

test('has user relationship', function () {
    $user = User::factory()->create();
    $staff = Staff::factory()->create(['user_id' => $user->id]);

    expect($staff->user)->toBeInstanceOf(User::class)
        ->and($staff->user->id)->toBe($user->id);
});
