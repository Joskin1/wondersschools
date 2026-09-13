<?php

use App\Livewire\Home;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('Home Page', function () {
    it('displays the home page successfully', function () {
        get('/')
            ->assertOk()
            ->assertSeeLivewire(Home::class);
    });

    // TODO Phase 1: restore dynamic binding test when CMS keys are registered
    it('shows the hero section with school name', function () {
        Setting::create(['key' => 'school_name', 'value' => 'Wonders Kiddies Foundation Schools']);

        get('/')
            ->assertSee('Wonders Kiddies Foundation Schools')
            ->assertSee('Nurturing Intellectual Depth & Moral Leadership');
    });

    // TODO Phase 1: restore dynamic binding test when CMS keys are registered
    it('displays the pillars of the school', function () {
        get('/')
            ->assertSee('Integrated Dual Curriculum')
            ->assertSee('Individualized Tutorial Mentorship')
            ->assertSee('Applied STEM & Computational Thinking');
    });

    // TODO Phase 1: restore dynamic binding test when CMS keys are registered
    it('shows the What We Do section', function () {
        get('/')
            ->assertSee('DISTINCTIVES')
            ->assertSee('Integrated Dual Curriculum')
            ->assertSee('Individualized Tutorial Mentorship');
    });

    // TODO Phase 1: restore dynamic binding test when CMS keys are registered
    it('displays statistics section', function () {
        get('/')
            ->assertSee('EXAMINATION OUTCOMES')
            ->assertSee('100%')
            ->assertSee('WAEC Pass Rate');
    });

    // TODO Phase 1: restore dynamic binding test when CMS keys are registered
    it('shows latest news posts when available', function () {
        get('/')
            ->assertSee('BULLETIN')
            ->assertSee('2026/2027 First Batch National Entrance Examination');
    });

    // TODO Phase 1: restore dynamic binding test when CMS keys are registered
    it('shows call to action buttons', function () {
        get('/')
            ->assertSee('Apply for Admission')
            ->assertSee('Explore Prospectus')
            ->assertSee('Begin Online Application');
    });

    // TODO Phase 1: restore dynamic binding test when CMS keys are registered
    it('displays WhatsApp chat link', function () {
        get('/')
            ->assertSee('Admissions Prospectus Inquiry');
    });
});
