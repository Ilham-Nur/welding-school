@extends('layouts.participant')
@section('page-title', 'Absensi Sesi')
@section('training-section', 'attendance')
@section('title', 'Absensi selfie · '.$session->title)
@section('robots', 'noindex, nofollow')
@push('styles')<link rel="stylesheet" href="{{ asset('templates/welding-school/attendance.css') }}?v={{ filemtime(public_path('templates/welding-school/attendance.css')) }}">@endpush
@section('content')
@php
    $canCapture = $record && $session->execution_status === 'running' && $batch->operational_status === 'running' && !$session->attendance_finalized_at
        && ($record->status === 'unrecorded' || ($record->status === 'pending' && !$record->checked_out_at));
    $kind = $record?->checked_in_at ? 'out' : 'in';
@endphp
<div class="attendance-shell">
<a href="{{ route('participant.attendance.index', $batch) }}">← Kehadiran</a><h1>{{ $session->title }}</h1>
<p>{{ $batch->name }} · {{ $session->location->name }} · {{ $session->starts_at->format('d M Y H:i') }}–{{ $session->ends_at->format('H:i') }} · {{ config('app.timezone') }}</p>
@if($session->attendance_latitude !== null && $session->attendance_longitude !== null)
<p><a class="button button--outline" href="{{ 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($session->attendance_latitude.','.$session->attendance_longitude) }}" target="_blank" rel="noopener noreferrer">Buka lokasi absensi di Google Maps ↗</a></p>
<p class="attendance-muted">Radius {{ $session->attendance_radius_meters }} meter dari titik pusat. Link peta hanya petunjuk; absensi tetap memeriksa lokasi aktual perangkat saat selfie.</p>
@endif
<section class="attendance-card"><h2>{{ $record ? \App\Models\SessionAttendance::STATUSES[$record->status] : 'Absensi belum dibuka' }}</h2><p>{{ $session->attendance_finalized_at ? 'Hasil sudah difinalisasi.' : 'Hasil baru masuk rekap setelah diperiksa dan difinalisasi trainer.' }}</p>
@if($record)<div class="attendance-evidence">@foreach(['in' => 'Masuk', 'out' => 'Keluar'] as $side => $label)<div><strong>{{ $label }}</strong><p>{{ $record->{'checked_'.$side.'_at'}?->format('H:i:s') ?? 'Belum tercatat' }}</p>@if($record->{$side.'_photo_path'})<a href="{{ route('participant.attendance.photo', [$batch, $session, $record, $side]) }}" target="_blank" rel="noopener">Lihat selfie {{ strtolower($label) }}</a>@endif</div>@endforeach</div>@if($record->review_notes)<p class="attendance-prewrap">Catatan trainer: {{ $record->review_notes }}</p>@endif @endif
</section>
@if($canCapture)
<section class="attendance-card" data-selfie-attendance
    data-challenge-url="{{ route('participant.attendance.challenge', [$batch, $session]) }}"
    data-submit-url="{{ route('participant.attendance.store', [$batch, $session]) }}"
    data-kind="{{ $kind }}" data-latitude="{{ $session->attendance_latitude }}" data-longitude="{{ $session->attendance_longitude }}"
    data-radius="{{ $session->attendance_radius_meters }}" data-max-accuracy="{{ config('attendance.max_accuracy_meters') }}">
    <h2>Selfie absen {{ $kind === 'in' ? 'masuk' : 'keluar' }}</h2>
    <p>Pastikan wajah terlihat jelas dan Anda berada di {{ $session->location->name }}. Radius absensi {{ $session->attendance_radius_meters }} meter. Izinkan akses kamera dan lokasi presisi saat diminta.</p>
    <p class="attendance-muted">Foto dan lokasi disimpan bersama waktu server sebagai bukti absensi. Kamera dimatikan setelah foto diambil atau halaman ditinggalkan.</p>
    <div class="attendance-camera"><video data-camera autoplay playsinline muted hidden aria-label="Pratinjau kamera selfie"></video><img data-preview hidden alt="Selfie yang akan dikirim"><div data-camera-placeholder>Kamera akan terbuka setelah Anda menekan tombol di bawah.</div></div>
    <p data-capture-message role="status" aria-live="polite"></p>
    <div class="attendance-actions"><button class="button button--primary" type="button" data-camera-start>Buka kamera & periksa lokasi</button><button class="button button--primary" type="button" data-camera-capture hidden>Ambil selfie</button><button class="button button--outline" type="button" data-camera-retake hidden>Foto ulang</button><button class="button button--primary" type="button" data-camera-submit hidden>Kirim absensi {{ $kind === 'in' ? 'masuk' : 'keluar' }}</button></div>
    <noscript>Aktifkan JavaScript untuk mengambil selfie dan lokasi.</noscript>
</section>
@else<section class="attendance-card"><p>{{ $session->execution_status === 'scheduled' ? 'Trainer belum membuka sesi.' : 'Pengambilan selfie tidak tersedia atau bukti masuk dan keluar sudah lengkap.' }}</p></section>@endif
</div>
@endsection
@push('scripts')<script src="{{ asset('templates/welding-school/attendance-capture.js') }}?v={{ filemtime(public_path('templates/welding-school/attendance-capture.js')) }}" defer></script>@endpush
