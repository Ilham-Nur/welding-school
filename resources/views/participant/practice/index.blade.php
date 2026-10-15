@extends('layouts.participant')
@section('page-title', 'Tugas & Logbook')
@section('training-section', 'logbook')
@section('title', 'Tugas & Logbook · '.$batch->name)
@push('styles')<link rel="stylesheet" href="{{ asset('templates/welding-school/practice.css') }}?v={{ filemtime(public_path('templates/welding-school/practice.css')) }}">@endpush
@section('content')
<div class="practice-shell">
    <h1>Tugas & logbook</h1>
    <p>Satu pengiriman untuk tugas sekaligus catatan praktik. Data sesi sudah terisi; cukup pilih hasil dan tambahkan bukti jika diminta.</p>
    <div class="practice-stats">
        <div><span>Menunggu pemeriksaan</span><strong>{{ $summary['submitted'] }}</strong></div>
        <div><span>Perlu perbaikan</span><strong>{{ $summary['revision'] }}</strong></div>
        <div><span>Catatan diterima</span><strong>{{ $summary['accepted'] }}</strong></div>
        <div><span>Praktik terverifikasi</span><strong>{{ $summary['minutes'] }} menit</strong><small>Bukan otomatis dari durasi absensi</small></div>
    </div>
    @forelse($tasks as $task)
    @php($record = $task->submissions->first())
    <article class="practice-card">
        <div class="practice-card-heading"><div><span class="eyebrow">{{ $task->session->starts_at->isToday() ? 'AKTIVITAS HARI INI' : 'AKTIVITAS SESI' }}</span><h2>{{ $task->title }}</h2></div><span class="practice-badge">{{ $record ? \App\Models\PracticeSubmission::STATUSES[$record->status] : 'Belum diisi' }}</span></div>
        <p>{{ $task->session->title }} · {{ $task->session->starts_at->locale('id')->translatedFormat('j F Y H:i') }}</p>
        <p>{{ $task->session->trainer->name }} · {{ $task->session->location->name }}</p>
        @if($record?->outcome)<p><strong>{{ \App\Models\PracticeSubmission::OUTCOMES[$record->outcome] }}</strong>@if($record->verified_minutes !== null) · {{ $record->verified_minutes }} menit praktik terverifikasi @endif</p>@endif
        @if($record?->status === 'revision')<p class="practice-feedback">{{ $record->review_notes }}</p>@endif
        <a class="button button--primary" href="{{ route('participant.practice.show', [$batch, $task]) }}">{{ $record?->status === 'revision' ? 'Perbaiki hasil' : ($record && in_array($record->status, ['submitted', 'accepted']) ? 'Lihat hasil & logbook' : ($task->session->execution_status === 'scheduled' ? 'Lihat instruksi' : 'Isi hasil aktivitas')) }}</a>
    </article>
    @empty<div class="practice-card"><h2>Belum ada aktivitas</h2><p>Aktivitas akan muncul setelah instruktur menyiapkannya untuk sesi pelatihan. Anda tidak perlu membuat logbook kosong.</p></div>@endforelse
</div>
@endsection
