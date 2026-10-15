<?php

namespace Tests\Feature;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TrainingApplication;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\Payments\InvoiceDocumentPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParticipantBillingPdfTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(): array
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $program = TrainingProgram::create(['code' => 'PDF-TEST', 'title' => 'FCAW Welder 3G', 'category' => 'Welding', 'duration_hours' => 40, 'price' => 9999999, 'status' => 'active']);
        $batch = $program->batches()->create(['code' => 'PDF-BATCH', 'name' => 'FCAW Oktober', 'start_date' => today(), 'end_date' => today()->addWeek(), 'capacity' => 15, 'status' => 'closed', 'operational_status' => 'running']);
        $application = TrainingApplication::create(['registration_number' => 'REG-PDF-001', 'user_id' => $user->id, 'training_program_id' => $program->id, 'training_batch_id' => $batch->id, 'status' => 'approved', 'personal_data_snapshot' => ['full_name' => 'Peserta PDF', 'email' => 'peserta@example.test', 'identity_number' => 'SECRET-IDENTITY']]);
        $invoice = Invoice::create(['invoice_number' => 'WS-INV-PDF-001', 'training_application_id' => $application->id, 'user_id' => $user->id, 'subtotal' => 3500000, 'discount_amount' => 100000, 'total_amount' => 3550000, 'currency' => 'IDR', 'status' => 'unpaid', 'issued_at' => now(), 'due_at' => now()->addDay()]);

        return [$user, $invoice];
    }

    private function payment(Invoice $invoice, array $attributes = []): Payment
    {
        return $invoice->payments()->create([...[
            'payment_reference' => 'WS-PAY-PDF-001', 'gateway' => 'midtrans', 'amount' => $invoice->total_amount,
            'currency' => 'IDR', 'payment_method' => 'qris', 'status' => 'paid', 'paid_at' => now(),
            'gateway_response' => ['token' => 'SECRET-GATEWAY-TOKEN'],
        ], ...$attributes]);
    }

    private function capture(string $name, string $pdf): void
    {
        // Optional isolated fixture output for render-and-inspect QA, not real user data.
        if ($directory = getenv('BILLING_PDF_PREVIEW_DIR')) {
            File::ensureDirectoryExists($directory);
            File::put($directory.'/'.$name.'.pdf', $pdf);
        }
    }

    public function test_owner_can_download_original_invoice_then_paid_invoice_and_verified_receipt(): void
    {
        [$user, $invoice] = $this->invoice();
        $this->actingAs($user);
        $unpaid = $this->get(route('invoices.pdf', $invoice))->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="invoice-WS-INV-PDF-001.pdf"')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('%PDF-', $unpaid->getContent());
        $this->capture('invoice-unpaid', $unpaid->getContent());
        $this->get(route('invoices.receipt', $invoice))->assertStatus(409);
        $this->payment($invoice);
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        $paid = $this->get(route('invoices.pdf', $invoice))->assertOk();
        $receipt = $this->get(route('invoices.receipt', $invoice))->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="kuitansi-WS-INV-PDF-001.pdf"');
        $this->assertStringStartsWith('%PDF-', $receipt->getContent());
        $this->capture('invoice-paid', $paid->getContent());
        $this->capture('receipt-paid', $receipt->getContent());
        $resource = InvoiceResource::make($invoice->fresh())->resolve();
        $this->assertSame(route('invoices.pdf', $invoice), $resource['invoice_pdf_url']);
        $this->assertSame(route('invoices.receipt', $invoice), $resource['receipt_pdf_url']);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_pdf_uses_saved_amounts_and_registration_identity_and_escapes_text_without_gateway_secrets(): void
    {
        [$user, $invoice] = $this->invoice();
        $invoice->trainingApplication->update(['personal_data_snapshot' => ['full_name' => '<img src="http://malicious.test/x">', 'email' => 'snapshot@example.test']]);
        $user->update(['name' => 'New account name']);
        $pdf = app(InvoiceDocumentPdf::class);
        $data = $pdf->data($invoice->fresh());
        $this->assertSame(150000, $data['administrationFee']);
        $html = view('participant.billing.document', $data)->render();
        $this->assertStringContainsString('IDR 3.550.000', $html);
        $this->assertStringContainsString('IDR 3.500.000', $html);
        $this->assertStringContainsString('IDR 100.000', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString('snapshot@example.test', $html);
        $this->assertStringNotContainsString('IDR 9.999.999', $html);
        $this->assertStringNotContainsString('New account name', $html);
        $this->assertStringNotContainsString('SECRET-IDENTITY', $html);
        $this->payment($invoice);
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        $html = view('participant.billing.document', $pdf->data($invoice->fresh(), true))->render();
        $this->assertStringNotContainsString('WS-PAY-PDF-001', $html);
        $this->assertStringNotContainsString('SECRET-GATEWAY-TOKEN', $html);
    }

    public function test_document_dates_are_readable_and_internal_payment_fields_are_not_displayed(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-28 18:30:00', 'Asia/Jakarta'));
        [$user, $invoice] = $this->invoice();
        $payment = $this->payment($invoice, ['payment_method' => 'bank_transfer']);
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        foreach ([false, true] as $receipt) {
            $html = view('participant.billing.document', app(InvoiceDocumentPdf::class)->data($invoice->fresh(), $receipt))->render();
            $this->assertStringContainsString('28 September 2026 18:30', $html);
            $this->assertStringContainsString('<td>Transfer</td>', $html);
            foreach (['Asia/Jakarta', 'Jatuh tempo', 'Referensi transaksi', 'WS-PAY-PDF-001', 'bank_transfer', 'midtrans', '28/09/2026'] as $hidden) {
                $this->assertStringNotContainsString($hidden, $html);
            }
        }
        $this->assertSame('bank_transfer', $payment->fresh()->payment_method);
        $this->assertSame('midtrans', $payment->fresh()->gateway);
        $this->assertSame('WS-PAY-PDF-001', $payment->fresh()->payment_reference);
        $this->assertTrue($invoice->fresh()->due_at->equalTo(now()->addDay()));
    }

    public function test_payment_method_labels_do_not_expose_technical_identifiers(): void
    {
        [$user, $invoice] = $this->invoice();
        $payment = $this->payment($invoice);
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        foreach (['bank_transfer' => 'Transfer', 'echannel' => 'Transfer', 'va-bca' => 'Transfer',
            'qris' => 'QRIS', 'other_qris' => 'QRIS', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay',
            'credit_card' => 'Kartu Kredit', 'unrecognized_method' => 'Pembayaran lainnya', '' => 'Tidak dicantumkan',
        ] as $method => $label) {
            $payment->update(['payment_method' => $method]);
            $data = app(InvoiceDocumentPdf::class)->data($invoice->fresh(), true);
            $this->assertSame($label, $data['paymentMethod']);
        }
    }

    public function test_guests_other_participants_inactive_owners_and_mismatched_application_owners_cannot_download(): void
    {
        [$owner, $invoice] = $this->invoice();
        foreach (['invoices.pdf', 'invoices.receipt'] as $route) {
            $this->get(route($route, $invoice))->assertRedirect(route('login'));
        }
        $outsider = User::factory()->create(['status' => 'active']);
        foreach (['invoices.pdf', 'invoices.receipt'] as $route) {
            $this->actingAs($outsider)->get(route($route, $invoice))->assertNotFound();
        }
        $owner->update(['status' => 'inactive']);
        $this->actingAs($owner)->get(route('invoices.pdf', $invoice))->assertForbidden();
        $owner->update(['status' => 'active']);
        $invoice->trainingApplication->update(['user_id' => $outsider->id]);
        $this->actingAs($owner)->get(route('invoices.pdf', $invoice))->assertNotFound();
    }

    public function test_receipt_rejects_pending_missing_mismatched_and_refunded_payments(): void
    {
        [$user, $invoice] = $this->invoice();
        $this->actingAs($user);
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        $this->get(route('invoices.receipt', $invoice))->assertStatus(409);
        $payment = $this->payment($invoice, ['status' => 'pending', 'paid_at' => null]);
        foreach ([['status' => 'pending', 'paid_at' => null], ['status' => 'paid', 'paid_at' => now(), 'amount' => 1], ['amount' => $invoice->total_amount, 'currency' => 'USD'], ['currency' => 'IDR', 'status' => 'refunded']] as $attributes) {
            $payment->update($attributes);
            $this->get(route('invoices.receipt', $invoice))->assertStatus(409);
            $this->assertNull(InvoiceResource::make($invoice->fresh())->resolve()['receipt_pdf_url']);
        }
        $payment->update(['status' => 'paid']);
        $invoice->update(['status' => 'refunded']);
        $this->get(route('invoices.receipt', $invoice))->assertStatus(409);
    }

    public function test_verified_midtrans_payment_unlocks_receipt_url_and_a_refund_revokes_it(): void
    {
        config(['services.midtrans.server_key' => 'SB-Mid-server-pdf-test']);
        [$user, $invoice] = $this->invoice();
        $payment = $this->payment($invoice, ['status' => 'pending', 'paid_at' => null]);
        $payload = ['order_id' => $payment->payment_reference, 'status_code' => '200', 'gross_amount' => '3550000.00',
            'transaction_status' => 'settlement', 'transaction_id' => 'transaction-pdf-verified', 'payment_type' => 'qris'];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'SB-Mid-server-pdf-test');
        $this->postJson(route('payments.midtrans.webhook'), $payload)->assertOk();
        $this->actingAs($user)->getJson(route('applications.current'))->assertOk()
            ->assertJsonPath('application.invoice.receipt_pdf_url', route('invoices.receipt', $invoice));
        $this->get(route('invoices.receipt', $invoice))->assertOk();
        $payload['transaction_status'] = 'refund';
        $this->postJson(route('payments.midtrans.webhook'), $payload)->assertOk();
        $this->get(route('invoices.receipt', $invoice))->assertStatus(409);
        $this->getJson(route('applications.current'))->assertOk()->assertJsonPath('application.invoice.receipt_pdf_url', null);
    }

    public function test_long_document_fields_render_without_changing_amount_or_exposing_identity_documents(): void
    {
        [$user, $invoice] = $this->invoice();
        $invoice->trainingApplication->trainingProgram->update(['title' => str_repeat('Pelatihan pengelasan profesional ', 6)]);
        $invoice->trainingApplication->update(['personal_data_snapshot' => ['full_name' => str_repeat('José García ', 10), 'email' => str_repeat('peserta', 16).'@example.test', 'identity_number' => 'SECRET-IDENTITY']]);
        $pdf = app(InvoiceDocumentPdf::class)->render($invoice->fresh());
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->capture('invoice-long-fields', $pdf);
    }

    public function test_browser_payment_return_does_not_unlock_receipt(): void
    {
        Http::fake();
        [$user, $invoice] = $this->invoice();
        $this->actingAs($user)->get(route('payments.midtrans.finish', ['order_id' => 'unverified', 'transaction_status' => 'settlement']))->assertRedirect();
        $this->get(route('invoices.receipt', $invoice))->assertStatus(409);
        $this->assertSame('unpaid', $invoice->fresh()->status);
        Http::assertNothingSent();
    }
}
