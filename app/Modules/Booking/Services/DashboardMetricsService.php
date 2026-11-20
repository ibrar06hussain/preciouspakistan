<?php

namespace App\Modules\Booking\Services;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Communications\Models\Inquiry;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleReservation;
use App\Modules\Hotels\Models\HotelAvailabilityVerification;
use App\Modules\Tours\Models\Tour;

final class DashboardMetricsService
{
    /**
     * Compute role-scoped KPI metrics for the Admin Dashboard.
     * Strictly hides modules the staff user lacks permission to view.
     *
     * @return array<string, mixed>
     */
    public function forStaff(User $user): array
    {
        $metrics = [
            'includes_sample_data' => Tour::query()->where('is_sample_content', true)->exists()
                || Booking::query()->where('is_sample_content', true)->exists(),
            'sections' => [],
        ];

        if ($user->can('bookings.view')) {
            $metrics['sections']['bookings'] = [
                'total' => Booking::query()->count(),
                'pending_verification' => Booking::query()->where('booking_status', Booking::STATUS_PENDING_VERIFICATION)->count(),
                'confirmed' => Booking::query()->where('booking_status', Booking::STATUS_CONFIRMED)->count(),
                'refund_required' => Booking::query()->where('booking_status', Booking::STATUS_REFUND_REQUIRED)->count(),
            ];
        }

        if ($user->can('invoices.view')) {
            $pkrCollected = Invoice::query()
                ->where('currency', 'PKR')
                ->where('status', '!=', Invoice::STATUS_VOID)
                ->sum('amount_paid');
            $usdCollected = Invoice::query()
                ->where('currency', 'USD')
                ->where('status', '!=', Invoice::STATUS_VOID)
                ->sum('amount_paid');

            $metrics['sections']['finance'] = [
                'issued_invoices' => Invoice::query()->whereNotNull('issued_at')->where('status', '!=', Invoice::STATUS_VOID)->count(),
                'pkr_collected' => number_format((float) $pkrCollected, 2, '.', ''),
                'usd_collected' => number_format((float) $usdCollected, 2, '.', ''),
                'outstanding_count' => Invoice::query()->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE])->count(),
            ];
        }

        if ($user->can('vehicles.view')) {
            $metrics['sections']['fleet'] = [
                'active_vehicles' => Vehicle::query()->where('status', Vehicle::STATUS_ACTIVE)->count(),
                'active_reservations' => VehicleReservation::query()->whereIn('status', [VehicleReservation::STATUS_HOLD, VehicleReservation::STATUS_CONFIRMED])->count(),
            ];
        }

        if ($user->can('hotels.view')) {
            $metrics['sections']['hotels'] = [
                'pending_verifications' => HotelAvailabilityVerification::query()->where('status', HotelAvailabilityVerification::STATUS_PENDING)->count(),
            ];
        }

        if ($user->can('inquiries.view')) {
            $metrics['sections']['inquiries'] = [
                'new_inquiries' => Inquiry::query()->where('status', Inquiry::STATUS_NEW)->count(),
            ];
        }

        return $metrics;
    }
}
