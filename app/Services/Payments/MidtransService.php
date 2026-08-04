<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MidtransService
{
    /**
     * @return array<string, mixed>
     */
    public function createSnapTransaction(Payment $payment): array
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            throw new RuntimeException('Server Key Midtrans belum dikonfigurasi.');
        }

        $payment->loadMissing([
            'invoice.user.participantProfile',
            'invoice.trainingApplication.trainingProgram',
        ]);

        $invoice = $payment->invoice;
        $application = $invoice->trainingApplication;
        $program = $application->trainingProgram;
        $user = $invoice->user;
        $administrationFee = max(
            0,
            $invoice->total_amount - $invoice->subtotal + $invoice->discount_amount,
        );
        $remainingMinutes = max(
            5,
            min(10080, (int) ceil(now()->diffInSeconds($invoice->due_at, false) / 60)),
        );

        $items = [[
            'id' => $program->code,
            'price' => $invoice->subtotal,
            'quantity' => 1,
            'name' => Str::limit($program->title, 50, ''),
        ]];

        if ($administrationFee > 0) {
            $items[] = [
                'id' => 'ADMIN-FEE',
                'price' => $administrationFee,
                'quantity' => 1,
                'name' => 'Biaya administrasi',
            ];
        }

        if ($invoice->discount_amount > 0) {
            $items[] = [
                'id' => 'DISCOUNT',
                'price' => -$invoice->discount_amount,
                'quantity' => 1,
                'name' => 'Diskon',
            ];
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->payment_reference,
                'gross_amount' => $payment->amount,
            ],
            'item_details' => $items,
            'customer_details' => [
                'first_name' => Str::limit($user->name, 50, ''),
                'email' => $user->email,
                'phone' => $user->participantProfile?->phone,
            ],
            'enabled_payments' => $this->enabledPayments($payment->payment_method),
            'callbacks' => [
                'finish' => route('payments.midtrans.finish'),
                'error' => route('payments.midtrans.finish'),
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'minutes',
                'duration' => $remainingMinutes,
            ],
            'custom_field1' => $invoice->invoice_number,
        ];

        $headers = [
            'Idempotency-Key' => $payment->payment_reference,
        ];
        $notificationUrl = trim((string) config('services.midtrans.notification_url'));

        if ($notificationUrl !== '') {
            $headers['X-Override-Notification'] = $notificationUrl;
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->withHeaders($headers)
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($this->snapUrl().'/snap/v1/transactions', $payload);

        return $this->validatedResponse($response);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.midtrans.server_key'))
            && filled(config('services.midtrans.client_key'));
    }

    /**
     * @return array<string, mixed>
     */
    public function transactionStatus(Payment $payment): array
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            throw new RuntimeException('Server Key Midtrans belum dikonfigurasi.');
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->timeout(20)
            ->get(
                $this->apiUrl()
                    .'/v2/'
                    .rawurlencode($payment->payment_reference)
                    .'/status',
            );
        $result = $response->json();

        if (! $response->successful()) {
            $message = is_array($result)
                ? data_get($result, 'status_message', data_get($result, 'message'))
                : null;

            throw new RuntimeException(
                filled($message)
                    ? 'Status Midtrans tidak dapat diperiksa: '.$message
                    : 'Status transaksi Midtrans tidak dapat diperiksa.',
            );
        }

        if (
            ! is_array($result)
            || ($result['order_id'] ?? null) !== $payment->payment_reference
        ) {
            throw new RuntimeException('Midtrans mengembalikan status transaksi yang tidak sesuai.');
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function enabledPayments(?string $method): array
    {
        return match ($method) {
            'va-bca' => ['bca_va'],
            'va-mandiri' => ['echannel'],
            'qris' => ['gopay', 'other_qris'],
            'other-va' => ['permata_va', 'bni_va', 'bri_va'],
            default => [
                'bca_va',
                'echannel',
                'permata_va',
                'bni_va',
                'bri_va',
                'gopay',
                'other_qris',
            ],
        };
    }

    private function snapUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }

    private function apiUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedResponse(Response $response): array
    {
        $result = $response->json();

        if (! $response->successful()) {
            $message = is_array($result)
                ? data_get($result, 'error_messages.0', data_get($result, 'message'))
                : null;

            throw new RuntimeException(
                filled($message)
                    ? 'Midtrans menolak transaksi: '.$message
                    : 'Midtrans tidak dapat membuat transaksi pembayaran.',
            );
        }

        if (! is_array($result) || blank($result['redirect_url'] ?? null)) {
            throw new RuntimeException('Midtrans tidak mengembalikan halaman pembayaran.');
        }

        return $result;
    }
}
