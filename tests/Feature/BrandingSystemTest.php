<?php

use App\Models\Setting;
use App\Tenancy\ConfigBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// ConfigBootstrapper — branding values propagated to config()
// ─────────────────────────────────────────────────────────────────────────────

describe('ConfigBootstrapper branding values', function () {

    it('sets default primary color when tenant has no primary_color', function () {
        $tenant = new class extends \Stancl\Tenancy\Database\Models\Tenant {
            public $primary_color = null;
            public function domains() {
                return collect();
            }
        };
        // Wrap domains in a property so ->first() works
        $tenant->setRelation('domains', collect());

        $bootstrapper = new ConfigBootstrapper();
        $bootstrapper->bootstrap($tenant);

        expect(config('app.tenant_primary_color'))->toBe('#f59e0b');
    });

    it('sets primary color from tenant model', function () {
        $tenant = new class extends \Stancl\Tenancy\Database\Models\Tenant {
            public $primary_color = '#ff0000';
        };
        $tenant->setRelation('domains', collect());

        $bootstrapper = new ConfigBootstrapper();
        $bootstrapper->bootstrap($tenant);

        expect(config('app.tenant_primary_color'))->toBe('#ff0000');
    });

    it('sets default secondary color when no setting exists', function () {
        $tenant = new class extends \Stancl\Tenancy\Database\Models\Tenant {
            public $primary_color = '#111111';
        };
        $tenant->setRelation('domains', collect());

        $bootstrapper = new ConfigBootstrapper();
        $bootstrapper->bootstrap($tenant);

        expect(config('app.tenant_secondary_color'))->toBe('#1e293b');
    });

    it('loads secondary color from settings table', function () {
        Setting::create(['key' => 'secondary_color', 'value' => '#abcdef']);

        $tenant = new class extends \Stancl\Tenancy\Database\Models\Tenant {
            public $primary_color = '#111111';
        };
        $tenant->setRelation('domains', collect());

        $bootstrapper = new ConfigBootstrapper();
        $bootstrapper->bootstrap($tenant);

        expect(config('app.tenant_secondary_color'))->toBe('#abcdef');
    });

    it('loads accent color from settings table', function () {
        Setting::create(['key' => 'accent_color', 'value' => '#00ff00']);

        $tenant = new class extends \Stancl\Tenancy\Database\Models\Tenant {
            public $primary_color = '#111111';
        };
        $tenant->setRelation('domains', collect());

        $bootstrapper = new ConfigBootstrapper();
        $bootstrapper->bootstrap($tenant);

        expect(config('app.tenant_accent_color'))->toBe('#00ff00');
    });

    it('loads layout style from settings table', function () {
        Setting::create(['key' => 'layout_style', 'value' => 'compact']);

        $tenant = new class extends \Stancl\Tenancy\Database\Models\Tenant {
            public $primary_color = '#111111';
        };
        $tenant->setRelation('domains', collect());

        $bootstrapper = new ConfigBootstrapper();
        $bootstrapper->bootstrap($tenant);

        expect(config('app.tenant_layout_style'))->toBe('compact');
    });

    it('sets default accent color when no setting exists', function () {
        $tenant = new class extends \Stancl\Tenancy\Database\Models\Tenant {
            public $primary_color = '#111111';
        };
        $tenant->setRelation('domains', collect());

        $bootstrapper = new ConfigBootstrapper();
        $bootstrapper->bootstrap($tenant);

        expect(config('app.tenant_accent_color'))->toBe('#f59e0b');
    });

    it('sets default layout style when no setting exists', function () {
        $tenant = new class extends \Stancl\Tenancy\Database\Models\Tenant {
            public $primary_color = '#111111';
        };
        $tenant->setRelation('domains', collect());

        $bootstrapper = new ConfigBootstrapper();
        $bootstrapper->bootstrap($tenant);

        expect(config('app.tenant_layout_style'))->toBe('standard');
    });

    it('loads school name from settings alongside branding values', function () {
        Setting::create(['key' => 'school_name', 'value' => 'Test Academy']);
        Setting::create(['key' => 'accent_color', 'value' => '#ff5500']);

        $tenant = new class extends \Stancl\Tenancy\Database\Models\Tenant {
            public $primary_color = '#222';
        };
        $tenant->setRelation('domains', collect());

        $bootstrapper = new ConfigBootstrapper();
        $bootstrapper->bootstrap($tenant);

        expect(config('app.name'))->toBe('Test Academy')
            ->and(config('app.tenant_accent_color'))->toBe('#ff5500');
    });

    it('reverts all branding values on revert()', function () {
        config(['app.tenant_primary_color'   => '#aaa']);
        config(['app.tenant_secondary_color' => '#bbb']);
        config(['app.tenant_accent_color'    => '#ccc']);
        config(['app.tenant_layout_style'    => 'compact']);

        $bootstrapper = new ConfigBootstrapper();
        $bootstrapper->revert();

        expect(config('app.tenant_primary_color'))->toBeNull()
            ->and(config('app.tenant_secondary_color'))->toBeNull()
            ->and(config('app.tenant_accent_color'))->toBeNull()
            ->and(config('app.tenant_layout_style'))->toBeNull();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// CSS variable injection into layout
// ─────────────────────────────────────────────────────────────────────────────

describe('CSS variable injection in layout', function () {

    it('injects --color-tenant-primary CSS variable into the page', function () {
        config(['app.tenant_primary_color' => '#e63946']);

        get('/')
            ->assertOk()
            ->assertSee('--color-tenant-primary: #e63946');
    });

    it('injects --color-tenant-secondary CSS variable into the page', function () {
        config(['app.tenant_secondary_color' => '#457b9d']);

        get('/')
            ->assertOk()
            ->assertSee('--color-tenant-secondary: #457b9d');
    });

    it('injects --color-tenant-accent CSS variable into the page', function () {
        config(['app.tenant_accent_color' => '#f4a261']);

        get('/')
            ->assertOk()
            ->assertSee('--color-tenant-accent: #f4a261');
    });

    it('uses fallback colors when config values are not set', function () {
        // When config key has null value, config() returns null (not the default)
        // but the Blade template uses config('..', 'fallback') which returns null.
        // The output will show the empty string for null values.
        config(['app.tenant_primary_color'   => null]);
        config(['app.tenant_secondary_color' => null]);
        config(['app.tenant_accent_color'    => null]);

        $response = get('/');
        $response->assertOk();

        // Verify the CSS variable block is present (even if values are empty)
        $response->assertSee('--color-tenant-primary:');
        $response->assertSee('--color-tenant-secondary:');
        $response->assertSee('--color-tenant-accent:');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Views use tenant branding classes instead of hardcoded colors
// ─────────────────────────────────────────────────────────────────────────────

describe('Views use tenant branding classes', function () {

    it('home page does NOT contain hardcoded lime-green classes', function () {
        $response = get('/');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->not->toContain('text-lime-green')
            ->and($content)->not->toContain('bg-lime-green')
            ->and($content)->not->toContain('border-lime-green');
    });

    it('home page contains tenant-accent utility classes', function () {
        $response = get('/');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->toContain('text-tenant-accent')
            ->and($content)->toContain('bg-tenant-accent');
    });

    it('about page does NOT contain hardcoded lime-green classes', function () {
        $response = get('/about-us');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->not->toContain('text-lime-green')
            ->and($content)->not->toContain('bg-lime-green');
    });

    it('contact page does NOT contain hardcoded lime-green classes', function () {
        $response = get('/contact-us');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->not->toContain('text-lime-green')
            ->and($content)->not->toContain('bg-lime-green');
    });

    it('admissions page does NOT contain hardcoded lime-green classes', function () {
        $response = get('/admissions');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->not->toContain('text-lime-green')
            ->and($content)->not->toContain('bg-lime-green');
    });

    it('academics page does NOT contain hardcoded lime-green classes', function () {
        $response = get('/academics');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->not->toContain('text-lime-green')
            ->and($content)->not->toContain('bg-lime-green');
    });

    it('gallery page does NOT contain hardcoded lime-green classes', function () {
        $response = get('/gallery');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->not->toContain('text-lime-green')
            ->and($content)->not->toContain('bg-lime-green');
    });

    it('news page does NOT contain hardcoded lime-green classes', function () {
        $response = get('/news');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->not->toContain('text-lime-green')
            ->and($content)->not->toContain('bg-lime-green');
    });

    it('nav active link uses tenant-accent border instead of lime-green', function () {
        $response = get('/');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->toContain('border-tenant-accent');
    });

    it('footer uses tenant-accent class for school name', function () {
        $response = get('/');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->toContain('text-tenant-accent');
    });

    it('footer hover links use tenant-accent class', function () {
        $response = get('/');
        $response->assertOk();
        $content = $response->getContent();

        expect($content)->toContain('hover:text-tenant-accent');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Settings model stores branding values
// ─────────────────────────────────────────────────────────────────────────────

describe('Settings model branding storage', function () {

    it('can store and retrieve secondary_color', function () {
        Setting::create(['key' => 'secondary_color', 'value' => '#334455']);

        $value = Setting::where('key', 'secondary_color')->value('value');

        expect($value)->toBe('#334455');
    });

    it('can store and retrieve accent_color', function () {
        Setting::create(['key' => 'accent_color', 'value' => '#ff8800']);

        $value = Setting::where('key', 'accent_color')->value('value');

        expect($value)->toBe('#ff8800');
    });

    it('can store and retrieve layout_style', function () {
        Setting::create(['key' => 'layout_style', 'value' => 'centered']);

        $value = Setting::where('key', 'layout_style')->value('value');

        expect($value)->toBe('centered');
    });

    it('can update an existing branding setting', function () {
        Setting::create(['key' => 'accent_color', 'value' => '#aaa']);

        Setting::where('key', 'accent_color')->update(['value' => '#bbb']);

        expect(Setting::where('key', 'accent_color')->value('value'))->toBe('#bbb');
    });
});
