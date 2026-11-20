<?php

namespace Tests\Feature\Phase1;

use App\Modules\Cms\Services\SiteSettingsService;
use Database\Seeders\SiteBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_page_renders_seeded_database_content_and_responsive_landmarks(): void
    {
        $this->seed(SiteBootstrapSeeder::class);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Precious Pakistan');
        $response->assertSee('Skip to main content');
        $response->assertSee('mobile-navigation-drawer', false);
        $response->assertSee('viewport', false);
        $response->assertSee('Gilgit-Baltistan');
        $response->assertSee('Azad Jammu &amp; Kashmir', false);
        $response->assertSee('Islamabad Capital Territory');
        $response->assertSee('Sample Preview');
        $response->assertSee('Request Quote');
        $response->assertDontSee('$0');
    }

    public function test_public_landing_page_reflects_database_setting_updates_without_code_changes(): void
    {
        $this->seed(SiteBootstrapSeeder::class);

        app(SiteSettingsService::class)->set(
            'hero_title',
            'Custom Northern Expeditions Edited Live From Database',
            'hero',
            'string',
            true
        );

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Custom Northern Expeditions Edited Live From Database');
    }
}
