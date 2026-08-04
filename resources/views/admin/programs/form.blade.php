@extends('layouts.admin')

@php($editing = $program->exists)

@section('title', $editing ? 'Edit Program' : 'Tambah Program')
@section('eyebrow', 'Katalog pelatihan')
@section('heading', $editing ? 'Edit Program' : 'Tambah Program')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $editing ? 'Edit program pelatihan' : 'Tambah program pelatihan' }}</h1>
            <p>Lengkapi informasi utama yang akan digunakan pada katalog dan proses pendaftaran.</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.programs.index') }}">← Kembali</a>
    </section>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Informasi program</h2>
                <p>Field bertanda wajib harus dilengkapi sebelum disimpan.</p>
            </div>
        </header>
        <div class="admin-panel__body">
            <form method="POST" action="{{ $editing ? route('admin.programs.update', $program) : route('admin.programs.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="admin-form-grid">
                    <x-ui.text-input
                        label="Kode program"
                        name="code"
                        :value="$program->code"
                        placeholder="SMAW-3G"
                        hint="Kode harus unik dan otomatis disimpan dalam huruf kapital."
                        maxlength="20"
                        required
                    />
                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Status program <em>Wajib</em></span>
                        <select name="status" required>
                            <option value="draft" @selected(old('status', $program->status ?? 'draft') === 'draft')>Draft</option>
                            <option value="active" @selected(old('status', $program->status) === 'active')>Aktif</option>
                            <option value="closed" @selected(old('status', $program->status) === 'closed')>Ditutup</option>
                        </select>
                    </label>
                    <x-ui.text-input
                        wrapper-class="ui-field--full"
                        label="Nama program"
                        name="title"
                        :value="$program->title"
                        placeholder="SMAW Welder 3G"
                        required
                    />
                    <x-ui.text-input
                        wrapper-class="ui-field--full"
                        label="Kategori"
                        name="category"
                        :value="$program->category"
                        placeholder="Shielded Metal Arc Welding"
                        required
                    />
                    <x-ui.text-input
                        label="Durasi pelatihan (jam)"
                        name="duration_hours"
                        type="number"
                        :value="$program->duration_hours"
                        min="1"
                        required
                    />
                    <x-ui.text-input
                        label="Biaya program (Rp)"
                        name="price"
                        type="number"
                        :value="$program->price"
                        min="0"
                        step="1000"
                        required
                    />
                    <x-ui.text-input
                        label="Tanggal mulai umum"
                        name="start_date"
                        type="date"
                        :value="$program->start_date?->format('Y-m-d')"
                        hint="Opsional. Jadwal rinci tetap dikelola melalui batch."
                    />
                </div>

                <div class="admin-form-actions">
                    <a class="button button--outline admin-button" href="{{ route('admin.programs.index') }}">Batal</a>
                    <button class="button button--primary admin-button" type="submit">{{ $editing ? 'Update program' : 'Tambah program' }}</button>
                </div>
            </form>
        </div>
    </section>
@endsection
