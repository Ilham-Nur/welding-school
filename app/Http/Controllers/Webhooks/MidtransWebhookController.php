<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\PaymentWebhook;
use App\Services\Payments\MidtransPaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class MidtransWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MidtransPaymentProcessor $processor,
    ): JsonResponse {
        $payload = $request->all();
        $required = [
            'order_id',
            'status_code',
            'gross_amount',
            'signature_key',
            'transaction_status',
        ];

        foreach ($required as $field) {
            if (blank($payload[$field] ?? null)) {
                return response()->json([
                    'message' => "Payload Midtrans tidak memiliki {$field}.",
                ], 422);
            }
        }

        $eventId = $this->eventId($payload);
        $webhook = PaymentWebhook::query()->firstOrCreate(
            [
                'gateway' => 'midtrans',
                'event_id' => $eventId,
            ],
            [
                'event_type' => (string) $payload['transaction_status'],
                'gateway_transaction_id' => $payload['transaction_id'] ?? null,
                'signature_valid' => false,
                'processing_status' => 'received',
                'payload' => $payload,
            ],
        );

        if (! $webhook->wasRecentlyCreated && $webhook->processing_status === 'processed') {
            return response()->json([
                'message' => 'Notifikasi Midtrans sudah diproses sebelumnya.',
            ]);
        }

        if (! $processor->hasValidSignature($payload)) {
            $webhook->update([
                'signature_valid' => false,
                'processing_status' => 'failed',
                'processed_at' => now(),
                'error_message' => 'Signature Midtrans tidak valid.',
            ]);

            return response()->json([
                'message' => 'Signature Midtrans tidak valid.',
            ], 403);
        }

        $webhook->update(['signature_valid' => true]);

        try {
            $processor->apply($payload);

            $webhook->update([
                'processing_status' => 'processed',
                'processed_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            $webhook->update([
                'processing_status' => 'failed',
                'processed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
            report($exception);

            return response()->json([
                'message' => 'Notifikasi Midtrans belum dapat diproses.',
            ], 500);
        }

        return response()->json([
            'message' => 'Notifikasi Midtrans berhasil diproses.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function eventId(array $payload): string
    {
        $transactionId = (string) ($payload['transaction_id'] ?? 'unknown');
        $status = (string) $payload['transaction_status'];
        $fraudStatus = (string) ($payload['fraud_status'] ?? 'none');

        return "{$transactionId}:{$status}:{$fraudStatus}";
    }
}
