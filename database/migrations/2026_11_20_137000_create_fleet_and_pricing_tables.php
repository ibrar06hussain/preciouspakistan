<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 160);
            $table->string('slug', 191)->unique();
            $table->string('vehicle_type', 48)->index();
            $table->string('make', 80);
            $table->string('model', 80);
            $table->unsignedSmallInteger('model_year')->nullable();
            // Private fleet identity (never exposed on public pages).
            $table->string('registration_number', 64)->nullable()->unique();
            $table->string('internal_fleet_tag', 64)->nullable();
            $table->text('private_notes')->nullable();
            $table->unsignedSmallInteger('passenger_capacity')->default(4);
            $table->unsignedSmallInteger('luggage_capacity')->default(4);
            $table->string('transmission', 32)->default('automatic');
            $table->boolean('has_ac')->default(true);
            $table->json('amenities')->nullable();
            $table->json('routing_suitability')->nullable();
            $table->boolean('driver_always_included')->default(true);
            $table->string('status', 32)->default('active')->index();
            $table->text('summary')->nullable();
            $table->longText('description_html')->nullable();
            $table->string('hero_image_url', 255)->nullable();
            $table->json('gallery')->nullable();
            $table->boolean('is_sample_content')->default(true);
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->boolean('noindex')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vehicle_driver_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('driver_name', 140);
            $table->string('driver_phone', 40)->nullable();
            $table->string('license_number', 64)->nullable();
            $table->date('assigned_from');
            $table->date('assigned_to')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'is_primary']);
        });

        Schema::create('vehicle_seasonal_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('rate_name', 120);
            $table->string('rate_tier', 32)->default('standard_yearly')->index();
            $table->date('valid_from');
            $table->date('valid_to');
            $table->unsignedSmallInteger('priority')->default(10);
            $table->decimal('with_fuel_rate_pkr', 14, 2)->nullable();
            $table->decimal('without_fuel_rate_pkr', 14, 2)->nullable();
            $table->decimal('with_fuel_rate_usd', 14, 2)->nullable();
            $table->decimal('without_fuel_rate_usd', 14, 2)->nullable();
            $table->unsignedSmallInteger('daily_mileage_allowance_km')->nullable();
            $table->decimal('excess_km_rate_pkr', 10, 2)->nullable();
            $table->decimal('excess_km_rate_usd', 10, 2)->nullable();
            $table->string('excess_km_terms', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['vehicle_id', 'is_active', 'valid_from', 'valid_to'], 'vsr_lookup_idx');
        });

        Schema::create('vehicle_route_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('geo_regions')->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained('geo_destinations')->nullOnDelete();
            $table->string('route_label', 140);
            $table->string('adjustment_mode', 32)->default('fixed_per_trip');
            $table->decimal('amount_pkr', 14, 2)->nullable();
            $table->decimal('amount_usd', 14, 2)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_blackouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('reason', 64)->default('maintenance');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'start_date', 'end_date']);
        });

        Schema::create('vehicle_reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_code', 64)->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('fuel_mode', 32)->default('with_fuel');
            $table->string('status', 32)->default('hold')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('quoted_currency', 3)->default('PKR');
            $table->decimal('quoted_total', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'status', 'start_date', 'end_date'], 'vres_conflict_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_reservations');
        Schema::dropIfExists('vehicle_blackouts');
        Schema::dropIfExists('vehicle_route_adjustments');
        Schema::dropIfExists('vehicle_seasonal_rates');
        Schema::dropIfExists('vehicle_driver_assignments');
        Schema::dropIfExists('vehicles');
    }
};
