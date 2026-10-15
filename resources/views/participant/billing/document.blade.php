@php
    $money = fn ($value) => $invoice->currency.' '.number_format($value, 0, ',', '.');
    $date = fn ($value) => $value ? $value->copy()->timezone(config('app.timezone'))->locale('id')->translatedFormat('j F Y H:i') : 'Tidak tercatat';
    $paid = $invoice->status === 'paid';
@endphp
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><title>{{ $receipt ? 'Kuitansi' : 'Invoice' }} {{ $documentNumber }}</title>
<style>
    @page { margin: 36px 40px 55px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #183449; line-height: 1.5; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    td, th { vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
    .header { border-bottom: 3px solid #148c61; margin-bottom: 24px; }
    .header td { padding-bottom: 16px; }
    .logo { width: 42px; height: 50px; }
    .brand { font-size: 14pt; font-weight: bold; color: #0b2c49; }
    .muted { color: #617586; font-size: 8.5pt; }
    .right { text-align: right; }
    h1 { font-size: 22pt; color: #0b2c49; margin: 0 0 5px; }
    .number { font-size: 10pt; font-weight: bold; }
    .status { color: {{ $paid ? '#148c61' : '#915d17' }}; font-weight: bold; font-size: 10pt; margin-top: 10px; }
    .identity td { width: 50%; padding: 0 16px 20px 0; }
    .label { text-transform: uppercase; letter-spacing: 1px; font-size: 8pt; color: #617586; margin-bottom: 5px; }
    .name { font-size: 11pt; font-weight: bold; }
    .details { background: #eff5f8; padding: 14px; margin: 0 0 20px; }
    .details td { padding: 4px 6px; }
    .items th { background: #0b2c49; color: white; text-align: left; padding: 10px 12px; }
    .items th.right { text-align: right; }
    .items td { padding: 12px; border-bottom: 1px solid #dde5eb; }
    .amount { width: 145px; }
    .totals { margin-top: 12px; }
    .totals td { padding: 7px 12px; }
    .total { background: #e9f8f2; color: #148c61; font-weight: bold; font-size: 11pt; }
    .note { border: 1px solid #dde5eb; background: #f8fafb; padding: 14px; margin-top: 24px; }
    .footer { margin-top: 20px; color: #617586; font-size: 8pt; }
    .keep { page-break-inside: avoid; }
</style></head>
<body>
<table class="header"><tr>
    <td style="width: 55px">@if($logo)<img class="logo" src="{{ $logo }}" alt="">@endif</td>
    <td><div class="brand">{{ config('branding.name') }}</div><div>{{ config('branding.company') }}</div><div class="muted">{{ config('branding.service') }}</div></td>
    <td class="right" style="width: 195px"><h1>{{ $receipt ? 'KUITANSI' : 'INVOICE' }}</h1><div class="number">{{ $documentNumber }}</div><div class="status">{{ $statusLabel }}</div></td>
</tr></table>
<table class="identity"><tr>
    <td><div class="label">{{ $receipt ? 'Diterima dari' : 'Ditagihkan kepada' }}</div><div class="name">{{ $payerName }}</div><div>{{ $payerEmail }}</div><div class="muted">Pendaftaran: {{ $registrationNumber }}</div></td>
    <td><div class="label">Penyelenggara</div><div class="name">{{ config('branding.company') }}</div><div>Komplek PT. Komoko Batam Centre Blok A No. 7, Batam</div><div class="muted">info@alphaacademy.id | +62 895-6035-02918</div></td>
</tr></table>
<div class="details"><table>
    <tr><td style="width: 125px">Tanggal invoice</td><td>{{ $date($invoice->issued_at) }}</td></tr>
    @if($paid)<tr><td>Tanggal pembayaran</td><td>{{ $date($payment?->paid_at ?? $invoice->paid_at) }}</td></tr>@endif
    @if($receipt)<tr><td>Referensi invoice</td><td>{{ $invoice->invoice_number }}</td></tr>@endif
    @if($payment)<tr><td>Metode pembayaran</td><td>{{ $paymentMethod }}</td></tr>@endif
</table></div>
<table class="items"><thead><tr><th>Rincian pelatihan</th><th class="right amount">Jumlah</th></tr></thead><tbody>
    <tr><td><strong>{{ $program }}</strong>@if($batch)<div class="muted">{{ $batch }}</div>@endif</td><td class="right">{{ $money($invoice->subtotal) }}</td></tr>
    @if($administrationFee > 0)<tr><td>Biaya administrasi</td><td class="right">{{ $money($administrationFee) }}</td></tr>@endif
    @if($invoice->discount_amount > 0)<tr><td>Diskon</td><td class="right">- {{ $money($invoice->discount_amount) }}</td></tr>@endif
</tbody></table>
<div class="keep">
<table class="totals"><tr class="total"><td>{{ $receipt ? 'Total diterima' : 'Total tagihan' }}</td><td class="right amount">{{ $money($invoice->total_amount) }}</td></tr></table>
<div class="note">
    @if($receipt)
        Pembayaran untuk invoice di atas telah tercatat lunas. Kuitansi elektronik ini merupakan bukti penerimaan pembayaran pelatihan.
    @elseif($paid)
        Invoice telah berstatus LUNAS. Kuitansi pembayaran tersedia terpisah melalui dashboard peserta setelah transaksi terverifikasi.
    @else
        Dokumen ini adalah tagihan, bukan bukti pembayaran. Status pembayaran mengikuti pencatatan pada dashboard peserta.
    @endif
</div>
<div class="footer">Dokumen diterbitkan secara elektronik oleh {{ config('branding.name') }}. Simpan dokumen ini sebagai arsip pembayaran Anda. Untuk informasi pembayaran, hubungi penyelenggara melalui kontak di atas.</div>
</div>
</body></html>
