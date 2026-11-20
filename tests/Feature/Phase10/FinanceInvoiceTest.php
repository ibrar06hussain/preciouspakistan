<?php

namespace Tests\Feature\Phase10;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingWorkflowService;
use App\Modules\Finance\Contracts\PaymentGatewayAdapter;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Services\InvoicePdfService;
use App\Modules\Finance\Services\InvoiceService;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Modules\Tours\Models\Tour;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleFleetSeeder;
use Database\Seeders\SampleToursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class FinanceInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleToursSeeder::class);
        $this->seed(SampleFleetSeeder::class);
    }

    public function test_issued_invoice_is_immutable_supports_fx_conversion_credit_notes_payments_pdf_and_non_pii_qr_verify(): void
    {
        $finance = User::factory()->create(['account_type' => AccountType::Staff, 'is_active' => true]);
        $finance->assignRole(RbacCatalog::ROLE_FINANCE_OFFICER);

        $tour = Tour::query()->where('code', 'PP-GB-HUNZA-05')->firstOrFail();
        $vehicle = Vehicle::query()->where('code', 'FLT-PRADO-01')->firstOrFail();

        $booking = app(BookingWorkflowService::class)->submitBookingRequest($tour, [
            'start_date' => now()->year.'-06-10',
            'guests_count' => 2,
            'currency' => 'PKR',
            'vehicle' => ['vehicle_id' => $vehicle->id, 'fuel_mode' => 'with_fuel'],
        ], [
            'customer_name' => 'Secret Customer Name',
            'customer_email' => 'secret-customer@example.com',
            'customer_phone' => '+923009998877',
        ]);

        $service = app(InvoiceService::class);
        $fx = $service->recordFxRate($finance, 'USD', 'PKR', '278.500000', 'State Bank of Pakistan');

        $draftUsd = $service->createFromBooking($finance, $booking, Invoice::TYPE_FINAL, 'USD', $fx);
        $this->assertSame('USD', $draftUsd->currency);
        $this->assertSame('278.500000', (string) $draftUsd->fx_rate_used);

        $issued = $service->issueInvoice($finance, $draftUsd);
        $this->assertNotNull($issued->invoice_number);
        $this->assertStringStartsWith('PP-INV-', $issued->invoice_number);
        $this->assertNotEmpty($issued->immutable_snapshot);

        // Attempt to silently tamper with an issued invoice MUST throw LogicException.
        try {
            $issued->forceFill(['total_amount' => '1.00'])->save();
            $this->fail('Expected LogicException when mutating issued invoice total.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        // Issue a Credit Note cleanly without mutating original total_amount.
        $issued->refresh();
        $originalTotal = (string) $issued->total_amount;
        $cn = $service->issueCreditNote($finance, $issued, '50.00', 'Early bird partner courtesy');
        $this->assertStringStartsWith('PP-CN-', $cn->credit_note_number);
        $issued->refresh();
        $this->assertSame($originalTotal, (string) $issued->total_amount);
        $this->assertSame('50.00', (string) $issued->credit_note_total);

        // Record partial & final bank transfer payment; booking payment_status syncs while booking_status remains independent!
        $receipt1 = $service->recordManualPayment($finance, $issued, '100.00', 'bank_transfer', 'IBFT-001');
        $this->assertStringStartsWith('PP-RCT-', $receipt1->receipt_number);
        $this->assertSame(Invoice::STATUS_PARTIAL, $issued->fresh()->status);
        $this->assertSame(Booking::PAYMENT_PARTIAL, $booking->fresh()->payment_status);
        $this->assertSame(Booking::STATUS_REQUESTED, $booking->fresh()->booking_status);

        $remaining = (string) $issued->fresh()->balance_due;
        $service->recordManualPayment($finance, $issued, $remaining, 'bank_transfer', 'IBFT-002');
        $this->assertSame(Invoice::STATUS_PAID, $issued->fresh()->status);
        $this->assertSame('0.00', (string) $issued->fresh()->balance_due);
        $this->assertSame(Booking::PAYMENT_PAID, $booking->fresh()->payment_status);

        // PDF renders real PDF bytes with QR code.
        $pdfBytes = app(InvoicePdfService::class)->renderPdfBytes($issued->fresh());
        $this->assertStringStartsWith('%PDF', $pdfBytes);

        // Public QR verification displays non-sensitive document status ONLY (zero customer PII!).
        $this->get(route('invoices.verify', ['token' => $issued->verification_token]))
            ->assertOk()
            ->assertSee($issued->invoice_number)
            ->assertDontSee('Secret Customer Name')
            ->assertDontSee('secret-customer@example.com')
            ->assertDontSee('+923009998877');
    }

    public function test_unconfigured_payment_gateway_adapter_is_explicitly_disabled_and_never_fakes_success(): void
    {
        $adapter = app(PaymentGatewayAdapter::class);
        $this->assertFalse($adapter->isConfigured());

        $this->expectException(RuntimeException::class);
        $adapter->createCheckoutSession(new Invoice);
    }
}
