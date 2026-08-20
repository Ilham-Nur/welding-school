@extends('layouts.admin')
@php($editing = $location->exists)
@section('title', $editing ? 'Edit Lokasi' : 'Tambah Lokasi')
@section('eyebrow', 'Master data')
@section('heading', $editing ? 'Edit Lokasi' : 'Tambah Lokasi')
@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $editing ? 'Edit lokasi' : 'Tambah lokasi' }}</h1>
            <p>{{ $editing ? 'Perbarui informasi lokasi atau bagian ini.' : 'Tambahkan area utama seperti Workshop atau Main Store.' }}</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.locations.index') }}">&larr; Kembali</a>
    </section>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Informasi lokasi</h2>
                <p>Bagian seperti Booth 1, Booth 2, dan Booth 3 ditambahkan dari halaman Detail Workshop.</p>
            </div>
        </header>
        <div class="admin-panel__body">
            <form method="POST" action="{{ $editing ? route('admin.locations.update', $location) : route('admin.locations.store') }}">
                @csrf
                @if($editing) @method('PUT') @endif

                <div class="admin-form-grid">
                    <x-ui.text-input
                        wrapper-class="admin-field--full"
                        label="Nama lokasi"
                        name="name"
                        :value="$location->name"
                        placeholder="Contoh: Workshop, Booth 1, atau Main Store"
                        maxlength="120"
                        required
                    />

                    @if($editing && $location->parent)
                        <div class="storage-location-parent admin-field--full">
                            <span>Bagian dari</span>
                            <strong>{{ $location->parent->fullName() }}</strong>
                            <small>Susunan lokasi dikelola melalui halaman detail lokasi induk.</small>
                        </div>
                    @endif

                    <label class="storage-check">
                        <input type="checkbox" name="is_storage" value="1" @checked(old('is_storage', $location->is_storage))>
                        <span><strong>Digunakan sebagai Storage</strong><small>Aktifkan hanya jika lokasi ini dapat memiliki stok consumable.</small></span>
                    </label>
                    <label class="storage-check">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $location->exists ? $location->is_active : true))>
                        <span><strong>Lokasi aktif</strong><small>Lokasi aktif dapat dipilih pada formulir Asset dan Storage.</small></span>
                    </label>
                    <label class="ui-field admin-field admin-field--full">
                        <span class="ui-field__label">Catatan</span>
                        <textarea name="notes" maxlength="1000">{{ old('notes', $location->notes) }}</textarea>
                    </label>
                </div>

                <div class="admin-form-actions">
                    <a class="button button--outline admin-button" href="{{ route('admin.locations.index') }}">Batal</a>
                    <button class="button button--primary admin-button">Simpan lokasi</button>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/storage.css') }}?v={{ filemtime(public_path('templates/welding-school/storage.css')) }}">
@endpush
