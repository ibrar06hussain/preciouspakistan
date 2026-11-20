<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 191)->unique();
            $table->boolean('is_homepage')->default(false)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedBigInteger('published_version_id')->nullable()->index();
            $table->unsignedBigInteger('draft_version_id')->nullable()->index();
            $table->timestamp('scheduled_publish_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->string('og_image_url', 255)->nullable();
            $table->boolean('noindex')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cms_page_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('cms_pages')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('label', 120)->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->string('theme_preset', 64)->nullable();
            $table->json('meta_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'version_number']);
        });

        Schema::create('cms_page_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_version_id')->constrained('cms_page_versions')->cascadeOnDelete();
            $table->string('section_key', 64);
            $table->string('component_type', 64)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_visible')->default(true);
            $table->string('device_visibility', 32)->default('all');
            $table->string('style_preset', 32)->default('default');
            $table->json('config');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_page_sections');
        Schema::dropIfExists('cms_page_versions');
        Schema::dropIfExists('cms_pages');
    }
};
