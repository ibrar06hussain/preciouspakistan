<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('title', 191);
            $table->string('slug', 191)->unique();
            $table->string('category', 32)->default('sightseeing')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->unsignedSmallInteger('duration_nights')->default(0);
            $table->unsignedSmallInteger('min_guests')->default(1);
            $table->unsignedSmallInteger('max_guests')->default(12);
            $table->unsignedSmallInteger('booking_lead_days')->default(2);
            $table->unsignedSmallInteger('booking_window_days')->default(365);
            $table->string('difficulty', 32)->default('moderate')->index();
            $table->json('languages')->nullable();
            $table->string('pickup_location', 180)->nullable();
            $table->string('dropoff_location', 180)->nullable();
            $table->text('summary')->nullable();
            $table->longText('description_html')->nullable();
            $table->json('highlights')->nullable();
            $table->json('inclusions')->nullable();
            $table->json('exclusions')->nullable();
            $table->longText('cancellation_policy_html')->nullable();
            $table->json('faqs')->nullable();
            $table->string('hero_image_url', 255)->nullable();
            $table->json('gallery')->nullable();
            $table->string('pricing_model', 32)->default('custom_quote')->index();
            $table->decimal('base_price_pkr', 14, 2)->nullable();
            $table->decimal('base_price_usd', 14, 2)->nullable();
            $table->boolean('includes_base_accommodation')->default(false);
            $table->boolean('requires_company_vehicle')->default(true);
            $table->boolean('supports_hotel_selection')->default(true);
            $table->boolean('supports_nomad_addon')->default(false)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_sample_content')->default(true);
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->string('og_image_url', 255)->nullable();
            $table->boolean('noindex')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tour_destinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained('geo_destinations')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['tour_id', 'destination_id']);
        });

        Schema::create('tour_itinerary_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number');
            $table->string('title', 191);
            $table->foreignId('destination_id')->nullable()->constrained('geo_destinations')->nullOnDelete();
            $table->longText('description_html')->nullable();
            $table->string('meals_included', 120)->nullable();
            $table->unsignedInteger('altitude_meters')->nullable();
            $table->unsignedSmallInteger('travel_distance_km')->nullable();
            $table->timestamps();

            $table->unique(['tour_id', 'day_number']);
        });

        Schema::create('tour_stays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained('geo_destinations')->cascadeOnDelete();
            $table->unsignedSmallInteger('stay_order')->default(1);
            $table->unsignedSmallInteger('check_in_day');
            $table->unsignedSmallInteger('nights_count')->default(1);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['tour_id', 'stay_order']);
        });

        Schema::create('tour_departures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->string('departure_code', 64)->unique();
            $table->date('start_date')->index();
            $table->date('end_date');
            $table->unsignedSmallInteger('capacity')->default(12);
            $table->unsignedSmallInteger('reserved_seats')->default(0);
            $table->decimal('price_override_pkr', 14, 2)->nullable();
            $table->decimal('price_override_usd', 14, 2)->nullable();
            $table->string('status', 32)->default('open')->index();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['tour_id', 'status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_departures');
        Schema::dropIfExists('tour_stays');
        Schema::dropIfExists('tour_itinerary_days');
        Schema::dropIfExists('tour_destinations');
        Schema::dropIfExists('tours');
    }
};
