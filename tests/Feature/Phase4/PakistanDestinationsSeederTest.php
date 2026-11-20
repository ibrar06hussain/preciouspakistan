<?php

namespace Tests\Feature\Phase4;

use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\Region;
use Database\Seeders\PakistanDestinationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PakistanDestinationsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_seven_required_regions_and_twenty_two_required_destinations_are_seeded_under_pakistan(): void
    {
        $this->seed(PakistanDestinationsSeeder::class);

        $expectedRegions = [
            'Punjab',
            'Sindh',
            'Khyber Pakhtunkhwa',
            'Balochistan',
            'Gilgit-Baltistan',
            'Azad Jammu & Kashmir',
            'Islamabad Capital Territory',
        ];

        $this->assertSame(7, Region::query()->count());
        foreach ($expectedRegions as $name) {
            $this->assertDatabaseHas('geo_regions', ['name' => $name, 'is_active' => true]);
        }

        $expectedByRegion = [
            'gilgit-baltistan' => ['hunza', 'gilgit', 'skardu', 'ghizer', 'nagar', 'astore', 'shigar', 'ghanche', 'kharmang'],
            'azad-jammu-and-kashmir' => ['neelum', 'muzaffarabad'],
            'khyber-pakhtunkhwa' => ['swat', 'naran', 'chitral'],
            'punjab' => ['lahore', 'murree'],
            'sindh' => ['karachi', 'mohenjo-daro'],
            'balochistan' => ['quetta', 'ziarat', 'gwadar'],
            'islamabad-capital-territory' => ['islamabad'],
        ];

        foreach ($expectedByRegion as $regionSlug => $destSlugs) {
            $region = Region::query()->where('slug', $regionSlug)->firstOrFail();

            foreach ($destSlugs as $destSlug) {
                $dest = Destination::query()->where('slug', $destSlug)->first();
                $this->assertNotNull($dest, "Expected destination [{$destSlug}] to be seeded.");
                $this->assertSame($region->id, $dest->region_id);
                $this->assertNotNull($dest->latitude);
                $this->assertNotNull($dest->longitude);
                $this->assertNotNull($dest->seo_title);
            }
        }
    }
}
