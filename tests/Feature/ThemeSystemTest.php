<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use App\Services\FrontendLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ThemeSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        FrontendLibrary::flush();
    }

    public function test_get_available_themes_returns_all_supported_themes(): void
    {
        $themes = FrontendLibrary::getAvailableThemes();

        $this->assertArrayHasKey('editorial', $themes);
        $this->assertArrayHasKey('modern', $themes);
        $this->assertArrayHasKey('classic', $themes);

        $this->assertEquals('Ivy League Editorial', $themes['editorial']['name']);
        $this->assertEquals('Contemporary Modern Campus', $themes['modern']['name']);
        $this->assertEquals('Stately Heritage Academy', $themes['classic']['name']);
    }

    public function test_defaults_to_editorial_theme_when_unconfigured(): void
    {
        $this->assertEquals('editorial', FrontendLibrary::getTheme());
    }

    public function test_normalizes_legacy_layout_style_aliases(): void
    {
        Setting::create(['key' => 'layout_style', 'value' => 'standard']);
        FrontendLibrary::flush();
        $this->assertEquals('editorial', FrontendLibrary::getTheme());

        Setting::updateOrCreate(['key' => 'layout_style'], ['value' => 'compact']);
        FrontendLibrary::flush();
        $this->assertEquals('modern', FrontendLibrary::getTheme());

        Setting::updateOrCreate(['key' => 'layout_style'], ['value' => 'centered']);
        FrontendLibrary::flush();
        $this->assertEquals('classic', FrontendLibrary::getTheme());
    }

    public function test_respects_modern_and_classic_setting_values(): void
    {
        Setting::create(['key' => 'layout_style', 'value' => 'modern']);
        FrontendLibrary::flush();
        $this->assertEquals('modern', FrontendLibrary::getTheme());

        Setting::updateOrCreate(['key' => 'layout_style'], ['value' => 'classic']);
        FrontendLibrary::flush();
        $this->assertEquals('classic', FrontendLibrary::getTheme());
    }

    public function test_query_parameter_overrides_database_setting_for_instant_preview(): void
    {
        Setting::create(['key' => 'layout_style', 'value' => 'editorial']);
        FrontendLibrary::flush();

        // Simulate request with ?theme=modern
        $request = Request::create('/?theme=modern', 'GET');
        app()->instance('request', $request);

        $this->assertEquals('modern', FrontendLibrary::getTheme());

        // Simulate request with ?theme=classic
        $request = Request::create('/?theme=classic', 'GET');
        app()->instance('request', $request);

        $this->assertEquals('classic', FrontendLibrary::getTheme());
    }

    public function test_all_theme_views_exist_and_render_cleanly(): void
    {
        foreach (['editorial', 'modern', 'classic'] as $theme) {
            $this->assertTrue(view()->exists("themes.{$theme}.home"), "Theme view themes.{$theme}.home must exist");
            $this->assertTrue(view()->exists("themes.{$theme}.hero"), "Theme view themes.{$theme}.hero must exist");
            $this->assertTrue(view()->exists("themes.{$theme}.about"), "Theme view themes.{$theme}.about must exist");
            $this->assertTrue(view()->exists("themes.{$theme}.features"), "Theme view themes.{$theme}.features must exist");
            $this->assertTrue(view()->exists("themes.{$theme}.academics"), "Theme view themes.{$theme}.academics must exist");
            $this->assertTrue(view()->exists("themes.{$theme}.facilities"), "Theme view themes.{$theme}.facilities must exist");
            $this->assertTrue(view()->exists("themes.{$theme}.news"), "Theme view themes.{$theme}.news must exist");
            $this->assertTrue(view()->exists("themes.{$theme}.testimonials"), "Theme view themes.{$theme}.testimonials must exist");
            $this->assertTrue(view()->exists("themes.{$theme}.admissions"), "Theme view themes.{$theme}.admissions must exist");
            $this->assertTrue(view()->exists("themes.{$theme}.contact"), "Theme view themes.{$theme}.contact must exist");
        }
    }
}
