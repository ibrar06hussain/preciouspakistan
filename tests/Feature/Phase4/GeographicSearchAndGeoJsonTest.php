<?php

namespace Tests\Feature\Phase4;

use Database\Seeders\PakistanDestinationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographicSearchAndGeoJsonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PakistanDestinationsSeeder::class);
    }

    public function test_destinations_explorer_filters_by_region_and_keyword_and_renders_leaflet_map(): void
    {
        $this->get('/destinations')
            ->assertOk()
            ->assertSee('data-leaflet-map', false)
            ->assertSee('not an exhaustive administrative census')
            ->assertSee('Hunza')
            ->assertSee('Gwadar');

        $this->get('/destinations?region=balochistan')
            ->assertOk()
            ->assertSee('Quetta')
            ->assertSee('Ziarat')
            ->assertSee('Gwadar')
            ->assertDontSee('/destinations/mohenjo-daro');

        $this->get('/destinations?q=Mohenjo')
            ->assertOk()
            ->assertSee('Mohenjo-daro')
            ->assertDontSee('/destinations/muzaffarabad');
    }

    public function test_region_and_destination_pages_render_breadcrumbs_seo_and_nearby_attractions(): void
    {
        $this->get('/regions/gilgit-baltistan')
            ->assertOk()
            ->assertSee('Gilgit-Baltistan')
            ->assertSee('Hunza')
            ->assertSee('Skardu')
            ->assertSee('Shigar');

        $this->get('/destinations/hunza')
            ->assertOk()
            ->assertSee('Gilgit-Baltistan')
            ->assertSee('Baltit Fort')
            ->assertSee('Attabad Lake')
            ->assertSee('Passu Cones')
            ->assertSee('rel="canonical"', false);
    }

    public function test_geojson_api_returns_valid_feature_collection(): void
    {
        $response = $this->getJson('/api/geo/features?region=sindh');

        $response->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonCount(2, 'features');

        $names = collect($response->json('features'))->pluck('properties.name')->all();
        $this->assertContains('Karachi', $names);
        $this->assertContains('Mohenjo-daro', $names);
    }
}
