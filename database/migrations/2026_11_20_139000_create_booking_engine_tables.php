<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_quotes', function (Blueprint $table): void {
            $table->id();
            $table->string('quote_token', 64)->unique();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('currency', 3)->default('PKR');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('guests_count');
            $table->unsignedSmallInteger('vehicle_days')->default(0);
            $table->unsignedSmallInteger('hotel_nights')->default(0);
            $table->decimal('subtotal', 14, 2)->default('0.00');
            $table->decimal('discount_amount', 14, 2)->default('0.00');
            $table->decimal('tax_rate_percent', 6, 3)->default('0.000');
            $table->decimal('tax_amount', 14, 2)->default('0.00');
            $table->decimal('total_amount', 14, 2)->default('0.00');
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('expires_at')->index();
            $table->json('snapshot');
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('booking_reference', 40)->unique();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->foreignId('quote_id')->nullable()->constrained('booking_quotes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tour_id')->constrained('tours')->restrictOnDelete();
            $table->foreignId('departure_id')->nullable()->constrained('tour_departures')->nullOnDelete();
            $table->string('customer_name', 160);
            $table->string('customer_email', 160)->index();
            $table->string('customer_phone', 40);
            $table->date('start_date')->index();
            $table->date('end_date');
            $table->unsignedSmallInteger('guests_count');
            $table->string('currency', 3)->default('PKR');
            // Booking status and Payment status are strictly separated.
            $table->string('booking_status', 40)->default('requested')->index();
            $table->string('payment_status', 32)->default('unpaid')->index();
            $table->decimal('subtotal', 14, 2)->default('0.00');
            $table->decimal('discount_amount', 14, 2)->default('0.00');
            $table->decimal('tax_rate_percent', 6, 3)->default('0.000');
            $table->decimal('tax_amount', 14, 2)->default('0.00');
            $table->decimal('total_amount', 14, 2)->default('0.00');
            $table->json('pricing_snapshot');
            $table->foreignId('vehicle_reservation_id')->nullable()->constrained('vehicle_reservations')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('officer_notes')->nullable();
            $table->boolean('is_sample_content')->default(false);
            $table->timestamps();
        });

        Schema::create('booking_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('item_type', 40)->index(); // tour_base|vehicle|driver_included|route_adjustment|hotel_stay|nomad_workspace|extra|discount|tax
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('label', 220);
            $table->string('charge_unit', 24)->default('fixed'); // day|night|hour|person|fixed|included
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 14, 2)->default('0.00');
            $table->decimal('line_total', 14, 2)->default('0.00');
            $table->boolean('is_included')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 60)->index();
            $table->string('summary', 255);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_events');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('booking_quotes');
    }
};
