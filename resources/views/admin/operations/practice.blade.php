@extends('layouts.admin')
@section('title', 'Tugas & Logbook · '.$session->title)
@section('eyebrow', $batch->code)
@section('heading', 'Tugas & logbook sesi')
@push('styles')
<link rel="stylesheet" href="{{ asset('templates/welding-school/operations.css') }}">
<link rel="stylesheet" href="{{ asset('templates/welding-school/practice.css') }}?v={{ filemtime(public_path('templates/welding-school/practice.css')) }}">
@endpush
@section('content')
@php
    $teamTrainer = auth()->user()->can('operations.manage-all') || $batch->assignments()->where('user_id', auth()->id())->whereIn('role', ['lead_trainer', 'assistant_trainer'])->exists();
    $canConfigure = $teamTrainer && auth()->user()->can('practice.manage') && !in_array($batch->operational_status, ['cancelled', 'completed', 'postponed']) && $session->status !== 'cancelled' && (!$task || $task->submissions->isEmpty());
    $canReview = $teamTrainer && auth()->user()->can('practice.review') && in_array($batch->operational_status, ['running', 'completed']) && $session->status === 'published';
    $records = $task?->submissions->keyBy('enrollment_id') ?? collect();
    $roster = $enrollments->merge($task?->submissions->pluck('enrollment') ?? collect())->unique('id');
@endphp
<section class="admin-page-heading"><div><h1>{{ $session->title }}</h1><p>{{ $batch->name }} · {{ $session->starts_at->locale('id')->translatedFormat('j F Y H:i') }}</p></div><a class="button button--outline" href="{{ route('admin.operations.execution.show', [$batch, $session]) }}">Pelaksanaan & absensi</a></section>
<section class="admin-panel"><header class="admin-panel__header"><div><h2>Aktivitas bersama untuk sesi ini</h2><p>Satu aktivitas per sesi, satu pengiriman per peserta. Instruksi terkunci setelah peserta mulai menyimpan hasil.</p></div></header><div class="admin-panel__body">
@if($canConfigure)
    <form method="POST" action="{{ route('admin.operations.practice.configure', [$batch, $session]) }}" class="ops-stack">@csrf @method('PUT')
        <label class="admin-field"><span>Aktivitas / tugas</span><input name="title" required maxlength="255" value="{{ old('title', $task?->title ?? $session->title) }}"></label>
        <label class="admin-field"><span>Instruksi singkat / acuan job sheet</span><textarea name="instructions" required maxlength="4000" rows="3">{{ old('instructions', $task?->instructions ?? 'Kerjakan aktivitas sesuai arahan instruktur dan job sheet sesi. Kirim hasil setelah latihan.') }}</textarea></label>
        <label class="admin-field"><span>Bukti foto</span><select name="photo_required"><option value="1" @selected(old('photo_required', $task?->photo_required ?? true))>Diminta jika pekerjaan dilakukan</option><option value="0" @selected(!old('photo_required', $task?->photo_required ?? true))>Opsional</option></select></label>
        @if($session->type === 'practice')<label class="admin-field"><span>Acuan durasi praktik efektif (menit, opsional)</span><input name="effective_minutes" type="number" min="1" max="1440" value="{{ old('effective_minutes', $task?->effective_minutes) }}"><small>Tidak termasuk jeda. Acuan saja, bukan jam terverifikasi otomatis; instruktur tetap memeriksa durasi tiap peserta.</small></label>@endif
        <button class="button button--primary">{{ $task ? 'Perbarui aktivitas' : 'Terbitkan aktivitas' }}</button>
    </form>
@elseif($task)
    <h3>{{ $task->title }}</h3><p class="practice-prewrap">{{ $task->instructions }}</p><p>{{ $task->photo_required ? 'Foto diminta untuk pekerjaan yang dilakukan.' : 'Foto opsional.' }} @if($task->effective_minutes) Acuan praktik efektif: {{ $task->effective_minutes }} menit. @endif</p>
@else<p>Aktivitas belum disiapkan. Trainer batch dengan izin tugas dapat menyiapkannya.</p>@endif
</div></section>
@if($task)
<section class="admin-panel"><header class="admin-panel__header"><div><h2>Hasil peserta</h2><p>Periksa bukti sebelum menerima. Catatan diterima bukan otomatis tugas selesai, kelulusan, atau jam praktik.</p></div></header>
@forelse($roster as $enrollment)
@php($record = $records->get($enrollment->id))
<article class="admin-panel__body practice-review">
    <div class="practice-card-heading"><h3>{{ $enrollment->user->name }}</h3><span class="practice-badge">{{ $record ? \App\Models\PracticeSubmission::STATUSES[$record->status] : 'Belum diisi' }}</span></div>
    @if($record && $record->status !== 'draft')
        <p><strong>{{ \App\Models\PracticeSubmission::OUTCOMES[$record->outcome] ?? 'Belum dipilih' }}</strong>@if($record->obstacle) · Kendala {{ \App\Models\PracticeSubmission::OBSTACLES[$record->obstacle] }} @endif</p>
        @if($record->notes)<p class="practice-prewrap">{{ $record->notes }}</p>@endif
        @if($record->photo_path)<a href="{{ route('admin.operations.practice.photo', [$batch, $session, $record]) }}" target="_blank" rel="noopener"><img class="practice-photo" src="{{ route('admin.operations.practice.photo', [$batch, $session, $record]) }}" loading="lazy" alt="Hasil pekerjaan {{ $enrollment->user->name }}"></a>@endif
        @if($record->review_notes)<p class="practice-feedback">{{ $record->review_notes }}</p>@endif
        @if($record->verified_minutes !== null)<p>{{ $record->verified_minutes }} menit praktik terverifikasi</p>@endif
        @if($canReview && in_array($record->status, ['submitted', 'accepted']))
        <form method="POST" action="{{ route('admin.operations.practice.review', [$batch, $session, $record]) }}" class="ops-stack">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $record->version }}">
            <label class="admin-field"><span>Catatan (wajib hanya jika meminta perbaikan)</span><textarea name="review_notes" rows="2" maxlength="1000" placeholder="Apa yang perlu diperbaiki?">{{ $record->status === 'accepted' ? $record->review_notes : '' }}</textarea></label>
            @if($session->type === 'practice' && $session->attendance_finalized_at && $record->outcome !== 'not_started')
            <label class="admin-field"><span>Durasi yang Anda verifikasi (menit, opsional)</span><input name="verified_minutes" type="number" min="1" max="1440" value="{{ $record->verified_minutes }}" placeholder="{{ $task->effective_minutes ? 'Acuan sesi: '.$task->effective_minutes : 'Kosongkan jika belum diverifikasi' }}"><small>Isi hanya durasi praktik efektif yang Anda periksa. Tidak boleh melebihi kehadiran final atau jadwal sesi.</small></label>
            @else<p class="practice-muted">Anda dapat menerima catatan tanpa jam praktik. Verifikasi durasi tersedia saat absensi sesi sudah final.</p>@endif
            <div class="practice-actions"><button class="button button--primary" name="status" value="accepted">{{ $record->status === 'accepted' ? 'Simpan verifikasi' : 'Terima catatan' }}</button>@if($batch->operational_status !== 'completed')<button class="button button--outline" name="status" value="revision">Minta perbaikan</button>@endif</div>
        </form>
        @endif
        <details><summary>Riwayat pemeriksaan</summary><ol>@foreach($record->events as $event)<li>{{ $event->created_at->locale('id')->translatedFormat('j F Y H:i') }} · {{ $event->action === 'hours_invalidated' ? 'Durasi perlu diverifikasi ulang setelah koreksi absensi' : (\App\Models\PracticeSubmission::STATUSES[$event->action] ?? $event->action) }}</li>@endforeach</ol></details>
    @elseif($record)<p>Draf pribadi peserta; belum dikirim untuk diperiksa.</p>@endif
</article>
@empty<div class="admin-panel__body"><p>Belum ada peserta aktif lunas.</p></div>@endforelse
</section>
@endif
@endsection
