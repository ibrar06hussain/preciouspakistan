<?php

namespace Tests\Feature\Phase5;

use App\Models\User;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Services\TourCatalogService;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TourCatalogWorkflowAndValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
    }

    public function test_incomplete_tour_cannot_be_published_without_destinations_and_complete_itinerary(): void
    {
        $manager = User::factory()->staff()->create();
        $manager->assignRole(RbacCatalog::ROLE_TOUR_MANAGER);

        $service = app(TourCatalogService::class);
        $draft = $service->saveTour($manager, null, [
            'title' => 'Incomplete Karakoram Draft',
            'slug' => 'incomplete-karakoram-draft',
            'category' => 'trekking',
            'duration_days' => 3,
            'duration_nights' => 2,
            'summary' => 'Short draft summary',
            'description_html' => '<p>Draft details</p>',
            'pricing_model' => Tour::PRICING_CUSTOM_QUOTE,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $service->publishTour($manager, $draft);
    }

    public function test_invalid_passenger_caps_are_rejected(): void
    {
        $manager = User::factory()->staff()->create();
        $manager->assignRole(RbacCatalog::ROLE_TOUR_MANAGER);

        $this->expectException(InvalidArgumentException::class);

        app(TourCatalogService::class)->saveTour($manager, null, [
            'title' => 'Bad Caps Tour',
            'slug' => 'bad-caps-tour',
            'min_guests' => 10,
            'max_guests' => 4,
            'duration_days' => 2,
            'duration_nights' => 1,
        ]);
    }

    public function test_non_chronological_itinerary_with_gaps_is_rejected(): void
    {
        $manager = User::factory()->staff()->create();
        $manager->assignRole(RbacCatalog::ROLE_TOUR_MANAGER);

        $this->expectException(InvalidArgumentException::class);

        app(TourCatalogService::class)->saveTour($manager, null, [
            'title' => 'Gap Itinerary Tour',
            'slug' => 'gap-itinerary-tour',
            'duration_days' => 3,
            'duration_nights' => 2,
            'itinerary_days' => [
                ['day_number' => 1, 'title' => 'Arrival'],
                ['day_number' => 3, 'title' => 'Skipped Day 2'],
            ],
        ]);
    }

    public function test_zero_price_on_priced_model_is_rejected_and_jodit_html_is_sanitized(): void
    {
        $manager = User::factory()->staff()->create();
        $manager->assignRole(RbacCatalog::ROLE_TOUR_MANAGER);

        $hunza = Destination::query()->where('slug', 'hunza')->firstOrFail();
        $service = app(TourCatalogService::class);

        try {
            $service->saveTour($manager, null, [
                'title' => 'Zero Price Tour',
                'slug' => 'zero-price-tour',
                'pricing_model' => Tour::PRICING_PER_PERSON,
                'base_price_pkr' => 0,
            ]);
            $this->fail('Expected zero price on priced tour to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('greater than zero', $e->getMessage());
        }

        $tour = $service->saveTour($manager, null, [
            'title' => 'Sanitized Hunza Tour',
            'slug' => 'sanitized-hunza-tour',
            'duration_days' => 2,
            'duration_nights' => 1,
            'summary' => 'Clean summary',
            'description_html' => '<p>Welcome to <strong>Hunza</strong></p><script>alert(1)</script>',
            'pricing_model' => Tour::PRICING_CUSTOM_QUOTE,
            'destination_ids' => [$hunza->id],
            'itinerary_days' => [
                ['day_number' => 1, 'title' => 'Day 1', 'description_html' => '<p>Drive</p><img src="x" onerror="alert(2)">'],
                ['day_number' => 2, 'title' => 'Day 2', 'description_html' => '<p>Return</p>'],
            ],
            'stays' => [
                ['destination_id' => $hunza->id, 'check_in_day' => 1, 'nights_count' => 1],
            ],
        ]);

        $this->assertStringContainsString('<strong>Hunza</strong>', (string) $tour->description_html);
        $this->assertStringNotContainsString('<script>', (string) $tour->description_html);
        $this->assertStringNotContainsString('onerror', (string) $tour->itineraryDays->first()->description_html);
        $this->assertSame('Request Quote', $tour->displayPrice('PKR'));

        // Publish, rename slug, verify 301 redirect and archival.
        $published = $service->publishTour($manager, $tour);
        $this->assertTrue($published->isPublished());

        $renamed = $service->saveTour($manager, $published, [
            'title' => 'Sanitized Hunza Tour',
            'slug' => 'karakoram-hunza-tour',
            'summary' => 'Clean summary',
            'description_html' => '<p>Updated</p>',
            'pricing_model' => Tour::PRICING_CUSTOM_QUOTE,
        ]);

        $this->get('/tours/sanitized-hunza-tour')
            ->assertStatus(301)
            ->assertRedirect('/tours/karakoram-hunza-tour');

        $service->archiveTour($manager, $renamed);
        $this->get('/tours/karakoram-hunza-tour')->assertNotFound();
    }
}
