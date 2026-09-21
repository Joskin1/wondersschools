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

test('admin settings page mounts with seeded data across all 8 tabs', function () {
    Livewire::test(Settings::class)
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'school_name' => 'Apex Crown College',
            'hero_title'  => 'Nurturing Intellectual Depth & Moral Leadership',
        ]);
});

test('admin can save school identity settings (Tab 1) and see them on home page', function () {
    Livewire::test(Settings::class)
        ->fillForm([
            'school_name' => 'St. Augustine Premier College',
            'school_established' => '1995',
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified('Settings saved successfully');

    expect(Setting::where('key', 'school_name')->value('value'))->toBe('St. Augustine Premier College');
    expect(Setting::where('key', 'school_established')->value('value'))->toBe('1995');

    // Reload / and assert change appears without manual cache flush
    get('/')
        ->assertSuccessful()
        ->assertSee('St. Augustine Premier College')
        ->assertSee('Est. 1995');
});

test('admin can save branding colors (Tab 2) and see them in :root styles on home page', function () {
    Livewire::test(Settings::class)
        ->fillForm([
            'primary_color' => '#1A365D',
            'accent_color'  => '#E2B93B',
        ])
        ->call('submit')
        ->assertNotified('Settings saved successfully');

    get('/')
        ->assertSuccessful()
        ->assertSee('--ink:     #1A365D;', false)
        ->assertSee('--accent:  #E2B93B;', false);
});

test('admin can save navigation & portals labels (Tab 3) and see them on home page', function () {
    Livewire::test(Settings::class)
        ->fillForm([
            'nav_about_label' => 'Our Heritage',
            'topbar_badge'    => 'Open Day 2026',
        ])
        ->call('submit')
        ->assertNotified('Settings saved successfully');

    expect(FrontendContent::where('key', 'nav_about_label')->value('value'))->toBe('Our Heritage');

    get('/')
        ->assertSuccessful()
        ->assertSee('Our Heritage')
        ->assertSee('Open Day 2026');
});

test('admin can save home page content & repeaters (Tab 4) and see them on home page', function () {
    Livewire::test(Settings::class)
        ->fillForm([
            'hero_title'   => 'Empowering Future Leaders & Innovators',
            'about_heading'=> 'A Century of Scholastic Distinction',
        ])
        ->call('submit')
        ->assertNotified('Settings saved successfully');

    expect(FrontendContent::where('key', 'hero_title')->value('value'))->toBe('Empowering Future Leaders & Innovators');

    get('/')
        ->assertSuccessful()
        ->assertSee('Empowering Future Leaders & Innovators')
        ->assertSee('A Century of Scholastic Distinction');
});

test('admin can save contact & inquiry form configuration (Tab 5) and see them on home page', function () {
    Livewire::test(Settings::class)
        ->fillForm([
            'contact_heading'    => 'Visit Our Victoria Island Campus',
            'contact_form_title' => 'Request an Admissions Dossier',
        ])
        ->call('submit')
        ->assertNotified('Settings saved successfully');

    expect(FrontendContent::where('key', 'contact_heading')->value('value'))->toBe('Visit Our Victoria Island Campus');

    get('/')
        ->assertSuccessful()
        ->assertSee('Visit Our Victoria Island Campus')
        ->assertSee('Request an Admissions Dossier');
});

test('admin can save footer & colophon (Tab 6) and see them on home page', function () {
    Livewire::test(Settings::class)
        ->fillForm([
            'footer_description'   => 'A prestigious institution shaping leaders of tomorrow through holistic education.',
            'footer_edition_label' => '2026 Prospectus Handbook',
        ])
        ->call('submit')
        ->assertNotified('Settings saved successfully');

    expect(FrontendContent::where('key', 'footer_description')->value('value'))->toBe('A prestigious institution shaping leaders of tomorrow through holistic education.');

    get('/')
        ->assertSuccessful()
        ->assertSee('A prestigious institution shaping leaders of tomorrow through holistic education.')
        ->assertSee('2026 Prospectus Handbook');
});

test('admin can save social links (Tab 7)', function () {
    Livewire::test(Settings::class)
        ->fillForm([
            'footer_social_facebook'  => 'https://facebook.com/apexcrown',
            'footer_social_instagram' => 'https://instagram.com/apexcrown',
        ])
        ->call('submit')
        ->assertNotified('Settings saved successfully');

    expect(Setting::where('key', 'footer_social_facebook')->value('value'))->toBe('https://facebook.com/apexcrown');
    expect(Setting::where('key', 'footer_social_instagram')->value('value'))->toBe('https://instagram.com/apexcrown');
});

test('admin can save SEO settings (Tab 8)', function () {
    Livewire::test(Settings::class)
        ->fillForm([
            'seo_title'       => 'Premier British-Nigerian College in Lagos',
            'seo_description' => 'Discover world-class secondary education, modern STEM laboratories, and leadership development.',
        ])
        ->call('submit')
        ->assertNotified('Settings saved successfully');

    expect(Setting::where('key', 'seo_title')->value('value'))->toBe('Premier British-Nigerian College in Lagos');
    expect(Setting::where('key', 'seo_description')->value('value'))->toBe('Discover world-class secondary education, modern STEM laboratories, and leadership development.');
});

test('admin can save individual section separately without saving entire page', function () {
    Livewire::test(Settings::class)
        ->fillForm([
            'hero_title' => 'Standalone Saved Hero Title',
        ])
        ->call('saveSectionKeys', ['hero_title'], 'Hero Section')
        ->assertNotified('Hero Section saved successfully');

    expect(FrontendContent::where('key', 'hero_title')->value('value'))->toBe('Standalone Saved Hero Title');

    get('/')
        ->assertSuccessful()
        ->assertSee('Standalone Saved Hero Title');
});

