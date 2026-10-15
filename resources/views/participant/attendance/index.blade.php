@extends('layouts.participant')
@section('page-title', 'Kehadiran Peserta')
@section('training-section', 'attendance')
@section('title', 'Kehadiran · '.$batch->name)
@section('robots', 'noindex, nofollow')
@push('styles')<link rel="stylesheet" href="{{ asset('templates/welding-school/attendance.css') }}?v={{ filemtime(public_path('templates/welding-school/attendance.css')) }}">@endpush
@section('content')
<div class="attendance-shell">
    <h1>Kehadiran peserta</h1><p>{{ $batch->trainingProgram->title }} · {{ $batch->name }}</p>
    <div class="attendance-stats"><div><span>Kehadiran final</span><strong>{{ $summary['percentage'] === null ? 'Belum ada rekap' : $summary['percentage'].'%' }}</strong><small>{{ $summary['present'] }} hadir dari {{ $summary['sessions'] }} sesi final</small></div><div><span>Kehadiran praktik terverifikasi</span><strong>{{ $summary['practice_minutes'] }} menit</strong><small>Durasi masuk–keluar yang diterima trainer</small></div></div>
    <p>Absen masuk dan keluar menggunakan selfie langsung dari kamera di area sesi. Selfie dan lokasi hanya digunakan untuk bukti absensi dan diperiksa petugas pelatihan.</p>
    @forelse($sessions as $session)
    @php($record = $session->attendances->first())
    <article class="attendance-card"><h2>{{ $session->title }}</h2><p>{{ $session->starts_at->format('d M Y H:i') }}–{{ $session->ends_at->format('H:i') }} · {{ config('app.timezone') }}</p><p>{{ $session->location->name }} · {{ \App\Models\BatchSession::EXECUTION_STATUSES[$session->execution_status] }}</p><p><strong>{{ $record ? \App\Models\SessionAttendance::STATUSES[$record->status] : 'Absensi belum dibuka' }}</strong>{{ $record?->is_late ? ' · Terlambat' : '' }}{{ $session->attendance_finalized_at ? ' · Final' : '' }}</p><a class="button button--primary" href="{{ route('participant.attendance.show', [$batch, $session]) }}">{{ $session->execution_status === 'running' ? 'Buka absensi selfie' : 'Lihat absensi sesi' }}</a></article>
    @empty<div class="attendance-card"><h2>Belum ada sesi diterbitkan</h2><p>Jadwal akan tersedia setelah diterbitkan trainer.</p></div>@endforelse
</div>
@endsection
