<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\TrainingApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'agreement' => ['accepted'],
            'training_application_id' => ['nullable', 'integer'],
        ], [
            'agreement.accepted' => 'Kebijakan pembayaran dan pembatalan harus disetujui.',
        ]);

        [$invoice, $created] = DB::transaction(function () use ($request): array {
            $applicationQuery = TrainingApplication::query()
                ->where('user_id', $request->user()->id)
                ->where('status', 'approved')
                ->with('trainingProgram');

            if ($request->filled('training_application_id')) {
                $applicationQuery->whereKey($request->integer('training_application_id'));
            }

            $application = $applicationQuery
                ->latest('verified_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $application) {
                throw ValidationException::withMessages([
                    'application' => 'Invoice hanya dapat dibuat setelah pendaftaran disetujui admin.',
                ]);
            }

            $existingInvoice = $application->invoice()->first();

            if ($existingInvoice) {
                return [$existingInvoice, false];
            }

            $subtotal = (int) $application->trainingProgram->price;
            $administrationFee = max(0, (int) config('billing.administration_fee'));
            $dueHours = max(1, (int) config('billing.invoice_due_hours'));

            $invoice = Invoice::query()->create([
                'invoice_number' => $this->invoiceNumber(),
                'training_application_id' => $application->id,
                'user_id' => $request->user()->id,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'total_amount' => $subtotal + $administrationFee,
                'currency' => 'IDR',
                'status' => 'unpaid',
                'issued_at' => now(),
                'due_at' => now()->addHours($dueHours),
            ]);

            return [$invoice, true];
        });

        return response()->json([
            'message' => $created
                ? 'Invoice berhasil dibuat. Selesaikan pembayaran sebelum batas waktu.'
                : 'Invoice yang sudah ada berhasil dimuat.',
            'invoice' => InvoiceResource::make($invoice)->resolve($request),
        ], $created ? 201 : 200);
    }

    private function invoiceNumber(): string
    {
        do {
            $number = 'WS-INV-'.now()->format('ymd').'-'.random_int(1000, 9999);
        } while (Invoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }
}
