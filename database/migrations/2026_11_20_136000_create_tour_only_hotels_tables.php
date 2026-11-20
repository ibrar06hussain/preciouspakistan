<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 180);
            $table->string('slug', 191)->unique();
            $table->foreignId('region_id')->constrained('geo_regions')->cascadeOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('geo_districts')->nullOnDelete();
            $table->foreignId('destination_id')->constrained('geo_destinations')->cascadeOnDelete();
            $table->string('address', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->longText('short_description_html')->nullable();
            $table->string('main_photo_url', 255)->nullable();
            $table->json('gallery')->nullable();
            $table->string('public_contact_phone', 40)->nullable();
            $table->string('public_contact_email', 191)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->boolean('is_sample_content')->default(true);
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->boolean('noindex')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['destination_id', 'status']);
        });

        Schema::create('hotel_room_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 48);
            $table->unsignedSmallInteger('max_occupancy')->default(2);
            $table->string('bed_configuration', 120)->nullable();
            $table->string('short_description', 400)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['hotel_id', 'code']);
        });

        Schema::create('hotel_room_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_room_type_id')->constrained('hotel_room_types')->cascadeOnDelete();
            $table->string('rate_name', 120);
            $table->string('rate_tier', 32)->default('standard_yearly')->index();
            $table->date('valid_from');
            $table->date('valid_to');
            $table->unsignedSmallInteger('priority')->default(10);
            $table->decimal('nightly_rate_pkr', 14, 2)->nullable();
            $table->decimal('nightly_rate_usd', 14, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['hotel_room_type_id', 'is_active', 'valid_from', 'valid_to'], 'hrr_lookup_idx');
        });

        Schema::create('tour_stay_hotels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_stay_id')->constrained('tour_stays')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('default_room_type_id')->nullable()->constrained('hotel_room_types')->nullOnDelete();
            $table->string('option_type', 32)->default('alternative')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['tour_stay_id', 'hotel_id']);
        });

        Schema::create('hotel_availability_verifications', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_code', 64)->unique();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('tour_stay_id')->constrained('tour_stays')->cascadeOnDelete();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->foreignId('requested_hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('requested_room_type_id')->constrained('hotel_room_types')->cascadeOnDelete();
            $table->foreignId('confirmed_hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
            $table->foreignId('confirmed_room_type_id')->nullable()->constrained('hotel_room_types')->nullOnDelete();
            $table->date('check_in_date');
            $table->unsignedSmallInteger('nights_count');
            $table->unsignedSmallInteger('rooms_count');
            $table->unsignedSmallInteger('guests_count');
            $table->string('status', 40)->default('pending_verification')->index();
            $table->string('verification_method', 32)->nullable();
            $table->string('external_reference', 120)->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('officer_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_availability_verifications');
        Schema::dropIfExists('tour_stay_hotels');
        Schema::dropIfExists('hotel_room_rates');
        Schema::dropIfExists('hotel_room_types');
        Schema::dropIfExists('hotels');
    }
};
