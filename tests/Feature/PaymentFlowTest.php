<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TrainingApplication;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_open_midtrans_sandbox_checkout(): void
    {
        config()->set([
            'services.midtrans.server_key' => 'SB-Mid-server-test',
            'services.midtrans.client_key' => 'SB-Mid-client-test',
            'services.midtrans.is_production' => false,
            'services.midtrans.notification_url' => 'https://tunnel.example.test/payments/midtrans/webhook',
        ]);
        Http::fake([
            'https://app.sandbox.midtrans.com/*' => Http::response([
                'token' => 'sandbox-snap-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test',
            ], 201),
        ]);

        [$participant, $invoice] = $this->invoice();

        $response = $this->actingAs($participant)
            ->postJson(route('payments.store'), [
                'invoice_id' => $invoice->id,
                'payment_method' => 'qris',
            ])
            ->assertCreated()
            ->assertJsonPath(
                'redirect_url',
                'https://app.sandbox.midtrans.com/snap/v2/vtweb/test',
            )
            ->assertJsonPath('payment.gateway', 'midtrans')
            ->assertJsonPath('payment.status', 'pending');

        $reference = $response->json('payment.reference');

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'payment_reference' => $reference,
            'gateway' => 'midtrans',
            'payment_method' => 'qris',
            'amount' => 3650000,
            'status' => 'pending',
        ]);

        Http::assertSent(function (ClientRequest $request) use ($reference): bool {
            return $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
                && $request->hasHeader(
                    'Authorization',
                    'Basic '.base64_encode('SB-Mid-server-test:'),
                )
                && $request->hasHeader(
                    'X-Override-Notification',
                    'https://tunnel.example.test/payments/midtrans/webhook',
                )
                && $request['transaction_details']['order_id'] === $reference
                && $request['transaction_details']['gross_amount'] === 3650000
                && $request['enabled_payments'] === ['gopay', 'other_qris'];
        });

        $this->postJson(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'payment_method' => 'qris',
        ])
            ->assertOk()
            ->assertJsonPath('payment.reference', $reference);

        Http::assertSentCount(1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_finish_redirect_reconciles_a_settled_payment_when_webhook_was_missed(): void
    {
        config()->set('services.midtrans.server_key', 'SB-Mid-server-status');

        [$participant, $invoice, $application] = $this->invoice();
        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'payment_reference' => 'WS-PAY-STATUS-001',
            'gateway' => 'midtrans',
            'payment_method' => 'va-bca',
            'amount' => $invoice->total_amount,
            'currency' => 'IDR',
            'status' => 'pending',
            'expires_at' => $invoice->due_at,
        ]);

        Http::fake([
            'https://api.sandbox.midtrans.com/v2/*/status' => Http::response(
                $this->webhookPayload($payment, 'SB-Mid-server-status'),
            ),
        ]);

        $this->actingAs($participant)
            ->get(route('payments.midtrans.finish', [
                'order_id' => $payment->payment_reference,
            ]))
            ->assertRedirect(route('home').'#member-programs')
            ->assertSessionHas(
                'auth_status',
                'Pembayaran berhasil dikonfirmasi. Selamat, pendaftaran Anda sudah aktif.',
            );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('enrollments', [
            'training_application_id' => $application->id,
            'status' => 'active',
        ]);

        Http::assertSent(
            fn (ClientRequest $request): bool => $request->method() === 'GET'
                && $request->url()
                    === 'https://api.sandbox.midtrans.com/v2/WS-PAY-STATUS-001/status',
        );
    }

    public function test_current_application_reconciles_a_missed_midtrans_webhook(): void
    {
        config()->set('services.midtrans.server_key', 'SB-Mid-server-current');

        [$participant, $invoice] = $this->invoice();
        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'payment_reference' => 'WS-PAY-CURRENT-001',
            'gateway' => 'midtrans',
            'payment_method' => 'va-bca',
            'amount' => $invoice->total_amount,
            'currency' => 'IDR',
            'status' => 'pending',
            'expires_at' => $invoice->due_at,
        ]);

        Http::fake([
            'https://api.sandbox.midtrans.com/v2/*/status' => Http::response(
                $this->webhookPayload($payment, 'SB-Mid-server-current'),
            ),
        ]);

        $this->actingAs($participant)
            ->getJson(route('applications.current'))
            ->assertOk()
            ->assertJsonPath('application.invoice.status', 'paid')
            ->assertJsonPath('application.enrollment.status', 'active');
    }

    public function test_valid_paid_webhook_marks_invoice_paid_and_creates_enrollment(): void
    {
        config()->set('services.midtrans.server_key', 'SB-Mid-server-webhook');

        [$participant, $invoice, $application] = $this->invoice();
        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'payment_reference' => 'WS-PAY-WEBHOOK-001',
            'gateway' => 'midtrans',
            'payment_method' => 'qris',
            'amount' => $invoice->total_amount,
            'currency' => 'IDR',
            'status' => 'pending',
            'expires_at' => $invoice->due_at,
        ]);
        $payload = $this->webhookPayload(
            $payment,
            'SB-Mid-server-webhook',
        );

        $this->postJson(route('payments.midtrans.webhook'), $payload)
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'gateway_transaction_id' => 'midtrans-transaction-001',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('enrollments', [
            'training_application_id' => $application->id,
            'user_id' => $participant->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('payment_webhooks', [
            'gateway' => 'midtrans',
            'signature_valid' => true,
            'processing_status' => 'processed',
        ]);

        $this->postJson(route('payments.midtrans.webhook'), $payload)
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Notifikasi Midtrans sudah diproses sebelumnya.',
            );

        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseCount('payment_webhooks', 1);

        $this->actingAs($participant)
            ->getJson(route('applications.current'))
            ->assertOk()
            ->assertJsonPath('application.invoice.status', 'paid')
            ->assertJsonPath('application.enrollment.status', 'active');
    }

    public function test_invalid_midtrans_signature_cannot_mark_payment_as_paid(): void
    {
        config()->set('services.midtrans.server_key', 'SB-Mid-server-webhook');

        [, $invoice] = $this->invoice();
        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'payment_reference' => 'WS-PAY-WEBHOOK-INVALID',
            'gateway' => 'midtrans',
            'payment_method' => 'qris',
            'amount' => $invoice->total_amount,
            'currency' => 'IDR',
            'status' => 'pending',
            'expires_at' => $invoice->due_at,
        ]);
        $payload = $this->webhookPayload($payment, 'wrong-server-key');

        $this->postJson(route('payments.midtrans.webhook'), $payload)
            ->assertForbidden();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertDatabaseCount('enrollments', 0);
    }

    /**
     * @return array{User, Invoice, TrainingApplication}
     */
    private function invoice(): array
    {
        $participant = User::factory()->create();
        $program = TrainingProgram::query()->create([
            'code' => 'MIDTRANS-TEST',
            'title' => 'Program Midtrans Sandbox',
            'category' => 'Testing',
            'duration_hours' => 80,
            'price' => 3500000,
            'status' => 'active',
        ]);
        $application = TrainingApplication::query()->create([
            'registration_number' => 'WS-MID-'.str()->random(8),
            'user_id' => $participant->id,
            'training_program_id' => $program->id,
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
            'verified_at' => now(),
        ]);
        $invoice = Invoice::query()->create([
            'invoice_number' => 'WS-INV-'.str()->random(8),
            'training_application_id' => $application->id,
            'user_id' => $participant->id,
            'subtotal' => 3500000,
            'discount_amount' => 0,
            'total_amount' => 3650000,
            'currency' => 'IDR',
            'status' => 'unpaid',
            'issued_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        return [$participant, $invoice, $application];
    }

    /**
     * @return array<string, mixed>
     */
    private function webhookPayload(Payment $payment, string $serverKey): array
    {
        $payload = [
            'transaction_id' => 'midtrans-transaction-001',
            'order_id' => $payment->payment_reference,
            'status_code' => '200',
            'gross_amount' => number_format($payment->amount, 2, '.', ''),
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id']
                .$payload['status_code']
                .$payload['gross_amount']
                .$serverKey,
        );

        return $payload;
    }
}
