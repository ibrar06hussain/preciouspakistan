<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('sha256_hash', 64)->unique();
            $table->string('original_filename', 255);
            $table->string('disk', 40)->default('uploads');
            $table->string('original_path', 255)->nullable();
            $table->string('webp_path', 255)->nullable();
            $table->string('avif_path', 255)->nullable();
            $table->json('variants')->nullable(); // responsive thumbnails (400, 800, 1440)
            $table->string('mime_type', 80);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->decimal('focal_x', 5, 2)->default('50.00');
            $table->decimal('focal_y', 5, 2)->default('50.00');
            $table->string('alt_text', 255)->nullable();
            $table->string('caption', 500)->nullable();
            $table->string('license_attribution', 255)->nullable();
            $table->boolean('preserve_original')->default(true);
            $table->boolean('is_sample_asset')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('media_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_asset_id')->constrained('media_assets')->restrictOnDelete();
            $table->string('usable_type', 120);
            $table->unsignedBigInteger('usable_id');
            $table->string('context', 60)->default('general');
            $table->timestamps();

            $table->unique(['media_asset_id', 'usable_type', 'usable_id', 'context'], 'media_usages_unique_ref');
            $table->index(['usable_type', 'usable_id'], 'media_usages_usable_idx');
        });

        Schema::create('seo_metadata', function (Blueprint $table): void {
            $table->id();
            $table->string('seoable_type', 120);
            $table->unsignedBigInteger('seoable_id');
            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->string('og_title', 160)->nullable();
            $table->string('og_description', 320)->nullable();
            $table->string('og_image_url', 255)->nullable();
            $table->string('twitter_card', 40)->default('summary_large_image');
            $table->string('robots', 60)->default('index,follow');
            $table->boolean('include_in_sitemap')->default(true)->index();
            $table->string('schema_type', 60)->nullable();
            $table->longText('editorial_html')->nullable();
            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id'], 'seo_metadata_morph_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metadata');
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media_assets');
    }
};
