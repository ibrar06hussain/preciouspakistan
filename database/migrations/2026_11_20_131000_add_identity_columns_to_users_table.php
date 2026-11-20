<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('account_type', 32)->default('customer')->index()->after('phone');
            $table->boolean('is_active')->default(true)->index()->after('account_type');
            $table->boolean('mfa_enabled')->default(false)->after('is_active');
            $table->text('mfa_secret')->nullable()->after('mfa_enabled');
            $table->text('mfa_recovery_codes')->nullable()->after('mfa_secret');
            $table->timestamp('mfa_confirmed_at')->nullable()->after('mfa_recovery_codes');
            $table->timestamp('last_login_at')->nullable()->after('mfa_confirmed_at');
            $table->foreignId('invited_by')->nullable()->after('last_login_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('invited_by');
            $table->dropColumn([
                'phone',
                'account_type',
                'is_active',
                'mfa_enabled',
                'mfa_secret',
                'mfa_recovery_codes',
                'mfa_confirmed_at',
                'last_login_at',
            ]);
        });
    }
};
