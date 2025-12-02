<?php

use App\Models\Inquiry;
use App\Models\ContactSubmission;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Inquiry Model', function () {
    it('creates an inquiry with valid data', function () {
        // Arrange & Act
        $inquiry = Inquiry::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+234 123 456 7890',
            'child_age' => '5 years',
        ]);

        // Assert
        expect($inquiry->name)->toBe('John Doe')
            ->and($inquiry->email)->toBe('john@example.com')
            ->and($inquiry->status)->toBe('pending')
            ->and($inquiry->exists)->toBeTrue();
    });

    it('has default status of pending', function () {
        $inquiry = Inquiry::factory()->create();

        expect($inquiry->status)->toBe('pending');
    });

    it('allows nullable message', function () {
        $inquiry = Inquiry::factory()->create(['message' => null]);

        expect($inquiry->message)->toBeNull();
    });
});

describe('ContactSubmission Model', function () {
    it('creates a contact submission with valid data', function () {
        // Arrange & Act
        $submission = ContactSubmission::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'message' => 'I have a question',
        ]);

        // Assert
        expect($submission->name)->toBe('Jane Smith')
            ->and($submission->email)->toBe('jane@example.com')
            ->and($submission->message)->toBe('I have a question')
            ->and($submission->exists)->toBeTrue();
    });

    it('has default status of new', function () {
        $submission = ContactSubmission::factory()->create();

        expect($submission->status)->toBe('new');
    });
});

describe('Setting Model', function () {
    it('creates a setting with key and value', function () {
        // Arrange & Act
        $setting = Setting::create([
            'key' => 'school_name',
            'value' => 'Wonders Kiddies Foundation Schools',
        ]);

        // Assert
        expect($setting->key)->toBe('school_name')
            ->and($setting->value)->toBe('Wonders Kiddies Foundation Schools')
            ->and($setting->exists)->toBeTrue();
    });

    it('retrieves setting by key', function () {
        // Arrange
        Setting::create([
            'key' => 'school_email',
            'value' => 'info@wkfs.com',
        ]);

        // Act
        $value = Setting::where('key', 'school_email')->value('value');

        // Assert
        expect($value)->toBe('info@wkfs.com');
    });
});
