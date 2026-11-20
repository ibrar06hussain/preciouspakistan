<?php

namespace Tests\Feature\Phase11;

use App\Models\User;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Media\Services\MediaLibraryService;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Modules\Seo\Services\SeoService;
use App\Modules\Tours\Models\Tour;
use Database\Seeders\CmsDefaultPagesSeeder;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleToursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class MediaAndSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(CmsDefaultPagesSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleToursSeeder::class);
    }

    public function test_media_library_optimizes_webp_avif_deduplicates_and_blocks_deleting_referenced_assets(): void
    {
        Storage::fake('uploads');
        $staff = User::factory()->create(['account_type' => AccountType::Staff, 'is_active' => true]);
        $staff->assignRole(RbacCatalog::ROLE_CONTENT_SEO_MANAGER);

        // Generate a real 900x600 PNG in memory so GD converts to WebP, AVIF, and responsive thumbnails.
        $img = imagecreatetruecolor(900, 600);
        $emerald = imagecolorallocate($img, 4, 120, 87);
        imagefill($img, 0, 0, $emerald);
        $tmpPath = tempnam(sys_get_temp_dir(), 'pp_img_').'.png';
        imagepng($img, $tmpPath);
        imagedestroy($img);

        $file1 = new UploadedFile($tmpPath, 'hunza-valley.png', 'image/png', null, true);
        $service = app(MediaLibraryService::class);

        $asset = $service->uploadImage($file1, $staff, 'Hunza Valley Panorama');
        $this->assertNotNull($asset->webp_path);
        $this->assertTrue(Storage::disk('uploads')->exists($asset->webp_path));
        $this->assertArrayHasKey('400', $asset->variants);
        $this->assertArrayHasKey('800', $asset->variants);

        // Uploading identical bytes deduplicates to the exact same asset ID!
        $file2 = new UploadedFile($tmpPath, 'hunza-duplicate.png', 'image/png', null, true);
        $duplicate = $service->uploadImage($file2, $staff);
        $this->assertSame($asset->id, $duplicate->id);

        // Attach usage reference -> deletion MUST be blocked!
        $tour = Tour::query()->firstOrFail();
        $service->attachUsage($asset, Tour::class, $tour->id, 'hero');

        try {
            $service->deleteAsset($staff, $asset);
            $this->fail('Expected RuntimeException when deleting referenced media asset.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('still referenced', $e->getMessage());
        }

        // Remove usage reference -> deletion succeeds cleanly.
        $asset->usages()->delete();
        $service->deleteAsset($staff, $asset);
        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        @unlink($tmpPath);
    }

    public function test_sitemap_robots_truthful_json_ld_and_xss_sanitization(): void
    {
        // Draft/noindex tours MUST NOT appear in sitemap.xml.
        $publishedTour = Tour::query()->where('status', Tour::STATUS_PUBLISHED)->firstOrFail();
        $hiddenTour = Tour::query()->where('status', Tour::STATUS_PUBLISHED)->skip(1)->firstOrFail();
        $hiddenTour->forceFill(['noindex' => true])->save();

        $sitemapResponse = $this->get('/sitemap.xml')->assertOk();
        $sitemapResponse->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $sitemapResponse->assertSee($publishedTour->slug);
        $sitemapResponse->assertDontSee($hiddenTour->slug);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap:');

        // Truthful JSON-LD: zero fabricated review stars/aggregateRating.
        $seo = app(SeoService::class);
        $tourSchema = $seo->tourSchema($publishedTour);
        $this->assertSame('TouristTrip', $tourSchema['@type']);
        $this->assertArrayNotHasKey('aggregateRating', $tourSchema);
        $this->assertArrayNotHasKey('review', $tourSchema);

        $dest = Destination::query()->firstOrFail();
        $destSchema = $seo->destinationSchema($dest);
        $this->assertSame('TouristDestination', $destSchema['@type']);

        // Server-side HTML sanitizer strips <script> and inline event handlers from Jodit editor payloads.
        $sanitizer = app(HtmlSanitizerService::class);
        $clean = $sanitizer->sanitizeRichHtml('<p>Valid <strong>Karimabad</strong> itinerary.</p><script>alert(1)</script><img src="https://example.com/a.jpg" onerror="alert(2)">');
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringContainsString('<strong>Karimabad</strong>', $clean);
    }
}
