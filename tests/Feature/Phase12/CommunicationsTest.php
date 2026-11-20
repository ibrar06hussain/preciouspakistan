<?php

namespace Tests\Feature\Phase12;

use App\Modules\Booking\Models\Booking;
use App\Modules\Communications\Contracts\CommunicationChannelConnector;
use App\Modules\Communications\Services\InquiryService;
use App\Modules\Communications\Services\WhatsAppLinkService;
use App\Modules\Communications\Services\YouTubeEmbedHelper;
use App\Modules\Tours\Models\Tour;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleSocialAccountsSeeder;
use Database\Seeders\SampleToursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class CommunicationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleToursSeeder::class);
        $this->seed(SampleSocialAccountsSeeder::class);
    }

    public function test_whatsapp_click_to_chat_excludes_pii_and_validates_youtube_embeds(): void
    {
        $tour = Tour::query()->where('code', 'PP-GB-HUNZA-05')->firstOrFail();
        $wa = app(WhatsAppLinkService::class);

        $tourLink = $wa->buildTourInquiryUrl($tour);
        $this->assertNotNull($tourLink);
        $this->assertStringStartsWith('https://wa.me/923000000000?text=', $tourLink);
        $this->assertStringContainsString('PP-GB-HUNZA-05', urldecode($tourLink));

        $booking = new Booking([
            'booking_reference' => 'PP-BK-20261120-ABC123',
            'customer_name' => 'Private Guest',
            'customer_email' => 'private@example.com',
            'customer_phone' => '+923009998888',
        ]);
        $bookingLink = $wa->buildBookingSupportUrl($booking);
        $decoded = urldecode((string) $bookingLink);
        $this->assertStringContainsString('PP-BK-20261120-ABC123', $decoded);
        $this->assertStringNotContainsString('private@example.com', $decoded);
        $this->assertStringNotContainsString('Private Guest', $decoded);

        // Safe YouTube nocookie embed conversion + rejection of non-YouTube URLs.
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            YouTubeEmbedHelper::toSafeEmbedUrl('https://youtu.be/dQw4w9WgXcQ')
        );

        $this->expectException(InvalidArgumentException::class);
        YouTubeEmbedHelper::toSafeEmbedUrl('https://evil.example.com/embed/dQw4w9WgXcQ');
    }

    public function test_inquiry_requires_consent_records_retention_and_meta_connector_is_disabled(): void
    {
        $inquiries = app(InquiryService::class);

        try {
            $inquiries->recordInquiry([
                'name' => 'No Consent',
                'email' => 'nc@example.com',
                'message' => 'Hello',
                'consent_accepted' => false,
            ]);
            $this->fail('Expected consent rejection.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('consent', strtolower($e->getMessage()));
        }

        $saved = $inquiries->recordInquiry([
            'name' => 'Sanaullah',
            'email' => 'sana@example.com',
            'message' => 'Interested in Hunza tour in July.',
            'consent_accepted' => true,
        ], null, '203.0.113.5');

        $this->assertStringStartsWith('INQ-', $saved->reference_code);
        $this->assertNotNull($saved->consent_recorded_at);
        $this->assertNotNull($saved->retention_until);
        $this->assertNotNull($saved->ip_hash);

        $connector = app(CommunicationChannelConnector::class);
        $this->assertFalse($connector->isAuthorizedAndEnabled());
        $this->expectException(RuntimeException::class);
        $connector->sendMessage('12345', 'Test');
    }
}
