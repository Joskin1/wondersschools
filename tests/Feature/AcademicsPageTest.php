<?php

use App\Livewire\Academics;
use function Pest\Laravel\get;

describe('Academics Page', function () {
    it('displays the academics page successfully', function () {
        get('/academics')
            ->assertOk()
            ->assertSeeLivewire(Academics::class);
    });

    it('shows the WKFS advantage headline', function () {
        get('/academics')
            ->assertSee('The WKFS Advantage')
            ->assertSee('A Foundation That Outlasts Trends');
    });

    it('displays the curriculum introduction', function () {
        get('/academics')
            ->assertSee('strategically designed')
            ->assertSee('exceed them');
    });

    it('shows Early Years Foundation Stage information', function () {
        get('/academics')
            ->assertSee('Early Years Foundation Stage (EYFS)')
            ->assertSee('Play-based learning')
            ->assertSee('Building curiosity');
    });

    it('shows Primary School Programme information', function () {
        get('/academics')
            ->assertSee('Primary School Programme')
            ->assertSee('Mastery of core subjects')
            ->assertSee('Fostering independence');
    });

    it('displays literacy and communication subject highlight', function () {
        get('/academics')
            ->assertSee('Literacy & Communication')
            ->assertSee('reading for comprehension');
    });

    it('displays numeracy and logic subject highlight', function () {
        get('/academics')
            ->assertSee('Numeracy & Logic')
            ->assertSee('mathematical reasoning');
    });

    it('displays integrated science subject highlight', function () {
        get('/academics')
            ->assertSee('Integrated Science (STEM)')
            ->assertSee('practical experimentation');
    });

    it('displays character and ethics subject highlight', function () {
        get('/academics')
            ->assertSee('Character & Ethics')
            ->assertSee('core values, empathy, leadership');
    });

    it('shows subject highlights section heading', function () {
        get('/academics')
            ->assertSee('Subject Highlights: Building Mastery');
    });
});
