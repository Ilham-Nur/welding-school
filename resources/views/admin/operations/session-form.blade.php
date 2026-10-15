@extends('layouts.admin')
@section('title', $session->exists ? 'Edit Sesi Pelatihan' : 'Tambah Sesi Pelatihan')
@section('eyebrow', $batch->code)
@section('heading', 'Jadwal Sesi Pelatihan')
@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/operations.css') }}?v={{ filemtime(public_path('templates/welding-school/operations.css')) }}">
@endpush
@section('content')
    <section class="admin-page-heading"><div><h1>{{ $session->exists ? 'Edit sesi' : 'Tambah sesi' }}</h1><p>{{ $batch->name }} · Zona waktu {{ config('app.timezone') }}</p></div><a class="button button--outline admin-button" href="{{ route('admin.operations.show', ['batch' => $batch, 'tab' => 'schedule']) }}">Kembali ke jadwal</a></section>
    <section class="admin-panel"><div class="admin-panel__body">
        @if ($trainers->isEmpty() || $locations->isEmpty())<p class="ops-notice">Tugaskan Trainer dan pastikan ada lokasi aktif sebelum menyimpan sesi.</p>@endif
        <form method="POST" action="{{ $session->exists ? route('admin.operations.sessions.update', [$batch, $session]) : route('admin.operations.sessions.store', $batch) }}" class="ops-stack">@csrf @if ($session->exists) @method('PUT') @endif
            <div class="admin-form-grid">
                <label class="admin-field"><span>Nama sesi</span><input name="title" required maxlength="255" value="{{ old('title', $session->title) }}"></label>
                <label class="admin-field"><span>Jenis sesi</span><select name="type" required>@foreach (\App\Models\BatchSession::TYPES as $key => $label)<option value="{{ $key }}" @selected(old('type', $session->type) === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="admin-field"><span>Trainer</span><select name="trainer_id" required><option value="">Pilih Trainer</option>@foreach ($trainers as $assignment)<option value="{{ $assignment->user_id }}" @selected((string) old('trainer_id', $session->trainer_id) === (string) $assignment->user_id)>{{ $assignment->user->name }}</option>@endforeach</select></label>
                <label class="admin-field"><span>Lokasi sesi (ruang / workshop)</span><select name="location_id" required><option value="">Pilih lokasi sesi</option>@foreach ($locations as $location)<option value="{{ $location->id }}" @selected((string) old('location_id', $session->location_id) === (string) $location->id)>{{ $location->fullName() }}</option>@endforeach</select></label>
                <label class="admin-field"><span>Mulai</span><input name="starts_at" type="datetime-local" required value="{{ old('starts_at', $session->starts_at?->format('Y-m-d\TH:i')) }}"><small>Pilih tanggal dan jam pelaksanaan dalam WIB.</small></label>
                <label class="admin-field"><span>Selesai</span><input name="ends_at" type="datetime-local" required value="{{ old('ends_at', $session->ends_at?->format('Y-m-d\TH:i')) }}"><small>Harus setelah waktu mulai dan berada dalam periode batch.</small></label>
                <label class="admin-field"><span>Status</span><select name="status" required>@foreach (\App\Models\BatchSession::STATUSES as $key => $label)<option value="{{ $key }}" @selected(old('status', $session->status) === $key)>{{ $label }}</option>@endforeach</select></label>
            </div>
            <label class="admin-field"><span>Catatan sesi</span><textarea name="notes" rows="3" maxlength="4000">{{ old('notes', $session->notes) }}</textarea></label>
            <label class="admin-field"><span>Alasan / catatan perubahan {{ $session->exists ? '(wajib)' : '(opsional)' }}</span><textarea name="reason" rows="2" maxlength="1000" @required($session->exists)>{{ old('reason') }}</textarea></label>
            <button class="button button--primary admin-button" @disabled($trainers->isEmpty() || $locations->isEmpty())>Simpan sesi</button>
        </form>
    </div></section>
@endsection
