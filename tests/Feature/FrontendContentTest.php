<?php

use App\Models\FrontendContent;
use App\Models\Setting;
use App\Services\FrontendLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// FrontendLibrary service
// ─────────────────────────────────────────────────────────────────────────────

describe('FrontendLibrary', function () {

    it('returns the default when the key does not exist in the database', function () {
        $result = FrontendLibrary::get('nonexistent_key', 'my-default');

        expect($result)->toBe('my-default');
    });

    it('returns null as default when no fallback is provided and key is missing', function () {
        $result = FrontendLibrary::get('nonexistent_key');

        expect($result)->toBeNull();
    });

    it('returns the stored value when the key exists', function () {
        FrontendContent::create([
            'key'   => 'hero_tagline',
            'group' => 'home.hero',
            'value' => 'Welcome to Test School',
        ]);

        $result = FrontendLibrary::get('hero_tagline', 'fallback');

        expect($result)->toBe('Welcome to Test School');
    });

    it('prefers the database value over the provided default', function () {
        FrontendContent::create([
            'key'   => 'hero_heading',
            'group' => 'home.hero',
            'value' => 'Custom Heading From DB',
        ]);

        $result = FrontendLibrary::get('hero_heading', 'Hardcoded Default');

        expect($result)->toBe('Custom Heading From DB');
    });

    it('returns the updated value after a record is changed', function () {
        $record = FrontendContent::create([
            'key'   => 'cta_enrol',
            'group' => 'home.cta',
            'value' => 'Apply Now',
        ]);

        expect(FrontendLibrary::get('cta_enrol'))->toBe('Apply Now');

        $record->update(['value' => 'Enrol Today']);

        expect(FrontendLibrary::get('cta_enrol'))->toBe('Enrol Today');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// FrontendContent model
// ─────────────────────────────────────────────────────────────────────────────

describe('FrontendContent model', function () {

    it('can be created with key, group and value', function () {
        $record = FrontendContent::create([
            'key'   => 'test_key',
            'group' => 'test.group',
            'value' => 'Test Value',
        ]);

        expect($record->key)->toBe('test_key')
            ->and($record->group)->toBe('test.group')
            ->and($record->value)->toBe('Test Value');
    });

    it('persists to the database', function () {
        FrontendContent::create([
            'key'   => 'persist_test',
            'group' => 'testing',
            'value' => 'Hello World',
        ]);

        $this->assertDatabaseHas('frontend_contents', [
            'key'   => 'persist_test',
            'value' => 'Hello World',
        ]);
    });

    it('allows null group', function () {
        $record = FrontendContent::create([
            'key'   => 'ungrouped_key',
            'group' => null,
            'value' => 'No group here',
        ]);

        $this->assertDatabaseHas('frontend_contents', [
            'key'   => 'ungrouped_key',
            'group' => null,
        ]);
    });

    it('allows null value', function () {
        $record = FrontendContent::create([
            'key'   => 'empty_value_key',
            'group' => 'testing',
            'value' => null,
        ]);

        $this->assertDatabaseHas('frontend_contents', [
            'key'   => 'empty_value_key',
        ]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Home page reflects dynamic content
// ─────────────────────────────────────────────────────────────────────────────

describe('Home page dynamic content', function () {

    it('shows the tenant school name from settings', function () {
        Setting::create(['key' => 'school_name', 'value' => 'Wonders Kiddies Foundation Schools']);

        get('/')->assertSee('Wonders Kiddies Foundation Schools');
    });

    it('shows a custom hero heading when stored in frontend_contents', function () {
        FrontendContent::create([
            'key'   => 'hero_heading',
            'group' => 'home.hero',
            'value' => 'Welcome to Sunrise Academy',
        ]);

        get('/')->assertSee('Welcome to Sunrise Academy');
    });

    it('shows the default heading when no database record exists', function () {
        get('/')
            ->assertSee('A Foundation That')
            ->assertSee('Builds Futures.');
    });

    it('shows custom heading highlight when overridden', function () {
        FrontendContent::create([
            'key'   => 'hero_heading_highlight',
            'group' => 'home.hero',
            'value' => 'Shapes Champions.',
        ]);

        get('/')->assertSee('Shapes Champions.');
    });

    it('shows default pillar labels', function () {
        get('/')
            ->assertSee('Science Laboratory')
            ->assertSee('Practical Work')
            ->assertSee('Information Technology')
            ->assertSee('Creative Arts');
    });

    it('reflects updated pillar label', function () {
        FrontendContent::create([
            'key'   => 'pillar_1_label',
            'group' => 'home.pillars',
            'value' => 'Accredited Lab',
        ]);

        get('/')->assertSee('Accredited Lab');
    });

    it('shows default Why Us heading', function () {
        get('/')->assertSee('What We Do');
    });

    it('shows default stats labels', function () {
        get('/')
            ->assertSee('Years of Excellence')
            ->assertSee('Happy Students')
            ->assertSee('Expert Staff');
    });

    it('shows custom stat value when overridden', function () {
        FrontendContent::create([
            'key'   => 'stat_1_value',
            'group' => 'home.stats',
            'value' => '20+',
        ]);

        get('/')->assertSee('20+');
    });

    it('shows default CTA buttons', function () {
        get('/')
            ->assertSee('Explore Our Campus')
            ->assertSee('Enrol Now')
            ->assertSee('Chat on WhatsApp');
    });

    it('shows updated CTA enrol text', function () {
        FrontendContent::create([
            'key'   => 'cta_enrol',
            'group' => 'home.cta',
            'value' => 'Apply Today',
        ]);

        get('/')->assertSee('Apply Today');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// About page reflects dynamic content
// ─────────────────────────────────────────────────────────────────────────────

describe('About page dynamic content', function () {

    it('shows default about hero title when no record exists', function () {
        get('/about-us')->assertSee('We Build Foundations That Last.');
    });

    it('shows custom about hero title from database', function () {
        FrontendContent::create([
            'key'   => 'about_hero_title',
            'group' => 'about',
            'value' => 'Building Tomorrow\'s Leaders Today.',
        ]);

        get('/about-us')->assertSee("Building Tomorrow's Leaders Today.");
    });

    it('shows default mission text', function () {
        get('/about-us')->assertSee('Our Mission');
    });

    it('shows custom mission title from database', function () {
        FrontendContent::create([
            'key'   => 'about_mission_title',
            'group' => 'about.mission',
            'value' => 'Our Purpose',
        ]);

        get('/about-us')->assertSee('Our Purpose');
    });

    it('shows default core values heading', function () {
        get('/about-us')->assertSee('Our Core Values');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Academics page reflects dynamic content
// ─────────────────────────────────────────────────────────────────────────────

describe('Academics page dynamic content', function () {

    it('shows default academics hero title', function () {
        // Default in the view when no DB record exists
        get('/academics')->assertSee('The WKFS Advantage');
    });

    it('shows custom academics hero title from database', function () {
        FrontendContent::create([
            'key'   => 'advantage_hero_title',
            'group' => 'academics',
            'value' => 'Our Curriculum Excellence',
        ]);

        get('/academics')->assertSee('Our Curriculum Excellence');
    });

    it('shows default EYFS title', function () {
        get('/academics')->assertSee('Early Years Foundation Stage (EYFS)');
    });

    it('shows default subjects section', function () {
        get('/academics')
            ->assertSee('Literacy & Communication')
            ->assertSee('Numeracy & Logic');
    });

    it('shows custom subject title when overridden', function () {
        FrontendContent::create([
            'key'   => 'subject_literacy_title',
            'group' => 'academics.subjects',
            'value' => 'Reading & Writing Mastery',
        ]);

        get('/academics')->assertSee('Reading & Writing Mastery');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Content isolation (simulated with clean DB between tests)
// ─────────────────────────────────────────────────────────────────────────────

describe('Content isolation', function () {

    it('does not bleed content between independent test runs', function () {
        // First "tenant" inserts its content
        FrontendContent::create([
            'key'   => 'hero_tagline',
            'group' => 'home.hero',
            'value' => 'Tenant A School',
        ]);

        expect(FrontendLibrary::get('hero_tagline'))->toBe('Tenant A School');

        // After RefreshDatabase rolls back, the record is gone
        // (next test starts fresh — verified by the default fallback test above)
    });

    it('returns the fallback when the table is empty', function () {
        expect(FrontendContent::count())->toBe(0);

        $result = FrontendLibrary::get('hero_tagline', 'Default School Name');

        expect($result)->toBe('Default School Name');
    });
});
