<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_countries', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('iso_code', 8)->unique();
            $table->string('slug', 120)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('geo_regions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained('geo_countries')->cascadeOnDelete();
            $table->string('name', 140);
            $table->string('region_type', 48)->default('province');
            $table->string('slug', 160)->unique();
            $table->string('tagline', 220)->nullable();
            $table->text('summary')->nullable();
            $table->longText('description_html')->nullable();
            $table->string('hero_image_url', 255)->nullable();
            $table->json('gallery')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedTinyInteger('default_zoom')->default(7);
            $table->json('geojson')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->string('og_image_url', 255)->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });

        Schema::create('geo_districts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('region_id')->constrained('geo_regions')->cascadeOnDelete();
            $table->string('name', 140);
            $table->string('slug', 160)->unique();
            $table->text('summary')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_sample_subset')->default(true);
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();

            $table->index(['region_id', 'is_active']);
        });

        Schema::create('geo_destinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('region_id')->constrained('geo_regions')->cascadeOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('geo_districts')->nullOnDelete();
            $table->string('name', 160);
            $table->string('slug', 191)->unique();
            $table->string('tagline', 220)->nullable();
            $table->text('summary')->nullable();
            $table->longText('description_html')->nullable();
            $table->unsignedInteger('elevation_meters')->nullable();
            $table->string('best_season', 120)->nullable();
            $table->string('hero_image_url', 255)->nullable();
            $table->json('gallery')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('geojson')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_sample_content')->default(true);
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->string('og_image_url', 255)->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();

            $table->index(['region_id', 'is_active']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('geo_attractions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('destination_id')->constrained('geo_destinations')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('slug', 191)->unique();
            $table->string('category', 64)->default('landmark')->index();
            $table->text('summary')->nullable();
            $table->longText('description_html')->nullable();
            $table->json('gallery')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_sample_content')->default(true);
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();

            $table->index(['destination_id', 'is_active']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_attractions');
        Schema::dropIfExists('geo_destinations');
        Schema::dropIfExists('geo_districts');
        Schema::dropIfExists('geo_regions');
        Schema::dropIfExists('geo_countries');
    }
};
