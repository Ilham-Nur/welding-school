<?php

namespace App\Services\Payments;

use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MidtransPaymentProcessor
{
    public function __construct(
        private readonly MidtransService $midtrans,
    ) {}

    public function synchronize(Payment $payment): Payment
    {
        $payload = $this->midtrans->transactionStatus($payment);

        if (! $this->hasValidSignature($payload)) {
            throw new RuntimeException('Signature status transaksi Midtrans tidak valid.');
        }

        return $this->apply($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hasValidSignature(array $payload): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if (
            $serverKey === ''
            || blank($payload['order_id'] ?? null)
            || blank($payload['status_code'] ?? null)
            || blank($payload['gross_amount'] ?? null)
            || blank($payload['signature_key'] ?? null)
        ) {
            return false;
        }

        $expected = hash(
            'sha512',
            (string) $payload['order_id']
                .(string) $payload['status_code']
                .(string) $payload['gross_amount']
                .$serverKey,
        );

        return hash_equals(
            $expected,
            strtolower((string) $payload['signature_key']),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function apply(array $payload): Payment
    {
        $payment = DB::transaction(function () use ($payload): Payment {
            $payment = Payment::query()
                ->where('gateway', 'midtrans')
                ->where('payment_reference', (string) $payload['order_id'])
                ->with('invoice.trainingApplication')
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw new RuntimeException('Transaksi Midtrans tidak ditemukan.');
            }

            $amount = (int) round((float) $payload['gross_amount']);

            if ($amount !== $payment->amount) {
                throw new RuntimeException('Nominal notifikasi Midtrans tidak sesuai.');
            }

            $status = $this->paymentStatus($payload);
            $gatewayResponse = array_merge(
                $payment->gateway_response ?? [],
                ['last_notification' => $payload],
            );
            $common = [
                'gateway_transaction_id' => $payload['transaction_id'] ?? null,
                'payment_method' => $payload['payment_type'] ?? $payment->payment_method,
                'gateway_response' => $gatewayResponse,
            ];

            if ($payment->status === 'paid' && $status !== 'refunded') {
                $payment->update($common);

                return $payment;
            }

            if ($status === 'paid') {
                $paidAt = now();
                $payment->update($common + [
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'failed_at' => null,
                ]);
                $payment->invoice->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                ]);
                $this->createEnrollment($payment, $paidAt);

                return $payment;
            }

            if ($status === 'refunded') {
                $payment->update($common + ['status' => 'refunded']);
                $payment->invoice->update(['status' => 'refunded']);
                $payment->invoice->trainingApplication->enrollment?->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                return $payment;
            }

            $payment->update($common + [
                'status' => $status,
                'failed_at' => in_array($status, ['failed', 'expired', 'cancelled'], true)
                    ? now()
                    : null,
            ]);

            if (
                $status === 'expired'
                && $payment->invoice->due_at?->isPast()
                && $payment->invoice->status === 'unpaid'
            ) {
                $payment->invoice->update(['status' => 'expired']);
            }

            return $payment;
        });

        return $payment->fresh(['invoice.trainingApplication.enrollment']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function paymentStatus(array $payload): string
    {
        $status = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');

        return match ($status) {
            'settlement' => 'paid',
            'capture' => $fraudStatus === 'accept' ? 'paid' : 'pending',
            'pending' => 'pending',
            'deny', 'failure' => 'failed',
            'expire' => 'expired',
            'cancel' => 'cancelled',
            'refund', 'partial_refund' => 'refunded',
            default => throw new RuntimeException(
                "Status Midtrans {$status} belum didukung.",
            ),
        };
    }

    private function createEnrollment(Payment $payment, mixed $paidAt): void
    {
        $application = $payment->invoice->trainingApplication;

        Enrollment::query()->firstOrCreate(
            ['training_application_id' => $application->id],
            [
                'enrollment_number' => $this->enrollmentNumber(),
                'user_id' => $application->user_id,
                'training_program_id' => $application->training_program_id,
                'training_batch_id' => $application->training_batch_id,
                'status' => 'active',
                'enrolled_at' => $paidAt,
            ],
        );
    }

    private function enrollmentNumber(): string
    {
        do {
            $number = 'WS-ENR-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Enrollment::query()->where('enrollment_number', $number)->exists());

        return $number;
    }
}
