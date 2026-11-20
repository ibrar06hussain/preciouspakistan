<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 32)->index(); // facebook|instagram|youtube|linkedin|whatsapp
            $table->string('label', 80);
            $table->string('url', 255);
            $table->string('whatsapp_number', 32)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true)->index();
            $table->json('placements')->nullable(); // ['header', 'footer', 'mobile', 'tour_detail']
            $table->boolean('open_in_new_tab')->default(true);
            $table->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_code', 40)->unique();
            $table->string('source_page', 120)->default('contact');
            $table->string('source_channel', 32)->default('web_form'); // web_form|whatsapp_click|tour_page
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('name', 160);
            $table->string('email', 160)->index();
            $table->string('phone', 40)->nullable();
            $table->string('preferred_dates', 120)->nullable();
            $table->text('message');
            $table->boolean('consent_accepted')->default(false);
            $table->timestamp('consent_recorded_at')->nullable();
            $table->date('retention_until')->nullable()->index();
            $table->string('status', 32)->default('new')->index(); // new|in_progress|resolved|archived
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->text('staff_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('communication_channel_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 48)->unique(); // whatsapp_cloud_api|facebook_messenger|instagram_messaging
            $table->string('display_name', 100);
            $table->boolean('is_enabled')->default(false);
            $table->string('status', 32)->default('disabled'); // disabled|configured
            $table->text('encrypted_credentials')->nullable();
            $table->string('webhook_verify_token_hash', 64)->nullable();
            $table->boolean('opt_in_required')->default(true);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_channel_configs');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('social_accounts');
    }
};
