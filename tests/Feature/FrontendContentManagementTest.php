<?php

use App\Filament\Pages\Settings;
use App\Models\FrontendContent;
use App\Models\Setting;
use App\Models\User;
use App\Services\FrontendLibrary;
use Database\Seeders\TenantFrontendContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role'      => 'admin',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    (new TenantFrontendContentSeeder())->run();
    FrontendLibrary::flush();
});

test('1. Clear content with hero kept shows header, hero and footer only without orphaned headings', function () {
    // Execute Action 1: Clear Content (keep_hero = true)
    Livewire::test(Settings::class)
        ->call('clearContent', true)
        ->assertNotified('Website content cleared successfully');

    $response = get('/')->assertSuccessful();

    // Header & Footer present
    $response->assertSee('Apex Crown College');
    $response->assertSee('All Rights Reserved');

    // Hero section present
    $response->assertSee('Nurturing Intellectual Depth & Moral Leadership');

    // Prospectus sections auto-hidden (headings & content omitted)
    $response->assertDontSee('01 &mdash;');
    $response->assertDontSee('A Tradition of Uncompromising Academic Standard');
    $response->assertDontSee('The Pillars of an Apex Crown Education');
    $response->assertDontSee('Ten-Year Record of Scholastic Excellence');
    $response->assertDontSee('Structured Pathways for Secondary Scholars');
    $response->assertDontSee('Purpose-Built Learning & Living Environments');
    $response->assertDontSee('Recent Announcements & Key Dates');
    $response->assertDontSee('Perspectives on an Apex Crown Education');
});

test('2. Clear content without hero shows header and footer only', function () {
    // Execute Action 1: Clear Content (keep_hero = false)
    Livewire::test(Settings::class)
        ->call('clearContent', false)
        ->assertNotified('Website content cleared successfully');

    $response = get('/')->assertSuccessful();

    // Header & Footer present
    $response->assertSee('Apex Crown College');
    $response->assertSee('All Rights Reserved');

    // Hero and all prospectus sections omitted
    $response->assertDontSee('Nurturing Intellectual Depth & Moral Leadership');
    $response->assertDontSee('01 &mdash;');
    $response->assertDontSee('The Pillars of an Apex Crown Education');
});

test('3. Edit three fields, clear, restore previous brings edits back exactly', function () {
    // 1. Make three specific edits
    FrontendContent::updateOrCreate(['key' => 'about_heading'], ['value' => 'Unique Custom Heritage Title']);
    FrontendContent::updateOrCreate(['key' => 'news_heading'], ['value' => 'Custom Breaking Bulletin News']);
    FrontendContent::updateOrCreate(['key' => 'testimonials_heading'], ['value' => 'Custom Community Testimonies']);
    FrontendLibrary::flush();

    get('/')
        ->assertSuccessful()
        ->assertSee('Unique Custom Heritage Title')
        ->assertSee('Custom Breaking Bulletin News')
        ->assertSee('Custom Community Testimonies');

    // 2. Clear content (which automatically snapshots first)
    Livewire::test(Settings::class)
        ->call('clearContent', true);

    get('/')
        ->assertSuccessful()
        ->assertDontSee('Unique Custom Heritage Title')
        ->assertDontSee('Custom Breaking Bulletin News')
        ->assertDontSee('Custom Community Testimonies');

    // 3. Restore previous content from snapshot
    Livewire::test(Settings::class)
        ->call('restorePreviousContent')
        ->assertNotified('Previous content snapshot restored successfully');

    // 4. Assert the three edits returned exactly without manual cache flush
    get('/')
        ->assertSuccessful()
        ->assertSee('Unique Custom Heritage Title')
        ->assertSee('Custom Breaking Bulletin News')
        ->assertSee('Custom Community Testimonies');
});

test('4. Restore defaults returns the full seeded site', function () {
    // Clear everything first
    Livewire::test(Settings::class)->call('clearContent', false);

    get('/')
        ->assertSuccessful()
        ->assertDontSee('A Tradition of Uncompromising Academic Standard');

    // Restore standard defaults
    Livewire::test(Settings::class)
        ->call('restoreDefaultContent')
        ->assertNotified('Standard default content restored successfully');

    // Assert full seeded site returned
    get('/')
        ->assertSuccessful()
        ->assertSee('A Tradition of Uncompromising Academic Standard')
        ->assertSee('The Pillars of an Apex Crown Education')
        ->assertSee('Ten-Year Record of Scholastic Excellence')
        ->assertSee('Structured Pathways for Secondary Scholars')
        ->assertSee('Purpose-Built Learning & Living Environments')
        ->assertSee('Recent Announcements & Key Dates')
        ->assertSee('Perspectives on an Apex Crown Education');
});

test('5. Restore previous after restoring defaults returns to pre-restore state', function () {
    // 1. Set a custom headline
    FrontendContent::updateOrCreate(['key' => 'hero_title'], ['value' => 'Bespoke Pre-Restore Title']);
    FrontendLibrary::flush();

    get('/')->assertSee('Bespoke Pre-Restore Title');

    // 2. Restore defaults (which snapshots first)
    Livewire::test(Settings::class)->call('restoreDefaultContent');
    get('/')->assertSee('Nurturing Intellectual Depth & Moral Leadership');

    // 3. Restore previous to undo the default restore
    Livewire::test(Settings::class)->call('restorePreviousContent');

    // 4. Assert it returned to the bespoke pre-restore title
    get('/')
        ->assertSuccessful()
        ->assertSee('Bespoke Pre-Restore Title');
});

test('6. Section renumbering dynamically recalculates continuous numbers when middle sections are hidden', function () {
    // When all 8 numbered sections are present:
    // About=01, Features=02, Stats=03, Academics=04, Facilities=05, News=06, Testimonials=07, Contact=08
    $response = get('/')->assertSuccessful();
    $response->assertSee('01 &mdash;', false);
    $response->assertSee('02 &mdash;', false);
    $response->assertSee('03 &mdash;', false);
    $response->assertSee('04 &mdash;', false);
    $response->assertSee('05 &mdash;', false);
    $response->assertSee('06 &mdash;', false);
    $response->assertSee('07 &mdash;', false);
    $response->assertSee('08 &mdash;', false);

    // Hide Distinctives (02) and News (06) by emptying their repeaters
    FrontendContent::updateOrCreate(['key' => 'features_items'], ['value' => '[]']);
    FrontendContent::updateOrCreate(['key' => 'news_articles'], ['value' => '[]']);
    FrontendLibrary::flush();

    // Now:
    // About=01
    // Stats=02 (was 03)
    // Academics=03 (was 04)
    // Facilities=04 (was 05)
    // Testimonials=05 (was 07)
    // Contact=06 (was 08)
    $response2 = get('/')->assertSuccessful();
    $response2->assertSee('01 &mdash; ABOUT THE COLLEGE', false);
    $response2->assertSee('02 &mdash; EXAMINATION OUTCOMES', false);
    $response2->assertSee('03 &mdash; CURRICULUM &amp; PROGRAMMES', false);
    $response2->assertSee('04 &mdash; CAMPUS INFRASTRUCTURE', false);
    $response2->assertSee('05 &mdash; VOICES OF PARENTS &amp; ALUMNI', false);
    $response2->assertSee('06 &mdash; CAMPUS VISITATION &amp; INQUIRY', false);
    $response2->assertDontSee('07 &mdash;', false);
    $response2->assertDontSee('08 &mdash;', false);
});

test('7. Content management actions write only to frontend_contents and settings content_snapshot row', function () {
    $userCountBefore = User::count();
    $settingCountBefore = Setting::where('key', '!=', 'content_snapshot')->count();

    Livewire::test(Settings::class)->call('clearContent', true);

    // Assert no operational tables touched
    expect(User::count())->toBe($userCountBefore);
    expect(Setting::where('key', '!=', 'content_snapshot')->count())->toBe($settingCountBefore);
    expect(Setting::where('key', 'content_snapshot')->exists())->toBeTrue();
});
