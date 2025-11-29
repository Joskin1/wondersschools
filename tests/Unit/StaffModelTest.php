<?php

use App\Models\Staff;

describe('Staff Model', function () {
    it('creates a staff member with valid data', function () {
        // Arrange & Act
        $staff = Staff::factory()->create([
            'name' => 'Dr. Jane Smith',
            'role' => 'Head of School',
            'bio' => 'Dr. Smith has over 20 years of experience in education.',
        ]);

        // Assert
        expect($staff->name)->toBe('Dr. Jane Smith')
            ->and($staff->role)->toBe('Head of School')
            ->and($staff->bio)->toContain('20 years')
            ->and($staff->exists)->toBeTrue();
    });

    it('can have an image', function () {
        $staff = Staff::factory()->create([
            'image' => 'staff/jane-smith.jpg',
        ]);

        expect($staff->image)->toBe('staff/jane-smith.jpg');
    });

    it('has an order field for sorting', function () {
        $staff = Staff::factory()->create(['order' => 1]);

        expect($staff->order)->toBe(1);
    });

    it('orders staff by order field', function () {
        // Arrange
        $staff1 = Staff::factory()->create(['order' => 2]);
        $staff2 = Staff::factory()->create(['order' => 1]);
        $staff3 = Staff::factory()->create(['order' => 3]);

        // Act
        $orderedStaff = Staff::orderBy('order')->get();

        // Assert
        expect($orderedStaff->first()->id)->toBe($staff2->id)
            ->and($orderedStaff->last()->id)->toBe($staff3->id);
    });

    it('allows nullable bio', function () {
        $staff = Staff::factory()->create(['bio' => null]);

        expect($staff->bio)->toBeNull();
    });
});
