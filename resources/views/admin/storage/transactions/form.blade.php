@extends('layouts.admin')

@php
    $receipt = $type === 'receipt';
    $oldLines = old('lines', [[
        'storage_item_id' => '',
        'quantity' => '',
        'notes' => '',
    ]]);
@endphp

@section('title', $receipt ? 'Terima Barang' : 'Keluarkan Barang')
@section('eyebrow', 'Storage')
@section('heading', $receipt ? 'Terima Barang' : 'Keluarkan Barang')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $receipt ? 'Catat penerimaan barang' : 'Catat pengeluaran barang' }}</h1>
            <p>{{ $receipt ? 'Stok bertambah setelah formulir disimpan.' : 'Catat saat barang keluar dari Storage; tidak perlu merinci pemakaian per siswa.' }}</p>
        </div>
        <a class="button button--outline admin-button" href="{{ $receipt ? route('admin.storage.receipts.index') : route('admin.storage.issues.index') }}">&larr; Kembali</a>
    </section>

    @if($locations->isEmpty())
        <x-ui.alert type="warning" title="Belum ada lokasi Storage">
            Buat atau edit Master Lokasi dan aktifkan opsi "Dapat menyimpan stok consumable" sebelum mencatat transaksi.
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ $receipt ? route('admin.storage.receipts.store') : route('admin.storage.issues.store') }}" data-storage-transaction-form>
        @csrf

        <section class="admin-panel">
            <header class="admin-panel__header">
                <div>
                    <h2>Informasi transaksi</h2>
                    <p>Tanggal, lokasi asal, dan tujuan menjadi dasar pelaporan.</p>
                </div>
            </header>
            <div class="admin-panel__body">
                <div class="admin-form-grid">
                    <x-ui.text-input label="Tanggal transaksi" name="transaction_date" type="date" :value="now()->format('Y-m-d')" required />

                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Lokasi Storage <em>Wajib</em></span>
                        <select name="location_id" required>
                            <option value="">Pilih lokasi</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>{{ $location->fullName() }}</option>
                            @endforeach
                        </select>
                    </label>

                    @if($receipt)
                        <x-ui.text-input label="Supplier / sumber" name="supplier" :value="null" placeholder="Nama supplier atau sumber barang" />
                    @else
                        <x-ui.text-input label="Tujuan penggunaan" name="purpose" :value="null" placeholder="Contoh: Praktik SMAW Batch Agustus" required />
                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Batch pelatihan</span>
                            <select name="training_batch_id">
                                <option value="">Tidak terkait batch</option>
                                @foreach($batches as $batch)
                                    <option value="{{ $batch->id }}" @selected((string) old('training_batch_id') === (string) $batch->id)>{{ $batch->code }} · {{ $batch->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    <x-ui.text-input label="Nomor referensi / PO" name="reference" :value="null" placeholder="Opsional" />

                    <label class="ui-field admin-field admin-field--full">
                        <span class="ui-field__label">Catatan</span>
                        <textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </div>
        </section>

        <section class="admin-panel storage-lines-panel">
            <header class="admin-panel__header">
                <div>
                    <h2>Daftar barang</h2>
                    <p>Cari berdasarkan kode, nama, kategori, merek, atau spesifikasi consumable.</p>
                </div>
                <button class="button button--outline admin-button" type="button" data-storage-line-add>+ Tambah baris</button>
            </header>
            <div class="admin-panel__body">
                @if($errors->has('lines') || $errors->has('lines.*'))
                    <p class="ui-field__error">{{ $errors->first('lines') ?: $errors->first('lines.*') }}</p>
                @endif

                <div class="storage-lines" data-storage-lines>
                    @foreach($oldLines as $index => $line)
                        <div class="storage-line" data-storage-line>
                            @include('admin.storage.transactions.item-picker', [
                                'name' => "lines[{$index}][storage_item_id]",
                                'selected' => $line['storage_item_id'] ?? '',
                            ])

                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Jumlah <em>Wajib</em></span>
                                <input type="text" inputmode="decimal" data-number-format data-number-decimals="3" name="lines[{{ $index }}][quantity]" value="{{ filled($line['quantity'] ?? null) ? format_quantity($line['quantity']) : '' }}" required>
                            </label>

                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Catatan baris</span>
                                <input type="text" name="lines[{{ $index }}][notes]" value="{{ $line['notes'] ?? '' }}" maxlength="255">
                            </label>

                            <button class="storage-line__remove" type="button" data-storage-line-remove aria-label="Hapus baris">
                                <x-ui.icon name="trash" size="16" />
                            </button>
                        </div>
                    @endforeach
                </div>

                <template data-storage-line-template>
                    <div class="storage-line" data-storage-line>
                        @include('admin.storage.transactions.item-picker', [
                            'name' => 'lines[__INDEX__][storage_item_id]',
                            'selected' => '',
                        ])

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Jumlah <em>Wajib</em></span>
                            <input type="text" inputmode="decimal" data-number-format data-number-decimals="3" name="lines[__INDEX__][quantity]" required>
                        </label>

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Catatan baris</span>
                            <input type="text" name="lines[__INDEX__][notes]" maxlength="255">
                        </label>

                        <button class="storage-line__remove" type="button" data-storage-line-remove aria-label="Hapus baris">
                            <x-ui.icon name="trash" size="16" />
                        </button>
                    </div>
                </template>
            </div>
        </section>

        <div class="admin-form-actions">
            <a class="button button--outline admin-button" href="{{ $receipt ? route('admin.storage.receipts.index') : route('admin.storage.issues.index') }}">Batal</a>
            <button class="button button--primary admin-button" @disabled($locations->isEmpty() || $items->isEmpty())>
                {{ $receipt ? 'Konfirmasi penerimaan' : 'Konfirmasi pengeluaran' }}
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
