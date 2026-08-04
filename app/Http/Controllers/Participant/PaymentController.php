<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payments\MidtransPaymentProcessor;
use App\Services\Payments\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly MidtransPaymentProcessor $midtransPayments,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => ['nullable', 'integer'],
            'payment_method' => [
                'required',
                Rule::in(['va-bca', 'va-mandiri', 'qris', 'other-va']),
            ],
        ]);

        if (! $this->midtrans->isConfigured()) {
            throw ValidationException::withMessages([
                'payment' => 'Midtrans Sandbox belum dikonfigurasi.',
            ]);
        }

        $invoiceQuery = Invoice::query()
            ->where('user_id', $request->user()->id)
            ->with(['payments', 'trainingApplication']);

        if (filled($validated['invoice_id'] ?? null)) {
            $invoiceQuery->whereKey((int) $validated['invoice_id']);
        }

        $invoice = $invoiceQuery
            ->latest('issued_at')
            ->first();

        if (! $invoice || $invoice->status !== 'unpaid') {
            throw ValidationException::withMessages([
                'invoice' => 'Tidak ada invoice aktif yang dapat dibayar.',
            ]);
        }

        if (! $invoice->due_at || $invoice->due_at->lte(now()->addMinutes(5))) {
            $invoice->update(['status' => 'expired']);

            throw ValidationException::withMessages([
                'invoice' => 'Batas pembayaran invoice telah berakhir.',
            ]);
        }

        $existingPayment = $invoice->payments
            ->where('gateway', 'midtrans')
            ->where('status', 'pending')
            ->sortByDesc('created_at')
            ->first(
                fn (Payment $payment): bool => filled(
                    data_get($payment->gateway_response, 'redirect_url'),
                ),
            );

        if ($existingPayment) {
            return response()->json([
                'message' => 'Halaman pembayaran Midtrans berhasil dimuat.',
                'redirect_url' => data_get($existingPayment->gateway_response, 'redirect_url'),
                'payment' => $this->paymentPayload($existingPayment),
            ]);
        }

        $payment = DB::transaction(fn (): Payment => Payment::query()->create([
            'invoice_id' => $invoice->id,
            'payment_reference' => $this->paymentReference(),
            'gateway' => 'midtrans',
            'payment_method' => $validated['payment_method'],
            'amount' => $invoice->total_amount,
            'currency' => $invoice->currency,
            'status' => 'pending',
            'expires_at' => $invoice->due_at,
        ]));

        try {
            $result = $this->midtrans->createSnapTransaction($payment);

            $payment->update([
                'gateway_response' => $result,
            ]);
        } catch (Throwable $exception) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'gateway_response' => [
                    'error' => $exception->getMessage(),
                ],
            ]);
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'message' => 'Halaman pembayaran Midtrans berhasil dibuat.',
            'redirect_url' => $result['redirect_url'],
            'payment' => $this->paymentPayload($payment->fresh()),
        ], 201);
    }

    public function finish(Request $request): RedirectResponse
    {
        $payment = $this->paymentFromFinishRequest($request);
        $message = 'Status pembayaran sedang diperbarui dari Midtrans.';

        if ($payment && $payment->status === 'pending') {
            try {
                $payment = $this->midtransPayments->synchronize($payment);
                $message = $payment->status === 'paid'
                    ? 'Pembayaran berhasil dikonfirmasi. Selamat, pendaftaran Anda sudah aktif.'
                    : 'Pembayaran belum selesai dikonfirmasi oleh Midtrans.';
            } catch (Throwable $exception) {
                report($exception);
            }
        } elseif ($payment?->status === 'paid') {
            $message = 'Pembayaran Anda sudah berhasil dikonfirmasi.';
        }

        return redirect(route('home').'#member-programs')
            ->with(
                'auth_status',
                $message,
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(Payment $payment): array
    {
        return [
            'reference' => $payment->payment_reference,
            'gateway' => $payment->gateway,
            'payment_method' => $payment->payment_method,
            'status' => $payment->status,
            'expires_at' => $payment->expires_at?->toIso8601String(),
        ];
    }

    private function paymentReference(): string
    {
        do {
            $reference = 'WS-PAY-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
        } while (Payment::query()->where('payment_reference', $reference)->exists());

        return $reference;
    }

    private function paymentFromFinishRequest(Request $request): ?Payment
    {
        $reference = trim((string) $request->query('order_id'));
        $query = Payment::query()->where('gateway', 'midtrans');

        if ($reference !== '') {
            return $query
                ->where('payment_reference', $reference)
                ->latest('created_at')
                ->first();
        }

        if (! $request->user()) {
            return null;
        }

        return $query
            ->whereHas(
                'invoice',
                fn ($invoice) => $invoice->where('user_id', $request->user()->id),
            )
            ->latest('created_at')
            ->first();
    }
}
