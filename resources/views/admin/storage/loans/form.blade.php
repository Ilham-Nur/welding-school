@extends('layouts.admin')
@section('title', 'Catat Pinjaman Aset')
@section('eyebrow', 'Storage')
@section('heading', 'Catat Pinjaman Aset')

@php
    $chosenAssets = collect(old('asset_ids', $selectedAssets))->map(fn ($id) => (string) $id)->all();
@endphp

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Catat pinjaman aset</h1>
            <p>Pilih satu atau beberapa aset untuk dipinjam oleh karyawan dalam satu transaksi.</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.storage.loans.index') }}">&larr; Kembali</a>
    </section>

    @if($assets->isEmpty())
        <x-ui.alert type="warning" title="Tidak ada aset tersedia">Semua aset sedang tidak aktif atau sudah dipinjam.</x-ui.alert>
    @endif
    @if($employees->isEmpty())
        <x-ui.alert type="warning" title="Belum ada karyawan aktif">Tambahkan akun internal aktif sebelum mencatat peminjaman.</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('admin.storage.loans.store') }}" data-loan-form>
        @csrf
        <section class="admin-panel">
            <header class="admin-panel__header">
                <div>
                    <h2>Serah terima aset</h2>
                    <p>Seluruh aset terpilih tercatat dalam satu nomor pinjaman.</p>
                </div>
            </header>
            <div class="admin-panel__body">
                <div class="admin-form-grid">
                    <label class="ui-field admin-field admin-field--full">
                        <span class="ui-field__label">Aset yang dipinjam <em>Wajib</em></span>
                        <div class="storage-loan-asset-picker" data-loan-asset-picker data-loan-picker-style="select2-multiple">
                            <select name="asset_ids[]" multiple required data-loan-asset-select>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" data-name="{{ $asset->equipment_name }}" @selected(in_array((string) $asset->id, $chosenAssets, true))>{{ $asset->equipment_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <small>Buka dropdown, lalu cari dan pilih satu atau beberapa aset.</small>
                        @error('asset_ids')<span class="ui-field__error">{{ $message }}</span>@enderror
                        @error('asset_ids.*')<span class="ui-field__error">{{ $message }}</span>@enderror
                    </label>

                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Nama karyawan <em>Wajib</em></span>
                        <select name="borrower_user_id" required>
                            <option value="">Pilih karyawan</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) old('borrower_user_id') === (string) $employee->id)>{{ $employee->name }}</option>
                            @endforeach
                        </select>
                        @error('borrower_user_id')<span class="ui-field__error">{{ $message }}</span>@enderror
                    </label>

                    <x-ui.text-input label="Waktu keluar" name="loaned_at" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" required />
                    <x-ui.text-input label="Rencana kembali" name="due_at" type="datetime-local" :value="null" placeholder="Opsional" />

                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Kondisi saat keluar <em>Wajib</em></span>
                        <select name="condition_out" required>
                            @foreach(\App\Models\Asset::CONDITIONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('condition_out', 'good') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="ui-field admin-field admin-field--full">
                        <span class="ui-field__label">Tujuan penggunaan <em>Wajib</em></span>
                        <textarea name="purpose" maxlength="2000" required>{{ old('purpose') }}</textarea>
                    </label>
                    <label class="ui-field admin-field admin-field--full">
                        <span class="ui-field__label">Catatan</span>
                        <textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </div>
        </section>

        <div class="admin-form-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.storage.loans.index') }}">Batal</a>
            <button class="button button--primary admin-button" @disabled($assets->isEmpty() || $employees->isEmpty())>Konfirmasi pinjaman</button>
        </div>
    </form>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/storage.css') }}?v={{ filemtime(public_path('templates/welding-school/storage.css')) }}">
@endpush

@push('scripts')
    @vite('resources/js/storage.js')
@endpush
