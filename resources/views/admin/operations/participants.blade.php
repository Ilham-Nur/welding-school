<section class="admin-panel">
    <header class="admin-panel__header"><div><h2>Peserta batch</h2><p>Peserta aktif dihitung dari enrollment aktif dengan invoice lunas. Riwayat peserta nonaktif tetap ditampilkan.</p></div></header>
    @if ($participants->isEmpty())
        <div class="admin-empty"><h2>Belum ada enrollment</h2><p>Peserta muncul otomatis setelah pembayaran berhasil dikonfirmasi.</p></div>
    @else
        <x-ui.table class="admin-table-wrap"><thead><tr><th>Peserta</th><th>Enrollment</th><th>Pendaftaran</th><th>Pembayaran</th><th>Status peserta</th></tr></thead><tbody>
            @foreach ($participants as $participant)
                @php($invoice = $participant->trainingApplication?->invoice)
                <tr><td><strong>{{ $participant->user->name }}</strong><small>{{ $participant->user->email }}</small></td><td>{{ $participant->enrollment_number }}<small>{{ $participant->enrolled_at?->format('d M Y') }}</small></td><td>{{ $participant->trainingApplication?->registration_number }}</td><td>{{ ['paid' => 'Lunas', 'unpaid' => 'Belum lunas', 'refunded' => 'Dikembalikan', 'expired' => 'Kedaluwarsa'][$invoice?->status] ?? 'Belum dikonfirmasi' }}<small>{{ $invoice?->paid_at?->format('d M Y H:i') }}</small></td><td><span class="ops-badge">{{ $participant->status === 'active' && $invoice?->status === 'paid' ? 'Aktif' : (['cancelled' => 'Dibatalkan', 'completed' => 'Selesai'][$participant->status] ?? 'Tidak aktif / pembayaran belum lunas') }}</span></td></tr>
            @endforeach
        </tbody></x-ui.table><x-ui.pagination :paginator="$participants" />
    @endif
</section>
