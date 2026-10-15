@extends('layouts.admin')
@section('title', 'Pelaksanaan · '.$session->title)
@section('eyebrow', $batch->code)
@section('heading', 'Pelaksanaan dan absensi')
@push('styles')
<link rel="stylesheet" href="{{ asset('templates/welding-school/operations.css') }}">
<link rel="stylesheet" href="{{ asset('templates/welding-school/attendance.css') }}?v={{ filemtime(public_path('templates/welding-school/attendance.css')) }}">
@endpush
@section('content')
@php
    $open = $batch->operational_status === 'running' && $session->status === 'published';
    $teamTrainer = auth()->user()->can('operations.manage-all') || $batch->assignments()->where('user_id', auth()->id())->whereIn('role', ['lead_trainer', 'assistant_trainer'])->exists();
    $reviewPermission = $session->attendance_finalized_at ? 'attendance.correct' : 'attendance.manage';
    $canReview = $teamTrainer && auth()->user()->can($reviewPermission) && $session->status !== 'cancelled' && $batch->operational_status !== 'cancelled'
        && $session->execution_status !== 'scheduled' && ($open || $session->attendance_finalized_at);
@endphp
<section class="admin-page-heading"><div><h1>{{ $session->title }}</h1><p>{{ $batch->name }} · {{ \App\Models\BatchSession::TYPES[$session->type] }} · {{ $session->location->fullName() }}</p></div><a class="button button--outline" href="{{ route('admin.operations.show', ['batch' => $batch, 'tab' => 'schedule']) }}">Kembali ke jadwal</a></section>
<p><a class="button button--outline" href="{{ route('admin.operations.practice.show', [$batch, $session]) }}">Siapkan / periksa tugas & logbook sesi</a></p>
<div class="ops-summary">
    <div><span>Jadwal · {{ config('app.timezone') }}</span><strong>{{ $session->starts_at->format('d M Y H:i') }}<small>– {{ $session->ends_at->format('H:i') }}</small></strong></div>
    <div><span>Pelaksanaan</span><strong>{{ $session->status === 'cancelled' ? 'Dibatalkan' : \App\Models\BatchSession::EXECUTION_STATUSES[$session->execution_status] }}</strong><small>{{ $session->actual_started_at?->format('H:i') }} {{ $session->actual_ended_at ? '– '.$session->actual_ended_at->format('H:i') : '' }}</small></div>
    <div><span>Absensi</span><strong>{{ $session->attendance_finalized_at ? 'Difinalisasi' : 'Belum final' }}</strong></div>
    <div><span>Trainer sesi</span><strong>{{ $session->trainer->name }}</strong></div>
</div>
<section class="admin-panel"><header class="admin-panel__header"><div><h2>Area selfie</h2><p>Gunakan titik pusat area pelatihan dan radius dalam meter. Area terkunci setelah sesi dimulai.</p></div></header><div class="admin-panel__body">
@if($teamTrainer && auth()->user()->can('attendance.area') && $session->execution_status === 'scheduled' && $session->status !== 'cancelled' && !in_array($batch->operational_status, ['cancelled', 'completed', 'postponed']))
    <form method="POST" action="{{ route('admin.operations.execution.area', [$batch, $session]) }}" class="ops-stack" data-attendance-area data-resolve-url="{{ route('admin.operations.execution.area.resolve', [$batch, $session]) }}">@csrf @method('PUT')
        <label class="admin-field"><span>Link Google Maps / koordinat</span><input type="text" data-area-maps-input maxlength="2048" placeholder="https://maps.app.goo.gl/… atau -6.2000000, 106.8000000"><small>Tempel link pin atau pasangan latitude, longitude, lalu tekan Baca titik. Nama tempat dan pusat tampilan peta saja tidak digunakan.</small></label>
        <div class="attendance-actions"><button class="button button--outline" type="button" data-area-maps-resolve>Baca titik Google Maps</button></div>
        <div class="admin-form-grid">
            <label class="admin-field"><span>Lintang / latitude</span><input type="number" step="0.0000001" min="-90" max="90" name="attendance_latitude" required value="{{ old('attendance_latitude', $session->attendance_latitude) }}"></label>
            <label class="admin-field"><span>Bujur / longitude</span><input type="number" step="0.0000001" min="-180" max="180" name="attendance_longitude" required value="{{ old('attendance_longitude', $session->attendance_longitude) }}"></label>
            <label class="admin-field"><span>Radius area (25–1.000 meter)</span><input type="number" min="25" max="1000" name="attendance_radius_meters" required value="{{ old('attendance_radius_meters', $session->attendance_radius_meters ?? 100) }}"></label>
            <label class="admin-field"><span>Toleransi terlambat (menit)</span><input type="number" min="0" max="120" name="late_grace_minutes" required value="{{ old('late_grace_minutes', $session->late_grace_minutes) }}"></label>
        </div>
        <div class="attendance-area-preview" data-area-preview hidden><strong>Pratinjau area yang akan disimpan</strong><p data-area-preview-text></p><a data-area-map-link target="_blank" rel="noopener noreferrer">Periksa titik di Google Maps ↗</a><small>Periksa pin melalui Google Maps. Radius absensi dihitung oleh aplikasi dari titik ini.</small></div>
        <label class="attendance-area-confirm"><input type="checkbox" name="attendance_area_confirmed" value="1" data-area-confirm required><span>Saya sudah memeriksa titik pusat dan radius area pelatihan.</span></label>
        <p>Jika Anda berada di titik pusat lokasi sesi, gunakan lokasi perangkat. Pastikan titiknya sesuai sebelum menyimpan.</p>
        <div class="attendance-actions"><button class="button button--outline" type="button" data-area-location>Gunakan lokasi saya</button><button class="button button--primary">Simpan area</button></div>
        <p data-area-message role="status" aria-live="polite"></p>
        <noscript>Isi koordinat dan radius secara manual. Pembacaan link Google Maps membutuhkan JavaScript.</noscript>
    </form>
@else
    <p>{{ $session->attendance_latitude === null ? 'Area belum ditentukan.' : 'Titik '.$session->attendance_latitude.', '.$session->attendance_longitude.' · radius '.$session->attendance_radius_meters.' meter' }}</p>
    @if($session->attendance_latitude !== null && $session->attendance_longitude !== null)<a href="{{ 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($session->attendance_latitude.','.$session->attendance_longitude) }}" target="_blank" rel="noopener noreferrer">Buka area di Google Maps ↗</a>@endif
@endif
<p class="attendance-muted">GPS harus akurat hingga {{ config('attendance.max_accuracy_meters') }} meter. Jarak ditambah ketidakpastian GPS harus berada dalam radius; dekatkan diri ke titik pusat jika berada di tepi area.</p>
</div></section>
@if($teamTrainer && auth()->user()->can('operations.execution') && $open)
<section class="admin-panel"><div class="admin-panel__body ops-stack">
    @if($session->execution_status === 'scheduled')
    <h2>Mulai pelaksanaan</h2><p>Sesi dibuka paling cepat sesuai waktu jadwal pada hari yang sama. Peserta aktif lunas saat mulai menjadi daftar absensi sesi.</p>
    <form method="POST" action="{{ route('admin.operations.execution.start', [$batch, $session]) }}">@csrf<button class="button button--primary">Mulai sesi & buka absensi</button></form>
    @elseif($session->execution_status === 'running')
    <h2>Selesaikan pelaksanaan</h2><p>Minta peserta melakukan selfie keluar sebelum menutup sesi. Setelah ditutup, pengambilan selfie berhenti.</p>
    <form method="POST" action="{{ route('admin.operations.execution.complete', [$batch, $session]) }}" class="ops-stack">@csrf<label class="admin-field"><span>Ringkasan pelaksanaan dan kendala</span><textarea name="execution_notes" required maxlength="4000" rows="3">{{ old('execution_notes') }}</textarea></label><button class="button button--primary">Selesaikan sesi & tutup absensi</button></form>
    @endif
</div></section>
@endif
@if($session->execution_notes)<section class="admin-panel"><div class="admin-panel__body"><h2>Catatan pelaksanaan</h2><p class="attendance-prewrap">{{ $session->execution_notes }}</p></div></section>@endif
<section class="admin-panel"><header class="admin-panel__header"><div><h2>Absensi peserta · {{ $session->attendances->count() }} orang</h2><p>Periksa identitas pada kedua selfie. Status Hadir memerlukan bukti masuk dan keluar. Semua perubahan disimpan dalam riwayat batch.</p></div></header>
@forelse($session->attendances as $record)
<article class="attendance-review admin-panel__body">
    <div class="attendance-review-heading"><h3>{{ $record->enrollment->user->name }}</h3><span class="ops-badge">{{ \App\Models\SessionAttendance::STATUSES[$record->status] }}{{ $record->is_late ? ' · Terlambat' : '' }}</span></div>
    <div class="attendance-evidence">
    @foreach(['in' => 'Masuk', 'out' => 'Keluar'] as $kind => $label)
        <div><strong>{{ $label }}</strong><p>{{ $record->{'checked_'.$kind.'_at'}?->format('d M Y H:i:s') ?? 'Belum ada selfie' }}</p>
        @if($record->{$kind.'_photo_path'})<a href="{{ route('admin.operations.execution.photo', [$batch, $session, $record, $kind]) }}" target="_blank" rel="noopener">Periksa selfie {{ strtolower($label) }}</a><p class="attendance-muted">Jarak {{ $record->{$kind.'_distance'} }} m · akurasi {{ $record->{$kind.'_accuracy'} }} m</p>@endif</div>
    @endforeach
    <div><strong>Durasi terverifikasi</strong><p>{{ $record->attended_minutes }} menit</p>@if($record->review_notes)<p class="attendance-prewrap">{{ $record->review_notes }}</p>@endif</div>
    </div>
    @if($canReview)
    <form method="POST" action="{{ route('admin.operations.execution.review', [$batch, $session, $record]) }}" class="attendance-review-form">@csrf @method('PUT')
        <label class="admin-field"><span>Hasil pemeriksaan</span><select name="status" required><option value="">Pilih hasil</option>@foreach(['present', 'permission', 'sick', 'absent'] as $status)<option value="{{ $status }}" @selected($record->status === $status)>{{ \App\Models\SessionAttendance::STATUSES[$status] }}</option>@endforeach</select></label>
        <label class="admin-field"><span>{{ $session->attendance_finalized_at ? 'Alasan koreksi (wajib)' : 'Catatan pemeriksaan (wajib)' }}</span><input name="review_notes" maxlength="1000" required placeholder="Hasil pengecekan identitas / alasan izin, sakit, atau tidak hadir"></label>
        <button class="button button--outline">{{ $session->attendance_finalized_at ? 'Simpan koreksi' : 'Simpan pemeriksaan' }}</button>
    </form>
    @endif
</article>
@empty<div class="admin-panel__body"><p>Daftar absensi terbentuk saat sesi dimulai.</p></div>@endforelse
</section>
@if($teamTrainer && auth()->user()->can('attendance.manage') && $open && $session->execution_status === 'completed' && !$session->attendance_finalized_at)
<section class="admin-panel"><div class="admin-panel__body"><h2>Finalisasi absensi</h2><p>Seluruh peserta harus sudah diperiksa. Setelah finalisasi, hasil masuk rekap; koreksi memerlukan izin khusus dan alasan.</p><form method="POST" action="{{ route('admin.operations.execution.finalize', [$batch, $session]) }}">@csrf<button class="button button--primary">Finalisasi absensi sesi</button></form></div></section>
@endif
@endsection
@push('scripts')<script src="{{ asset('templates/welding-school/attendance-area.js') }}?v={{ filemtime(public_path('templates/welding-school/attendance-area.js')) }}" defer></script>@endpush
