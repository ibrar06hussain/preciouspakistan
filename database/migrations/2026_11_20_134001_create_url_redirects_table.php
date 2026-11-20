<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('url_redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('source_path', 191)->unique();
            $table->string('target_path', 255);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->string('reason', 120)->nullable();
            $table->unsignedBigInteger('hits')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('url_redirects');
    }
};
