@extends('layouts.admin')

@section('title', 'Master Data Jabatan & Posisi')
@section('eyebrow', 'Kepegawaian & SDM')
@section('heading', 'Master Jabatan Karyawan')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Master Jabatan / Posisi</h1>
            <p>Kelola daftar jabatan dan posisi resmi untuk penempatan karyawan pada sistem.</p>
        </div>
        <div class="admin-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.employees.index') }}">← Kembali ke Data Karyawan</a>
            @can('employees.manage')
                <button class="button button--primary admin-button" type="button" data-modal-open="create-position-modal">
                    + Tambah Jabatan Baru
                </button>
            @endcan
        </div>
    </section>

    <!-- Filter & Pencarian -->
    <section class="admin-panel" style="margin-bottom: 24px">
        <div class="admin-panel__body">
            <form method="GET" action="{{ route('admin.employee-positions.index') }}" class="admin-filter">
                <label class="ui-field admin-field" style="margin: 0">
                    <span class="ui-field__label">Cari Jabatan</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Nama jabatan, kode, atau deskripsi..."
                    >
                </label>

                <label class="ui-field admin-field" style="margin: 0">
                    <span class="ui-field__label">Status</span>
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="active" @selected(request('status') === 'active')>Aktif Saja</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Non-aktif Saja</option>
                    </select>
                </label>

                <div style="display: flex; gap: 8px; align-items: flex-end;">
                    <button class="button button--primary admin-button" type="submit" style="height: 44px">Filter</button>
                    @if (request()->hasAny(['search', 'status']))
                        <a class="button button--outline admin-button" href="{{ route('admin.employee-positions.index') }}" style="height: 44px">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <!-- Tabel Daftar Jabatan -->
    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar Jabatan Resmi ({{ $positions->total() }})</h2>
                <p>Jabatan yang aktif akan muncul pada opsi pilihan (*selection*) formulir data karyawan.</p>
            </div>
        </header>

        @if ($positions->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true"><x-ui.icon name="users" size="24" /></span>
                <h3>Belum ada master data jabatan</h3>
                <p>Tambahkan jabatan atau posisi resmi untuk memulai pengelompokan karyawan.</p>
                @can('employees.manage')
                    <button class="button button--primary admin-button" type="button" data-modal-open="create-position-modal" style="margin-top: 12px">
                        + Tambah Jabatan Pertama
                    </button>
                @endcan
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead>
                    <tr>
                        <th style="width: 60px">Urutan</th>
                        <th>Nama Jabatan</th>
                        <th>Kode</th>
                        <th>Deskripsi</th>
                        <th>Jumlah Karyawan</th>
                        <th>Status</th>
                        @can('employees.manage')
                            <th style="width: 140px">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach ($positions as $pos)
                        <tr>
                            <td>
                                <span style="font-weight: 600; color: #64748b">{{ $pos->display_order ?: $loop->iteration }}</span>
                            </td>
                            <td>
                                <strong style="color: #0f172a; font-size: 14px">{{ $pos->name }}</strong>
                            </td>
                            <td>
                                @if ($pos->code)
                                    <span class="admin-badge admin-badge--neutral" style="font-size: 12px; font-weight: 600; font-family: monospace;">
                                        {{ $pos->code }}
                                    </span>
                                @else
                                    <span style="color: #94a3b8">-</span>
                                @endif
                            </td>
                            <td>
                                <span style="color: #475569; font-size: 13px">{{ $pos->description ?: '-' }}</span>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge--neutral" style="font-size: 12px; background: #e0f2fe; color: #0369a1;">
                                    {{ $pos->employees_count }} Karyawan
                                </span>
                            </td>
                            <td>
                                @if ($pos->is_active)
                                    <x-admin.status-badge status="published">Aktif</x-admin.status-badge>
                                @else
                                    <x-admin.status-badge status="inactive">Nonaktif</x-admin.status-badge>
                                @endif
                            </td>
                            @can('employees.manage')
                                <td>
                                    <div class="admin-action-group">
                                        <button
                                            class="admin-action-button admin-action-button--edit"
                                            type="button"
                                            data-modal-open="edit-position-{{ $pos->id }}"
                                            title="Edit Jabatan"
                                        >
                                            <x-ui.icon name="edit" size="13" /> Edit
                                        </button>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.employee-positions.destroy', $pos) }}"
                                            onsubmit="return confirm('Hapus jabatan {{ $pos->name }}?')"
                                            style="display: inline;"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                class="admin-action-button admin-action-button--delete"
                                                type="submit"
                                                title="Hapus Jabatan"
                                            >
                                                <x-ui.icon name="trash" size="13" /> Hapus
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Modal Edit Position -->
                                    <x-ui.modal
                                        id="edit-position-{{ $pos->id }}"
                                        title="Edit Jabatan: {{ $pos->name }}"
                                        description="Perbarui informasi master data jabatan."
                                    >
                                        <form method="POST" action="{{ route('admin.employee-positions.update', $pos) }}" id="form-edit-pos-{{ $pos->id }}">
                                            @csrf
                                            @method('PUT')
                                            <div style="display: grid; gap: 14px">
                                                <x-ui.text-input
                                                    label="Nama Jabatan / Posisi"
                                                    name="name"
                                                    :value="old('name', $pos->name)"
                                                    placeholder="Contoh: Instruktur Welder"
                                                    required
                                                />

                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px">
                                                    <x-ui.text-input
                                                        label="Kode Singkatan (Opsional)"
                                                        name="code"
                                                        :value="old('code', $pos->code)"
                                                        placeholder="Contoh: INS"
                                                        maxlength="50"
                                                    />

                                                    <x-ui.text-input
                                                        label="Urutan Tampilan"
                                                        name="display_order"
                                                        type="number"
                                                        :value="old('display_order', $pos->display_order)"
                                                        min="0"
                                                    />
                                                </div>

                                                <label class="ui-field admin-field">
                                                    <span class="ui-field__label">Deskripsi / Tanggung Jawab</span>
                                                    <textarea name="description" rows="3" placeholder="Uraian singkat fungsi jabatan...">{{ old('description', $pos->description) }}</textarea>
                                                </label>

                                                <label class="ui-field admin-field">
                                                    <span class="ui-field__label">Status Jabatan</span>
                                                    <select name="is_active">
                                                        <option value="1" @selected(old('is_active', $pos->is_active ? '1' : '0') === '1')>Aktif (Tampil di Pilihan Form)</option>
                                                        <option value="0" @selected(old('is_active', $pos->is_active ? '1' : '0') === '0')>Non-aktif (Disembunyikan)</option>
                                                    </select>
                                                </label>
                                            </div>
                                        </form>

                                        <x-slot:footer>
                                            <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                                            <button class="button button--primary admin-button" type="submit" form="form-edit-pos-{{ $pos->id }}">Simpan Perubahan</button>
                                        </x-slot:footer>
                                    </x-ui.modal>
                                </td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div class="admin-panel__footer" style="padding: 16px;">
                {{ $positions->links() }}
            </div>
        @endif
    </section>

    <!-- Modal Create Position -->
    @can('employees.manage')
        <x-ui.modal
            id="create-position-modal"
            title="Tambah Jabatan Baru"
            description="Tambahkan jabatan atau posisi baru ke dalam master data."
        >
            <form method="POST" action="{{ route('admin.employee-positions.store') }}" id="form-create-position">
                @csrf
                <div style="display: grid; gap: 14px">
                    <x-ui.text-input
                        label="Nama Jabatan / Posisi"
                        name="name"
                        :value="old('name')"
                        placeholder="Contoh: Instruktur Welder"
                        required
                    />

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px">
                        <x-ui.text-input
                            label="Kode Singkatan (Opsional)"
                            name="code"
                            :value="old('code')"
                            placeholder="Contoh: INS"
                            maxlength="50"
                        />

                        <x-ui.text-input
                            label="Urutan Tampilan"
                            name="display_order"
                            type="number"
                            :value="old('display_order', 0)"
                            min="0"
                        />
                    </div>

                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Deskripsi / Tanggung Jawab</span>
                        <textarea name="description" rows="3" placeholder="Uraian singkat fungsi jabatan...">{{ old('description') }}</textarea>
                    </label>

                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Status Jabatan</span>
                        <select name="is_active">
                            <option value="1" selected>Aktif (Tampil di Pilihan Form)</option>
                            <option value="0">Non-aktif (Disembunyikan)</option>
                        </select>
                    </label>
                </div>
            </form>

            <x-slot:footer>
                <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                <button class="button button--primary admin-button" type="submit" form="form-create-position">Simpan Jabatan</button>
            </x-slot:footer>
        </x-ui.modal>
    @endcan
@endsection
