<?php

use App\Livewire\Admissions;
use App\Models\Inquiry;
use Livewire\Livewire;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

describe('Admissions Page', function () {
    it('displays the admissions page successfully', function () {
        get('/admissions')
            ->assertOk()
            ->assertSeeLivewire(Admissions::class);
    });

    it('shows the admissions headline', function () {
        get('/admissions')
            ->assertSee('Secure a Brighter Start')
            ->assertSee('In Just Three Steps');
    });

    it('displays all three admission process steps', function () {
        get('/admissions')
            ->assertSee('Book an Inspection/Tour')
            ->assertSee('Application & Assessment')
            ->assertSee('Payment & Placement');
    });

    it('shows the fee schedule section', function () {
        get('/admissions')
            ->assertSee('School Fees')
            ->assertSee('competitive and offers great value');
    });

    it('displays the inquiry form', function () {
        get('/admissions')
            ->assertSee('Admission Inquiry')
            ->assertSee('Parent\'s Name')
            ->assertSee('Email Address')
            ->assertSee('Phone Number')
            ->assertSee('Child\'s Age / Desired Class');
    });
});

describe('Admissions Inquiry Form Submission', function () {
    it('creates an inquiry with valid data', function () {
        // Arrange
        $inquiryData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+234 123 456 7890',
            'child_age' => '5 years / Nursery 2',
            'message' => 'I would like to enroll my child',
        ];

        // Act
        Livewire::test(Admissions::class)
            ->set('name', $inquiryData['name'])
            ->set('email', $inquiryData['email'])
            ->set('phone', $inquiryData['phone'])
            ->set('child_age', $inquiryData['child_age'])
            ->set('message', $inquiryData['message'])
            ->call('submit');

        // Assert
        assertDatabaseHas('inquiries', [
            'name' => $inquiryData['name'],
            'email' => $inquiryData['email'],
            'phone' => $inquiryData['phone'],
            'child_age' => $inquiryData['child_age'],
        ]);
    });

    it('validates required fields', function () {
        Livewire::test(Admissions::class)
            ->set('name', '')
            ->set('email', '')
            ->set('phone', '')
            ->set('child_age', '')
            ->call('submit')
            ->assertHasErrors(['name', 'email', 'phone', 'child_age']);
    });

    it('validates email format', function () {
        Livewire::test(Admissions::class)
            ->set('name', 'John Doe')
            ->set('email', 'invalid-email')
            ->set('phone', '+234 123 456 7890')
            ->set('child_age', '5 years')
            ->call('submit')
            ->assertHasErrors(['email']);
    });

    it('clears form after successful submission', function () {
        Livewire::test(Admissions::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('phone', '+234 123 456 7890')
            ->set('child_age', '5 years')
            ->call('submit')
            ->assertSet('name', null)
            ->assertSet('email', null)
            ->assertSet('phone', null)
            ->assertSet('child_age', null);
    });

    it('displays success message after submission', function () {
        Livewire::test(Admissions::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('phone', '+234 123 456 7890')
            ->set('child_age', '5 years')
            ->call('submit')
            ->assertSee('Thank you for your inquiry');
    });
});
