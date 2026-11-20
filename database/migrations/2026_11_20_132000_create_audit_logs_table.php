<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80)->index();
            $table->string('auditable_type', 150)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('summary', 255);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
