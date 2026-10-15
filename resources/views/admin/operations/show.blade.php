@extends('layouts.admin')
@section('title', $batch->name.' · Operasional')
@section('eyebrow', 'Pelaksanaan pelatihan')
@section('heading', 'Operasional Batch')
@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/operations.css') }}?v={{ filemtime(public_path('templates/welding-school/operations.css')) }}">
@endpush
@include('admin.training-materials.style')
@section('content')
    <section class="admin-page-heading">
        <div><p class="ops-kicker">{{ $batch->code }} / {{ $batch->trainingProgram->title }}</p><h1>{{ $batch->name }}</h1><p>{{ $batch->start_date->format('d M Y') }} – {{ $batch->end_date?->format('d M Y') ?? 'Tanggal selesai belum ditentukan' }}</p></div>
        <a class="button button--outline admin-button" href="{{ route('admin.operations.index') }}">Kembali ke daftar</a>
    </section>
    <div class="ops-summary">
        <div><span>Status operasional</span><strong><span class="ops-badge ops-badge--{{ $batch->operational_status }}">{{ \App\Models\TrainingBatch::OPERATIONAL_STATUSES[$batch->operational_status] ?? $batch->operational_status }}</span></strong></div>
        <div><span>Peserta aktif lunas</span><strong>{{ $batch->active_paid_enrollments_count }} <small>/ {{ $batch->capacity }} kursi</small></strong></div>
        <div><span>Kesiapan wajib</span><strong>{{ collect($readiness)->where('done', true)->count() }} <small>/ {{ count($readiness) }} terpenuhi</small></strong></div>
        <div><span>Pendaftaran</span><strong><x-admin.status-badge :status="$batch->status" /></strong></div>
    </div>
    <nav class="ops-tabs" aria-label="Bagian operasional batch">
        @foreach (['overview' => 'Ringkasan', 'participants' => 'Peserta', 'team' => 'Tim pelaksana', 'schedule' => 'Jadwal', 'materials' => 'Materi', 'checklist' => 'Kesiapan', 'history' => 'Riwayat'] as $key => $label)
            <a href="{{ route('admin.operations.show', ['batch' => $batch, 'tab' => $key]) }}" @class(['is-active' => $tab === $key]) @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @if (! $editable)<p class="ops-notice">Batch ini ditutup. Riwayat tetap dapat dilihat.</p>@endif
    @include('admin.operations.'.$tab)
@endsection
