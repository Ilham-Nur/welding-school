@extends('layouts.admin')

@php
    $editing = $assetKind->exists;
    $selectedCategory = old('category_code', $selectedCategory);
    $lastNumber = (int) ($assetKind->numberSequence?->last_number ?? 0);
    $listParameters = [];
    if (request()->boolean('from_list')) {
        $listParameters = array_filter([
            'search' => request()->string('redirect_search')->toString(),
            'category' => request()->string('redirect_category')->toString(),
            'status' => request()->string('redirect_status')->toString(),
            'page' => request()->integer('redirect_page') > 1 ? request()->integer('redirect_page') : null,
        ], fn ($value) => $value !== null && $value !== '');
    }
    $backUrl = ! $editing && request('return_to') === 'asset-create'
        ? route('admin.assets.create', ['category_code' => $selectedCategory])
        : route('admin.asset-kinds.index', request()->boolean('from_list') ? $listParameters : ['category' => $selectedCategory]);
@endphp

@section('title', $editing ? 'Edit Jenis Aset' : 'Tambah Jenis Aset')
@section('eyebrow', 'Manajemen Aset')
@section('heading', $editing ? 'Edit Jenis Aset' : 'Tambah Jenis Aset')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $editing ? 'Edit jenis aset' : 'Tambah jenis aset baru' }}</h1>
            <p>{{ $editing
                ? 'Perbarui nama jenis aset tanpa mengubah identitas yang sudah digunakan.'
                : 'Tambahkan jenis baru untuk menentukan format Asset ID dan urutan nomornya.' }}</p>
        </div>
        <a class="button button--outline admin-button" href="{{ $backUrl }}">← Kembali</a>
    </section>

    <section class="asset-kind-master-layout">
        <section class="admin-panel" id="asset-kind-form">
            <header class="admin-panel__header">
                <div>
                    <h2>{{ $editing ? $assetKind->name.' | '.$assetKind->code : 'Informasi jenis aset' }}</h2>
                    <p>{{ $editing
                        ? 'Nama dapat diperbarui. Kode hanya dapat diubah sebelum pernah digunakan.'
                        : 'Jenis baru akan langsung tersedia pada pilihan registrasi aset.' }}</p>
                </div>
            </header>
            <div class="admin-panel__body">
                <form
                    class="asset-kind-master-form"
                    method="POST"
                    action="{{ $editing ? route('admin.asset-kinds.update', $assetKind) : route('admin.asset-kinds.store') }}"
                    data-asset-kind-master-form
                >
                    @csrf
                    @if ($editing)
                        @method('PUT')
                    @endif
                    @if (request()->boolean('from_list'))
                        <input type="hidden" name="from_list" value="1">
                        @foreach (['search', 'category', 'status', 'page'] as $filter)
                            @if (array_key_exists($filter, $listParameters))
                                <input type="hidden" name="redirect_{{ $filter }}" value="{{ $listParameters[$filter] }}">
                            @endif
                        @endforeach
                    @elseif ($editing)
                        <input type="hidden" name="redirect_category" value="{{ $assetKind->category_code }}">
                    @endif
                    @if (! $editing && request('return_to'))
                        <input type="hidden" name="return_to" value="{{ request('return_to') }}">
                    @endif

                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Kategori <em>Wajib</em></span>
                        @if ($editing)
                            <input value="{{ $assetKind->category_code }} | {{ $assetKind->categoryLabel() }}" disabled>
                            <input type="hidden" name="category_code" value="{{ $assetKind->category_code }}" data-asset-kind-master-category>
                            <small>Kategori tidak dapat dipindahkan agar pengelompokan kode tetap konsisten.</small>
                        @else
                            <select name="category_code" required data-asset-kind-master-category>
                                @foreach ($categories as $code => $label)
                                    <option value="{{ $code }}" @selected($selectedCategory === $code)>{{ $code }} | {{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('category_code')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>

                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Nama jenis aset <em>Wajib</em></span>
                        <input
                            name="name"
                            value="{{ old('name', $assetKind->name) }}"
                            maxlength="120"
                            placeholder="Contoh: Cutting"
                            autocomplete="off"
                            required
                            data-asset-kind-master-name
                        >
                        @error('name')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>

                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Kode jenis <em>Wajib</em></span>
                        <input
                            name="code"
                            value="{{ old('code', $assetKind->code) }}"
                            minlength="3"
                            maxlength="3"
                            pattern="[A-Za-z]{3}"
                            placeholder="CUT"
                            autocomplete="off"
                            required
                            @readonly($identityLocked)
                            data-asset-kind-master-code
                        >
                        @if ($identityLocked)
                            <small>Kode dikunci karena jenis ini sudah pernah menghasilkan nomor aset.</small>
                        @else
                            <small>Tepat 3 huruf A–Z. Kode masih dapat diubah selama belum pernah digunakan.</small>
                        @endif
                        @error('code')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>

                    <div class="asset-kind-master-example">
                        <span>Contoh Asset ID berikutnya</span>
                        <strong data-asset-kind-master-example>
                            {{ $editing
                                ? $assetKind->codeFor($lastNumber + 1)
                                : 'ATP-'.$selectedCategory.'-'.(old('code') ?: '___').'-001' }}
                        </strong>
                    </div>

                    <div class="admin-form-actions asset-kind-master-form__actions">
                        <a class="button button--outline admin-button" href="{{ $backUrl }}">Batal</a>
                        <button class="button button--primary admin-button" type="submit">
                            {{ $editing ? 'Simpan perubahan' : 'Tambah jenis aset' }}
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <aside class="asset-kind-master-policy">
            <h2>Aturan identitas</h2>
            <ul>
                <li><strong>Nama</strong> dapat diperbaiki kapan saja.</li>
                <li><strong>Kode</strong> dikunci setelah pernah menghasilkan nomor.</li>
                <li><strong>Nonaktif</strong> menyembunyikan jenis dari registrasi baru tanpa mengganggu aset lama.</li>
                <li><strong>Hapus</strong> hanya tersedia bila jenis belum pernah digunakan.</li>
            </ul>
        </aside>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/assets.js')
@endpush
