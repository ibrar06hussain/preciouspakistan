<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_rate_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('base_currency', 3)->default('USD');
            $table->string('quote_currency', 3)->default('PKR');
            $table->decimal('rate', 16, 6);
            $table->string('source', 120);
            $table->boolean('is_manual_override')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['base_currency', 'quote_currency', 'recorded_at'], 'fx_pair_recorded_idx');
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number', 40)->nullable()->unique();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invoice_type', 24)->default('proforma')->index(); // proforma|final
            $table->string('status', 24)->default('draft')->index(); // draft|issued|partial|paid|overdue|void
            $table->string('currency', 3)->default('PKR');
            $table->foreignId('fx_rate_snapshot_id')->nullable()->constrained('fx_rate_snapshots')->nullOnDelete();
            $table->decimal('fx_rate_used', 16, 6)->nullable();
            $table->string('fx_source', 120)->nullable();
            $table->timestamp('fx_recorded_at')->nullable();
            $table->string('customer_name', 160);
            $table->string('customer_email', 160)->index();
            $table->string('customer_phone', 40)->nullable();
            $table->string('customer_address', 255)->nullable();
            $table->decimal('subtotal', 14, 2)->default('0.00');
            $table->decimal('discount_total', 14, 2)->default('0.00');
            $table->decimal('tax_rate_percent', 6, 3)->default('0.000');
            $table->decimal('tax_total', 14, 2)->default('0.00');
            $table->decimal('total_amount', 14, 2)->default('0.00');
            $table->decimal('credit_note_total', 14, 2)->default('0.00');
            $table->decimal('amount_paid', 14, 2)->default('0.00');
            $table->decimal('balance_due', 14, 2)->default('0.00');
            $table->date('due_date')->nullable()->index();
            $table->timestamp('issued_at')->nullable()->index();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 255)->nullable();
            $table->string('verification_token', 64)->unique();
            $table->json('immutable_snapshot')->nullable();
            $table->foreignId('revision_of_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->text('payment_instructions')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_sample_content')->default(false);
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('item_type', 40);
            $table->string('label', 220);
            $table->string('charge_unit', 24)->default('fixed');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 14, 2)->default('0.00');
            $table->decimal('line_total', 14, 2)->default('0.00');
            $table->boolean('is_included')->default(false);
            $table->timestamps();
        });

        Schema::create('invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('receipt_number', 40)->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->string('payment_method', 40)->default('bank_transfer'); // bank_transfer|cash|cheque|gateway_disabled
            $table->string('currency', 3);
            $table->decimal('amount', 14, 2);
            $table->string('external_reference', 120)->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->string('credit_note_number', 40)->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->string('currency', 3);
            $table->decimal('amount', 14, 2);
            $table->string('reason', 255);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->timestamps();
        });

        Schema::create('invoice_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 60)->index();
            $table->string('summary', 255);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_events');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('fx_rate_snapshots');
    }
};
