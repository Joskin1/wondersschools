<?php

use App\Livewire\Contact;
use App\Models\ContactSubmission;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('Contact Page', function () {
    it('displays the contact page successfully', function () {
        get('/contact-us')
            ->assertOk()
            ->assertSeeLivewire(Contact::class);
    });

    it('shows the contact page headline', function () {
        get('/contact-us')
            ->assertSee('Contact Us')
            ->assertSee('We\'d love to hear from you', false);
    });

    it('displays school contact information', function () {
        get('/contact-us')
            ->assertSee('Get in Touch');
    });

    it('shows the contact form', function () {
        get('/contact-us')
            ->assertSee('Send a Message')
            ->assertSee('Name')
            ->assertSee('Email')
            ->assertSee('Message');
    });

    it('displays Google Maps embed', function () {
        get('/contact-us')
            ->assertSee('google.com/maps');
    });
});

describe('Contact Form Submission', function () {
    it('creates a contact submission with valid data', function () {
        // Arrange
        $contactData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'message' => 'I have a question about your curriculum',
        ];

        // Act
        Livewire::test(Contact::class)
            ->set('name', $contactData['name'])
            ->set('email', $contactData['email'])
            ->set('message', $contactData['message'])
            ->call('submit');

        // Assert
        assertDatabaseHas('contact_submissions', [
            'name' => $contactData['name'],
            'email' => $contactData['email'],
            'message' => $contactData['message'],
            'status' => 'new',
        ]);
    });

    it('validates required fields', function () {
        Livewire::test(Contact::class)
            ->set('name', '')
            ->set('email', '')
            ->set('message', '')
            ->call('submit')
            ->assertHasErrors(['name', 'email', 'message']);
    });

    it('validates email format', function () {
        Livewire::test(Contact::class)
            ->set('name', 'Jane Smith')
            ->set('email', 'not-an-email')
            ->set('message', 'Test message')
            ->call('submit')
            ->assertHasErrors(['email']);
    });

    it('clears form after successful submission', function () {
        Livewire::test(Contact::class)
            ->set('name', 'Jane Smith')
            ->set('email', 'jane@example.com')
            ->set('message', 'Test message')
            ->call('submit')
            ->assertSet('name', null)
            ->assertSet('email', null)
            ->assertSet('message', null);
    });

    it('sets status to new for new submissions', function () {
        Livewire::test(Contact::class)
            ->set('name', 'Jane Smith')
            ->set('email', 'jane@example.com')
            ->set('message', 'Test message')
            ->call('submit');

        $submission = ContactSubmission::latest()->first();
        expect($submission->status)->toBe('new');
    });

    it('displays success message after submission', function () {
        Livewire::test(Contact::class)
            ->set('name', 'Jane Smith')
            ->set('email', 'jane@example.com')
            ->set('message', 'Test message')
            ->call('submit')
            ->assertSee('Thank you for contacting us. We will get back to you soon.', false);
    });
});
