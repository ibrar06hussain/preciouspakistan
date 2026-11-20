<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomad_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 191)->unique();
            $table->foreignId('region_id')->constrained('geo_regions')->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained('geo_destinations')->cascadeOnDelete();
            $table->string('address', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('power_source', 140)->nullable();
            $table->string('backup_power_details', 255)->nullable();
            $table->boolean('power_guaranteed')->default(false);
            $table->string('internet_provider', 120)->nullable();
            $table->unsignedSmallInteger('advertised_download_mbps')->nullable();
            $table->unsignedSmallInteger('advertised_upload_mbps')->nullable();
            $table->boolean('internet_guaranteed')->default(false);
            $table->string('connectivity_warning', 320)->nullable();
            $table->string('operating_hours', 160)->nullable();
            $table->string('contact_info', 160)->nullable();
            $table->longText('description_html')->nullable();
            $table->json('gallery')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->boolean('is_sample_content')->default(true);
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['destination_id', 'status']);
        });

        Schema::create('nomad_facilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nomad_location_id')->constrained('nomad_locations')->cascadeOnDelete();
            $table->string('name', 140);
            $table->string('code', 48);
            $table->string('facility_type', 32)->default('hot_desk')->index();
            $table->unsignedSmallInteger('seat_capacity')->default(10);
            $table->boolean('allows_daily')->default(true);
            $table->boolean('allows_hourly')->default(true);
            $table->boolean('requires_manual_confirmation')->default(true);
            $table->string('description', 400)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['nomad_location_id', 'code']);
        });

        Schema::create('nomad_seasonal_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nomad_facility_id')->constrained('nomad_facilities')->cascadeOnDelete();
            $table->string('rate_name', 120);
            $table->string('rate_tier', 32)->default('standard_yearly');
            $table->date('valid_from');
            $table->date('valid_to');
            $table->unsignedSmallInteger('priority')->default(10);
            $table->decimal('daily_rate_pkr', 14, 2)->nullable();
            $table->decimal('daily_rate_usd', 14, 2)->nullable();
            $table->decimal('hourly_rate_pkr', 14, 2)->nullable();
            $table->decimal('hourly_rate_usd', 14, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['nomad_facility_id', 'is_active', 'valid_from', 'valid_to'], 'nsr_lookup_idx');
        });

        Schema::create('nomad_blackouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nomad_location_id')->constrained('nomad_locations')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason', 160)->default('Seasonal Maintenance');
            $table->timestamps();

            $table->index(['nomad_location_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomad_blackouts');
        Schema::dropIfExists('nomad_seasonal_rates');
        Schema::dropIfExists('nomad_facilities');
        Schema::dropIfExists('nomad_locations');
    }
};
