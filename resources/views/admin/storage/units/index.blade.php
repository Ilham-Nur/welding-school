@extends('layouts.admin')

@section('title', 'Satuan Consumable')
@section('eyebrow', 'Storage')
@section('heading', 'Satuan Consumable')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Master satuan consumable</h1>
            <p>Kelola satuan baku yang tersedia pada registrasi consumable.</p>
        </div>
        <div class="admin-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.storage-items.index') }}">&larr; Stok consumable</a>
            <button class="button button--primary admin-button" type="button" data-modal-open="create-storage-unit">+ Tambah satuan</button>
        </div>
    </section>

    <form class="admin-filter" method="GET">
        <label class="admin-field"><span>Cari satuan</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Simbol atau nama satuan"></label>
        <label class="admin-field"><span>Status</span><select name="status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></label>
        <div class="admin-actions"><button class="button button--primary admin-button">Terapkan</button><a class="button button--outline admin-button" href="{{ route('admin.storage.units.index') }}">Reset</a></div>
    </form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div><h2>Daftar satuan</h2><p>{{ $units->total() }} satuan ditemukan. Satuan nonaktif tetap tersimpan pada consumable lama.</p></div>
        </header>

        @if ($units->isEmpty())
            <div class="admin-empty"><h2>Belum ada satuan</h2><p>Tambahkan satuan pertama agar registrasi consumable dapat digunakan.</p></div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead><tr><th>Simbol</th><th>Nama lengkap</th><th>Dipakai</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($units as $unit)
                        <tr>
                            <td><strong>{{ $unit->symbol }}</strong></td>
                            <td>{{ $unit->name ?: '-' }}</td>
                            <td><strong>{{ number_format($unit->items_count) }}</strong><small>consumable</small></td>
                            <td><x-admin.status-badge :status="$unit->is_active ? 'active' : 'inactive'">{{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}</x-admin.status-badge></td>
                            <td>
                                <div class="admin-action-group">
                                    <button class="admin-action-button admin-action-button--edit" type="button" data-modal-open="edit-storage-unit-{{ $unit->id }}"><x-ui.icon name="edit" size="14" /> Edit</button>
                                    <form method="POST" action="{{ route('admin.storage.units.toggle', $unit) }}" data-confirm-dialog="toggle-storage-unit-{{ $unit->id }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="admin-action-button {{ $unit->is_active ? 'admin-action-button--delete' : 'admin-action-button--view' }}" type="submit">
                                            {{ $unit->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
            <x-ui.pagination :paginator="$units" />
        @endif
    </section>

    <x-ui.modal id="create-storage-unit" title="Tambah satuan consumable" description="Satuan aktif akan tersedia pada registrasi consumable baru.">
        <form id="create-storage-unit-form" method="POST" action="{{ route('admin.storage.units.store') }}">
            @csrf
            <div class="storage-unit-modal-form">
                <x-ui.text-input label="Simbol satuan" name="symbol" :value="old('symbol')" placeholder="Contoh: kg, pcs, box" maxlength="30" required />
                <x-ui.text-input label="Nama lengkap (opsional)" name="name" :value="old('name')" placeholder="Contoh: Kilogram" maxlength="80" />
            </div>
        </form>
        <x-slot:footer>
            <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
            <button class="button button--primary admin-button" type="submit" form="create-storage-unit-form">Simpan satuan</button>
        </x-slot:footer>
    </x-ui.modal>

    @foreach ($units as $unit)
        <x-ui.modal id="edit-storage-unit-{{ $unit->id }}" title="Edit satuan {{ $unit->symbol }}" description="Simbol dikunci setelah satuan digunakan oleh consumable.">
            <form id="edit-storage-unit-form-{{ $unit->id }}" method="POST" action="{{ route('admin.storage.units.update', $unit) }}">
                @csrf
                @method('PUT')
                <div class="storage-unit-modal-form">
                    <x-ui.text-input label="Simbol satuan" name="symbol" :value="$unit->symbol" maxlength="30" :disabled="$unit->items_count > 0" required />
                    @if ($unit->items_count > 0)<input type="hidden" name="symbol" value="{{ $unit->symbol }}">@endif
                    <x-ui.text-input label="Nama lengkap (opsional)" name="name" :value="$unit->name" placeholder="Contoh: Kilogram" maxlength="80" />
                </div>
            </form>
            <x-slot:footer>
                <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                <button class="button button--primary admin-button" type="submit" form="edit-storage-unit-form-{{ $unit->id }}">Simpan perubahan</button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.confirmation
            :id="'toggle-storage-unit-'.$unit->id"
            :title="$unit->is_active ? 'Nonaktifkan satuan?' : 'Aktifkan satuan?'"
            :confirm-label="$unit->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan'"
            :tone="$unit->is_active ? 'danger' : 'success'"
        >
            <strong>{{ $unit->label() }}</strong>
            {{ $unit->is_active
                ? 'tidak akan muncul pada registrasi baru. Consumable dan transaksi lama tetap aman.'
                : 'akan tersedia kembali pada registrasi consumable baru.' }}
        </x-ui.confirmation>
    @endforeach
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/storage.css') }}?v={{ filemtime(public_path('templates/welding-school/storage.css')) }}">
@endpush
