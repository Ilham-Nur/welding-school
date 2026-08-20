@extends('layouts.admin')

@php
    $editing = $storageItem->exists;
@endphp

@section('title', $editing ? 'Edit Consumable' : 'Tambah Consumable')
@section('eyebrow', 'Storage')
@section('heading', $editing ? 'Edit Consumable' : 'Tambah Consumable')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $editing ? 'Edit data consumable' : 'Registrasi consumable baru' }}</h1>
            <p>Kode internal dibuat otomatis oleh sistem dan tidak dapat diubah setelah tersimpan.</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.storage-items.index') }}">&larr; Kembali</a>
    </section>

    <form method="POST" action="{{ $editing ? route('admin.storage-items.update', $storageItem) : route('admin.storage-items.store') }}">
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        <div class="storage-item-form-layout">
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Identitas consumable</h2>
                        <p>Master ini hanya untuk barang habis pakai. Jumlah stok dikelola melalui transaksi Storage.</p>
                    </div>
                </header>

                <div class="admin-panel__body">
                    <div class="storage-item-code-preview">
                        <span>KODE INTERNAL</span>
                        <strong>{{ $editing ? $storageItem->code : 'ATP-CNS-######' }}</strong>
                        <small>{{ $editing ? 'Identitas permanen consumable ini.' : 'Nomor urut final dibuat otomatis saat data disimpan.' }}</small>
                    </div>

                    <div class="admin-form-grid">
                        <x-ui.text-input
                            label="Nama barang"
                            name="name"
                            :value="$storageItem->name"
                            placeholder="Contoh: Elektroda E7018 3.2 mm"
                            maxlength="255"
                            required
                        />
                        <x-ui.text-input
                            label="Kategori"
                            name="category"
                            :value="$storageItem->category"
                            placeholder="Contoh: Welding Consumable"
                            maxlength="80"
                            required
                        />
                        <x-ui.text-input
                            label="Satuan"
                            name="unit"
                            :value="$storageItem->unit"
                            placeholder="kg, box, pcs, atau roll"
                            maxlength="30"
                            required
                        />
                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Stok minimum <em>Wajib</em></span>
                            <input type="text" inputmode="decimal" data-number-format data-number-decimals="3" name="minimum_stock" value="{{ format_quantity(old('minimum_stock', $storageItem->minimum_stock ?? 0)) }}" required>
                            @error('minimum_stock')<span class="ui-field__error">{{ $message }}</span>@enderror
                        </label>

                        <label class="storage-check">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $storageItem->exists ? $storageItem->is_active : true))>
                            <span>
                                <strong>Barang aktif</strong>
                                <small>Dapat dipilih dalam transaksi Storage.</small>
                            </span>
                        </label>

                        <label class="ui-field admin-field admin-field--full">
                            <span class="ui-field__label">Catatan / spesifikasi</span>
                            <textarea name="notes" maxlength="2000" placeholder="Merek, ukuran, atau spesifikasi lain">{{ old('notes', $storageItem->notes) }}</textarea>
                        </label>
                    </div>
                </div>
            </section>
        </div>

        <div class="admin-form-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.storage-items.index') }}">Batal</a>
            <button class="button button--primary admin-button" type="submit">
                {{ $editing ? 'Simpan perubahan' : 'Generate kode & simpan' }}
            </button>
        </div>
    </form>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/storage.css') }}?v={{ filemtime(public_path('templates/welding-school/storage.css')) }}">
@endpush

@push('scripts')
    @vite('resources/js/storage.js')
@endpush
