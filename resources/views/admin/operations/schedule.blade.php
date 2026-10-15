<section class="admin-panel">
    <header class="admin-panel__header"><div><h2>Jadwal sesi pelatihan</h2><p>Waktu menggunakan zona {{ config('app.timezone') }}. Sesi yang diterbitkan tidak boleh bertabrakan.</p></div>@if ($editable && auth()->user()->can('operations.schedule'))<a class="button button--primary admin-button" href="{{ route('admin.operations.sessions.create', $batch) }}">+ Tambah sesi</a>@endif</header>
    @if ($sessions->isEmpty())<div class="admin-empty"><h2>Belum ada sesi</h2><p>Tentukan Trainer terlebih dahulu, lalu buat jadwal orientasi, teori, atau praktik.</p></div>@else
        <x-ui.table class="admin-table-wrap"><thead><tr><th>Sesi</th><th>Waktu</th><th>Trainer</th><th>Lokasi</th><th>Status</th><th></th></tr></thead><tbody>
            @foreach ($sessions as $session)
                <tr><td><strong>{{ $session->title }}</strong><small>{{ \App\Models\BatchSession::TYPES[$session->type] }}</small></td><td>{{ $session->starts_at->format('d M Y H:i') }}<small>s.d. {{ $session->ends_at->format('d M Y H:i') }}</small></td><td>{{ $session->trainer->name }}</td><td>{{ $session->location->fullName() }}</td><td><span class="ops-badge ops-badge--{{ $session->status }}">{{ \App\Models\BatchSession::STATUSES[$session->status] }}</span><small>{{ \App\Models\BatchSession::EXECUTION_STATUSES[$session->execution_status] }}</small></td><td><div class="ops-session-actions"><a class="button button--outline admin-button" href="{{ route('admin.operations.execution.show', [$batch, $session]) }}">Pelaksanaan & absensi</a>@if ($editable && $session->execution_status === 'scheduled' && auth()->user()->can('operations.schedule'))<a class="button button--outline admin-button" href="{{ route('admin.operations.sessions.edit', [$batch, $session]) }}">Edit sesi</a>
                    @if ($session->status !== 'cancelled')<button type="button" class="button button--outline admin-button ops-action--danger" data-modal-open="cancel-session-{{ $session->id }}" aria-haspopup="dialog" aria-controls="cancel-session-{{ $session->id }}">Batalkan sesi</button>@endif
                @endif <a class="button button--outline admin-button" href="{{ route('admin.operations.practice.show', [$batch, $session]) }}">Tugas & logbook</a></div></td></tr>
            @endforeach
        </tbody></x-ui.table><x-ui.pagination :paginator="$sessions" />
    @endif
</section>
@if ($editable && auth()->user()->can('operations.schedule'))
    @foreach ($sessions as $session)
        @if ($session->status !== 'cancelled' && $session->execution_status === 'scheduled')
            <x-ui.action-modal :id="'cancel-session-'.$session->id" title="Batalkan sesi?" :action="route('admin.operations.sessions.cancel', [$batch, $session])" confirm-label="Ya, batalkan sesi" reason-label="Alasan pembatalan" placeholder="Contoh: Trainer berhalangan hadir. Sesi pengganti akan dijadwalkan ulang.">
                <p>Sesi <strong>{{ $session->title }}</strong> pada {{ $session->starts_at->locale('id')->translatedFormat('j F Y H:i') }} akan dibatalkan. Jadwal dan riwayat sesi tetap tersimpan.</p>
            </x-ui.action-modal>
        @endif
    @endforeach
@endif
