@extends('layouts.admin')

@php
    $editing = $storageItem->exists;
    $selectedUnitId = (string) old('storage_unit_id', $storageItem->storage_unit_id ?? '');
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
                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Satuan <em>Wajib</em></span>
                            <div class="storage-unit-control">
                                <select name="storage_unit_id" required data-storage-unit-select @disabled($unitLocked)>
                                    <option value="">Pilih satuan</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" @selected($selectedUnitId === (string) $unit->id)>
                                            {{ $unit->label() }}{{ ! $unit->is_active ? ' (nonaktif)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @unless ($unitLocked)
                                    <button class="button button--outline admin-button" type="button" data-modal-open="create-storage-unit">+ Tambah</button>
                                @endunless
                            </div>
                            @if ($unitLocked)
                                <input type="hidden" name="storage_unit_id" value="{{ $storageItem->storage_unit_id }}">
                                <small>Satuan dikunci karena consumable sudah memiliki stok atau riwayat transaksi.</small>
                            @else
                                <small>Pilih dari master satuan atau tambahkan satuan baru langsung dari halaman ini.</small>
                            @endif
                            @error('storage_unit_id')<span class="ui-field__error">{{ $message }}</span>@enderror
                        </label>
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

    @unless ($unitLocked)
        <x-ui.modal id="create-storage-unit" title="Tambah satuan consumable" description="Satuan baru akan langsung dipilih pada registrasi ini.">
            <form id="storage-unit-inline-form" action="{{ route('admin.storage.units.store') }}" method="POST" data-storage-unit-create-form data-no-loading>
                @csrf
                <div class="storage-unit-modal-form">
                    <x-ui.text-input label="Simbol satuan" name="unit_symbol" placeholder="Contoh: kg, pcs, box" maxlength="30" required />
                    <x-ui.text-input label="Nama lengkap (opsional)" name="unit_name" placeholder="Contoh: Kilogram" maxlength="80" />
                    <p class="ui-field__error storage-unit-create-error" data-storage-unit-create-error hidden></p>
                </div>
            </form>

            <x-slot:footer>
                <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                <button class="button button--primary admin-button" type="submit" form="storage-unit-inline-form" data-storage-unit-create-submit>Simpan & pilih</button>
            </x-slot:footer>
        </x-ui.modal>
    @endunless
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/storage.css') }}?v={{ filemtime(public_path('templates/welding-school/storage.css')) }}">
@endpush

@push('scripts')
    @vite('resources/js/storage.js')
@endpush
