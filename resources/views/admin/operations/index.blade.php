@extends('layouts.admin')
@section('title', 'Operasional Batch')
@section('eyebrow', 'Pelaksanaan pelatihan')
@section('heading', 'Operasional Batch')
@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/operations.css') }}?v={{ filemtime(public_path('templates/welding-school/operations.css')) }}">
@endpush
@section('content')
    <section class="admin-page-heading">
        <div><h1>Operasional batch</h1><p>Siapkan tim, jadwal, dan kebutuhan pelatihan dalam satu tempat.</p></div>
        @can('batches.manage')<a class="button button--primary admin-button" href="{{ route('admin.batches.create') }}">+ Tambah batch</a>@endcan
    </section>
    <form class="admin-filter ops-filter" method="GET">
        <label class="admin-field"><span>Cari batch</span><input name="search" value="{{ request('search') }}" placeholder="Nama atau kode batch" type="search"></label>
        <label class="admin-field"><span>Status operasional</span><select name="status"><option value="">Semua status</option>@foreach (\App\Models\TrainingBatch::OPERATIONAL_STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></label>
        <div class="admin-actions"><button class="button button--primary admin-button">Terapkan</button><a class="button button--outline admin-button" href="{{ route('admin.operations.index') }}">Reset</a></div>
    </form>
    <section class="admin-panel">
        <header class="admin-panel__header"><div><h2>Batch pelatihan</h2><p>{{ $batches->total() }} batch dapat Anda akses.</p></div></header>
        @if ($batches->isEmpty())
            <div class="admin-empty"><h2>Belum ada batch</h2><p>Batch akan muncul setelah dibuat atau setelah Anda ditugaskan sebagai tim pelaksana.</p></div>
        @else
            <x-ui.table class="admin-table-wrap"><thead><tr><th>Batch & program</th><th>Periode</th><th>Peserta aktif lunas</th><th>Operasional</th><th></th></tr></thead><tbody>
                @foreach ($batches as $batch)
                    <tr><td><strong>{{ $batch->name }}</strong><small>{{ $batch->code }} · {{ $batch->trainingProgram->title }}</small></td><td>{{ $batch->start_date->format('d M Y') }}<small>s.d. {{ $batch->end_date?->format('d M Y') ?? 'Belum ditentukan' }}</small></td><td>{{ $batch->active_paid_enrollments_count }} / {{ $batch->capacity }}</td><td><span class="ops-badge ops-badge--{{ $batch->operational_status }}">{{ \App\Models\TrainingBatch::OPERATIONAL_STATUSES[$batch->operational_status] ?? $batch->operational_status }}</span></td><td><a class="button button--outline admin-button" href="{{ route('admin.operations.show', $batch) }}">Buka batch</a></td></tr>
                @endforeach
            </tbody></x-ui.table>
            <x-ui.pagination :paginator="$batches" />
        @endif
    </section>
@endsection
