<?php

use App\Models\Setting;
use App\Services\FrontendContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

// ────────────────────────────────────────────────────────────────────────────
// FrontendContentService unit-level tests
// ────────────────────────────────────────────────────────────────────────────

describe('FrontendContentService', function () {
    it('returns null for missing setting key', function () {
        $service = new FrontendContentService();

        expect($service->get('nonexistent_key'))->toBeNull();
    });

    it('returns default value for missing setting key', function () {
        $service = new FrontendContentService();

        expect($service->get('nonexistent_key', 'fallback'))->toBe('fallback');
    });

    it('returns a string setting value', function () {
        Setting::create(['key' => 'school_name', 'value' => 'Test School']);

        $service = new FrontendContentService();

        expect($service->get('school_name'))->toBe('Test School');
    });

    it('auto-decodes a JSON array setting', function () {
        Setting::create(['key' => 'stats', 'value' => json_encode([
            ['value' => '10+', 'label' => 'Years'],
        ])]);

        $service = new FrontendContentService();
        $stats = $service->get('stats');

        expect($stats)->toBeArray()
            ->and($stats[0]['label'])->toBe('Years');
    });

    it('loads all settings in a single query', function () {
        Setting::create(['key' => 'school_name', 'value' => 'Test School']);
        Setting::create(['key' => 'school_phone', 'value' => '+234 800 000 0000']);

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            if (str_contains($query->sql, 'settings')) {
                $queryCount++;
            }
        });

        $service = new FrontendContentService();
        $service->get('school_name');
        $service->get('school_phone');
        $service->get('school_email');  // not set, triggers no extra query

        expect($queryCount)->toBe(1);
    });

    it('does not re-query after initial load', function () {
        Setting::create(['key' => 'school_name', 'value' => 'Test School']);

        $service = new FrontendContentService();
        $service->get('school_name');  // first call loads all settings

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            if (str_contains($query->sql, 'settings')) {
                $queryCount++;
            }
        });

        $service->get('school_name');
        $service->get('school_phone');

        expect($queryCount)->toBe(0);
    });

    it('flush() clears the in-memory cache', function () {
        Setting::create(['key' => 'school_name', 'value' => 'Original Name']);
        $service = new FrontendContentService();
        expect($service->get('school_name'))->toBe('Original Name');

        // Update setting and flush
        Setting::where('key', 'school_name')->update(['value' => 'Updated Name']);
        $service->flush();

        expect($service->get('school_name'))->toBe('Updated Name');
    });

    it('magic __get accessor works like get()', function () {
        Setting::create(['key' => 'school_name', 'value' => 'Magic Test School']);

        $service = new FrontendContentService();

        expect($service->school_name)->toBe('Magic Test School');
    });
});

// ────────────────────────────────────────────────────────────────────────────
// Dynamic page rendering — content driven by settings
// ────────────────────────────────────────────────────────────────────────────

describe('Dynamic frontend content rendering', function () {
    it('renders school name from settings on the home page', function () {
        seedFrontendSettings(['school_name' => 'Sunrise Academy']);

        get('/')->assertSee('Sunrise Academy');
    });

    it('renders updated hero heading after settings change', function () {
        seedFrontendSettings(['hero_heading' => 'Building Tomorrow\'s Leaders.']);

        get('/')->assertSee('Building Tomorrow\'s Leaders.');
    });

    it('renders dynamic trust strip items on home page', function () {
        seedFrontendSettings([
            'trust_items' => json_encode([
                ['label' => 'Award Winning'],
                ['label' => 'Safe Environment'],
            ]),
        ]);

        get('/')
            ->assertSee('Award Winning')
            ->assertSee('Safe Environment')
            ->assertDontSee('Verified Curriculum');
    });

    it('renders dynamic stats on home page', function () {
        seedFrontendSettings([
            'stats' => json_encode([
                ['value' => '5+', 'label' => 'Campuses'],
                ['value' => '1000+', 'label' => 'Graduates'],
            ]),
        ]);

        get('/')
            ->assertSee('Campuses')
            ->assertSee('Graduates');
    });

    it('renders dynamic about heading and tagline', function () {
        seedFrontendSettings([
            'about_heading' => 'Our Story Begins Here.',
            'about_tagline' => 'Sunrise Academy',
        ]);

        get('/about-us')
            ->assertSee('Our Story Begins Here.')
            ->assertSee('Sunrise Academy');
    });

    it('renders dynamic mission and vision from settings', function () {
        seedFrontendSettings([
            'mission_statement' => 'To inspire every learner to achieve their full potential.',
            'vision_statement'  => 'To be the foremost school of choice in the region.',
        ]);

        get('/about-us')
            ->assertSee('inspire every learner')
            ->assertSee('foremost school of choice');
    });

    it('renders dynamic core values from settings', function () {
        seedFrontendSettings([
            'core_values' => json_encode([
                ['title' => 'Creativity',   'description' => ''],
                ['title' => 'Perseverance', 'description' => ''],
            ]),
        ]);

        get('/about-us')
            ->assertSee('Creativity')
            ->assertSee('Perseverance')
            ->assertDontSee('Integrity of Instruction');
    });

    it('renders dynamic academics heading from settings', function () {
        seedFrontendSettings(['academics_heading' => 'World-Class Curriculum']);

        get('/academics')->assertSee('World-Class Curriculum');
    });

    it('renders dynamic learning levels from settings', function () {
        seedFrontendSettings([
            'academics_levels' => json_encode([
                ['title' => 'Infant Class', 'focus' => 'Early play and discovery.', 'outcome' => 'School readiness.'],
            ]),
        ]);

        get('/academics')
            ->assertSee('Infant Class')
            ->assertSee('Early play and discovery.')
            ->assertSee('School readiness.')
            ->assertDontSee('Early Years Foundation Stage');
    });

    it('renders dynamic admissions steps from settings', function () {
        seedFrontendSettings([
            'admissions_steps' => json_encode([
                ['title' => 'Apply Online', 'description' => 'Submit your application on our portal.'],
                ['title' => 'Interview',    'description' => 'Meet with our admissions team.'],
            ]),
        ]);

        get('/admissions')
            ->assertSee('Apply Online')
            ->assertSee('Submit your application on our portal.')
            ->assertSee('Interview')
            ->assertDontSee('Inquire');
    });

    it('renders dynamic contact information from settings', function () {
        seedFrontendSettings([
            'school_address' => '45 Innovation Drive, Abuja',
            'school_phone'   => '+234 900 123 4567',
            'school_email'   => 'hello@sunrise.edu.ng',
        ]);

        get('/contact-us')
            ->assertSee('45 Innovation Drive, Abuja')
            ->assertSee('+234 900 123 4567')
            ->assertSee('hello@sunrise.edu.ng');
    });

    it('renders Google Maps embed when maps_embed_url is set', function () {
        seedFrontendSettings([
            'maps_embed_url' => 'https://www.google.com/maps/embed?pb=CUSTOM_MAP',
        ]);

        get('/contact-us')->assertSee('CUSTOM_MAP');
    });

    it('hides Google Maps embed when maps_embed_url is not set', function () {
        seedFrontendSettings();
        Setting::where('key', 'maps_embed_url')->delete();
        app(FrontendContentService::class)->flush();

        get('/contact-us')->assertDontSee('google.com/maps');
    });

    it('renders WhatsApp button only when social_whatsapp is set', function () {
        seedFrontendSettings(['social_whatsapp' => '+2348012345678']);

        get('/')->assertSee('wa.me/2348012345678');
    });

    it('hides WhatsApp button when social_whatsapp is not configured', function () {
        seedFrontendSettings();
        Setting::where('key', 'social_whatsapp')->delete();
        app(FrontendContentService::class)->flush();

        get('/')->assertDontSee('wa.me');
    });

    it('updated settings are reflected after service flush', function () {
        seedFrontendSettings(['school_name' => 'First School']);

        get('/')->assertSee('First School');

        Setting::where('key', 'school_name')->update(['value' => 'Second School']);
        app(FrontendContentService::class)->flush();

        get('/')->assertSee('Second School');
    });

    it('renders news page school name from settings', function () {
        seedFrontendSettings(['school_name' => 'Harbour Academy']);

        get('/news')->assertSee('Harbour Academy');
    });

    it('renders gallery page school name from settings', function () {
        seedFrontendSettings(['school_name' => 'Harbour Academy']);

        get('/gallery')->assertSee('Harbour Academy');
    });
});
