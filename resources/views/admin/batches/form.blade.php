@extends('layouts.admin')

@php($editing = $batch->exists)

@section('title', $editing ? 'Edit Batch' : 'Tambah Batch')
@section('eyebrow', 'Jadwal pelatihan')
@section('heading', $editing ? 'Edit Batch' : 'Tambah Batch')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $editing ? 'Edit batch pelatihan' : 'Tambah batch pelatihan' }}</h1>
            <p>Batch terbentuk dari RAB yang disetujui. Lengkapi jadwal dan buka pendaftaran saat siap.</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.batches.index') }}">← Kembali</a>
    </section>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Informasi batch</h2>
                <p>Pastikan tanggal pendaftaran tidak melewati tanggal mulai pelatihan.</p>
            </div>
        </header>
        <div class="admin-panel__body">
            @if($batch->training_budget_id)
                <p>RAB: {{ $batch->trainingBudget->name }} · {{ \App\Models\TrainingBudget::STATUSES[$batch->trainingBudget->status] }}.
                Harga final: {{ $batch->trainingBudget->approved_price !== null ? 'Rp '.number_format($batch->trainingBudget->approved_price, 0, ',', '.') : 'Belum disetujui' }}.</p>
            @elseif($editing && ! $batch->budget_required)
                <p>Batch lama masih memakai harga program dan biaya administrasi lama sampai dialihkan ke RAB.</p>
            @endif
            <form method="POST" action="{{ $editing ? route('admin.batches.update', $batch) : route('admin.batches.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="admin-form-grid">
                    <label class="ui-field admin-field admin-field--full">
                        <span class="ui-field__label">Program pelatihan <em>Wajib</em></span>
                        <select name="training_program_id" required>
                            <option value="">Pilih program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected((string) old('training_program_id', $batch->training_program_id) === (string) $program->id)>
                                    {{ $program->code }} · {{ $program->title }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <x-ui.text-input
                        label="Kode batch"
                        name="code"
                        :value="$batch->code"
                        placeholder="SMAW-2608"
                        maxlength="30"
                        required
                    />
                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Status pendaftaran <em>Wajib</em></span>
                        <select name="status" required>
                            <option value="draft" @selected(old('status', $batch->status ?? 'draft') === 'draft')>Draft</option>
                            <option value="open" @selected(old('status', $batch->status) === 'open')>Dibuka</option>
                            <option value="closed" @selected(old('status', $batch->status) === 'closed')>Ditutup</option>
                            @if ($batch->status === 'completed')
                                <option value="completed" @selected(old('status', $batch->status) === 'completed')>Selesai (data lama)</option>
                            @endif
                        </select>
                    </label>
                    <x-ui.text-input
                        wrapper-class="ui-field--full"
                        label="Nama batch"
                        name="name"
                        :value="$batch->name"
                        placeholder="Batch Agustus 2026"
                        required
                    />
                    <x-ui.text-input
                        label="Batas pendaftaran"
                        name="registration_deadline"
                        type="date"
                        :value="$batch->registration_deadline?->format('Y-m-d')"
                    />
                    <x-ui.text-input
                        label="Kapasitas peserta"
                        name="capacity"
                        type="number"
                        :value="$batch->capacity"
                        min="1"
                        required
                    />
                    <x-ui.text-input
                        label="Tanggal mulai"
                        name="start_date"
                        type="date"
                        :value="$batch->start_date?->format('Y-m-d')"
                        required
                    />
                    <x-ui.text-input
                        label="Tanggal selesai"
                        name="end_date"
                        type="date"
                        :value="$batch->end_date?->format('Y-m-d')"
                    />
                </div>

                <div class="admin-form-actions">
                    <a class="button button--outline admin-button" href="{{ route('admin.batches.index') }}">Batal</a>
                    <button class="button button--primary admin-button" type="submit">{{ $editing ? 'Update batch' : 'Tambah batch' }}</button>
                </div>
            </form>
        </div>
    </section>
@endsection
