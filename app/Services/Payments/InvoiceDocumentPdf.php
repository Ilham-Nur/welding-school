<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;

class InvoiceDocumentPdf
{
    public function data(Invoice $invoice, bool $receipt = false): array
    {
        $invoice->loadMissing(['trainingApplication.trainingProgram', 'trainingApplication.trainingBatch', 'user']);
        $payment = $invoice->receiptPayment();
        abort_if($receipt && ! $payment, 409, 'Kuitansi hanya tersedia setelah pembayaran terverifikasi lunas.');
        $snapshot = $invoice->trainingApplication->personal_data_snapshot ?? [];
        $status = $invoice->status === 'unpaid' && $invoice->due_at?->isPast() ? 'expired' : $invoice->status;
        $labels = ['unpaid' => 'MENUNGGU PEMBAYARAN', 'paid' => 'LUNAS', 'expired' => 'KEDALUWARSA', 'cancelled' => 'DIBATALKAN', 'refunded' => 'DIKEMBALIKAN'];
        $logoPath = public_path('logo_alpha.png');

        return [
            'invoice' => $invoice, 'payment' => $payment, 'receipt' => $receipt,
            'paymentMethod' => $payment ? $this->paymentMethodLabel($payment->payment_method) : null,
            'documentNumber' => $receipt ? 'KWT-'.$invoice->invoice_number : $invoice->invoice_number,
            'statusLabel' => $labels[$status] ?? strtoupper($status),
            'payerName' => $snapshot['full_name'] ?? $invoice->user->name,
            'payerEmail' => $snapshot['email'] ?? $invoice->user->email,
            'program' => $invoice->trainingApplication->trainingProgram?->title ?? 'Program pelatihan',
            'batch' => $invoice->trainingApplication->trainingBatch?->name,
            'registrationNumber' => $invoice->trainingApplication->registration_number,
            'administrationFee' => max(0, $invoice->total_amount - $invoice->subtotal + $invoice->discount_amount),
            'logo' => is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(File::get($logoPath)) : null,
        ];
    }

    private function paymentMethodLabel(?string $method): string
    {
        return match (strtolower(trim($method ?? ''))) {
            'bank_transfer', 'transfer', 'echannel', 'virtual_account', 'va-bca', 'va-mandiri', 'other-va',
            'bca_va', 'bni_va', 'bri_va', 'permata_va', 'mandiri_va' => 'Transfer',
            'qris', 'other_qris' => 'QRIS',
            'gopay' => 'GoPay',
            'shopeepay' => 'ShopeePay',
            'ovo' => 'OVO',
            'dana' => 'DANA',
            'credit_card' => 'Kartu Kredit',
            'cstore' => 'Gerai Retail',
            '' => 'Tidak dicantumkan',
            default => 'Pembayaran lainnya',
        };
    }

    public function render(Invoice $invoice, bool $receipt = false): string
    {
        $directory = storage_path('app/private/billing-pdf-runtime');
        File::ensureDirectoryExists($directory);
        $options = new Options([
            'defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false,
            'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'isFontSubsettingEnabled' => true,
            'tempDir' => $directory, 'fontCache' => $directory,
            'chroot' => [public_path(), base_path('vendor/dompdf')],
        ]);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('participant.billing.document', $this->data($invoice, $receipt))->render(), 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $pdf->getCanvas()->page_text(40, 805, ($receipt ? 'Kuitansi ' : 'Invoice ').$invoice->invoice_number.' | Halaman {PAGE_NUM} / {PAGE_COUNT}', 'DejaVu Sans', 8, [0.4, 0.45, 0.5]);

        return $pdf->output();
    }
}
