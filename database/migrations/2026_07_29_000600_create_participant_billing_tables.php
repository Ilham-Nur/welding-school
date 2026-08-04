<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('training_application_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->char('currency', 3)->default('IDR');
            $table->string('status', 20)->default('unpaid')->index();
            $table->timestamp('issued_at');
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('payment_reference', 50)->unique();
            $table->string('gateway', 30);
            $table->string('gateway_transaction_id')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('IDR');
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->unique(
                ['gateway', 'gateway_transaction_id'],
                'payments_gateway_transaction_unique'
            );
            $table->index(['invoice_id', 'status']);
        });

        Schema::create('payment_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway', 30);
            $table->string('event_id')->nullable();
            $table->string('event_type', 100)->nullable();
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->boolean('signature_valid')->default(false);
            $table->string('processing_status', 20)->default('received')->index();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
    }
};
