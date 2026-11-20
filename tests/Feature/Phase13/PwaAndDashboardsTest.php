<?php

namespace Tests\Feature\Phase13;

use App\Models\User;
use App\Modules\Booking\Services\BookingWorkflowService;
use App\Modules\Booking\Services\DashboardMetricsService;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Modules\Tours\Models\Tour;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleToursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaAndDashboardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleToursSeeder::class);
    }

    public function test_role_scoped_admin_kpis_hide_unauthorized_finance_metrics(): void
    {
        $support = User::factory()->create(['account_type' => AccountType::Staff, 'is_active' => true]);
        $support->assignRole(RbacCatalog::ROLE_CUSTOMER_SUPPORT);

        $finance = User::factory()->create(['account_type' => AccountType::Staff, 'is_active' => true]);
        $finance->assignRole(RbacCatalog::ROLE_FINANCE_OFFICER);

        $service = app(DashboardMetricsService::class);
        $supportKpi = $service->forStaff($support);
        $financeKpi = $service->forStaff($finance);

        $this->assertArrayNotHasKey('finance', $supportKpi['sections']);
        $this->assertArrayHasKey('inquiries', $supportKpi['sections']);
        $this->assertArrayHasKey('finance', $financeKpi['sections']);
        $this->assertTrue($supportKpi['includes_sample_data']);
    }

    public function test_customer_dashboard_and_offline_itinerary_download_and_safe_pwa_assets(): void
    {
        $customer = User::factory()->create([
            'account_type' => AccountType::Customer,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $tour = Tour::query()->where('code', 'PP-GB-HUNZA-05')->firstOrFail();
        $booking = app(BookingWorkflowService::class)->submitBookingRequest($tour, [
            'start_date' => now()->addDays(20)->toDateString(),
            'guests_count' => 2,
            'currency' => 'PKR',
        ], [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '+923001234567',
        ], $customer);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee($booking->booking_reference)
            ->assertSee('Save Offline Itinerary');

        $this->get(route('bookings.itinerary-offline', ['reference' => $booking->booking_reference]))
            ->assertOk()
            ->assertSee('Offline-Ready Itinerary Pack')
            ->assertSee('Offline mode supports reading downloaded itineraries only');

        $manifestPath = public_path('manifest.webmanifest');
        $swPath = public_path('sw.js');
        $this->assertFileExists($manifestPath);
        $this->assertFileExists($swPath);

        $swContents = (string) file_get_contents($swPath);
        $this->assertStringContainsString("'/admin'", $swContents);
        $this->assertStringContainsString("'/invoices'", $swContents);
        $this->assertStringContainsString('itinerary-offline', $swContents);
    }
}
