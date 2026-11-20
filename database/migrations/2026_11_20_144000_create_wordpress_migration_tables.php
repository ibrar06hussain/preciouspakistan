<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wordpress_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('batch_code', 48)->unique();
            $table->string('source_path', 255);
            $table->string('source_format', 16); // wxr_xml|json
            $table->boolean('is_dry_run')->default(false);
            $table->string('status', 32)->default('completed')->index(); // completed|rolled_back|failed
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('review_flagged_count')->default(0);
            $table->unsignedInteger('redirects_created_count')->default(0);
            $table->json('report_summary')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wordpress_import_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('wordpress_import_batches')->cascadeOnDelete();
            $table->string('source_wp_id', 80)->index();
            $table->string('source_post_type', 40)->default('page');
            $table->string('source_url', 255)->nullable()->index();
            $table->string('source_slug', 191)->nullable();
            $table->string('target_type', 120)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->foreignId('redirect_id')->nullable()->constrained('url_redirects')->nullOnDelete();
            $table->string('status', 32)->default('imported'); // imported|skipped_duplicate|needs_review|rolled_back
            $table->json('warnings')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id'], 'wp_items_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wordpress_import_items');
        Schema::dropIfExists('wordpress_import_batches');
    }
};
