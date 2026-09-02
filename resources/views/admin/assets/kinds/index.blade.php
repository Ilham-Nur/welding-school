@extends('layouts.admin')

@section('title', 'Daftar Jenis Aset')
@section('eyebrow', 'Manajemen Aset')
@section('heading', 'Kategori & Jenis Aset')

@section('content')
    @php
        $listContext = array_filter([
            'from_list' => '1',
            'redirect_search' => request()->string('search')->toString(),
            'redirect_category' => request()->string('category')->toString(),
            'redirect_status' => request()->string('status')->toString(),
            'redirect_page' => request()->integer('page') > 1 ? request()->integer('page') : null,
        ], fn ($value) => $value !== null && $value !== '');
        $createParameters = $listContext;
        if (request()->filled('category')) {
            $createParameters['category'] = request()->string('category')->toString();
        }
    @endphp

    <section class="admin-page-heading">
        <div>
            <h1>Daftar jenis aset</h1>
            <p>Lihat kategori, kode, nomor terakhir, jumlah aset, dan status setiap jenis aset.</p>
        </div>
        <div class="admin-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.assets.index') }}">← Kembali ke daftar aset</a>
            <a class="button button--primary admin-button" href="{{ route('admin.asset-kinds.create', $createParameters) }}">+ Tambah jenis aset</a>
        </div>
    </section>

    <section class="admin-panel asset-kind-master-list">
        <header class="admin-panel__header">
            <div>
                <h2>Jenis aset terdaftar</h2>
                <p>Cari berdasarkan nama atau kode, kemudian filter berdasarkan kategori dan status.</p>
            </div>
        </header>
        <div class="admin-panel__body asset-kind-master-filter-wrap">
            <form class="asset-kind-master-filter" method="GET" action="{{ route('admin.asset-kinds.index') }}">
                <label class="ui-field admin-field">
                    <span class="ui-field__label">Pencarian</span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari Cutting atau CUT...">
                </label>
                <label class="ui-field admin-field">
                    <span class="ui-field__label">Kategori</span>
                    <select name="category">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $code => $label)
                            <option value="{{ $code }}" @selected(request('category') === $code)>{{ $code }} | {{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="ui-field admin-field">
                    <span class="ui-field__label">Status</span>
                    <select name="status">
                        <option value="">Semua status</option>
                        <option value="active" @selected(request('status') === 'active')>Aktif</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                    </select>
                </label>
                <div class="admin-actions asset-kind-master-filter__actions">
                    <button class="button button--primary admin-button" type="submit">Terapkan</button>
                    @if (request()->hasAny(['search', 'category', 'status']))
                        <a class="button button--outline admin-button" href="{{ route('admin.asset-kinds.index') }}">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        @if ($assetKinds->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true"><x-ui.icon name="asset" size="24" /></span>
                <h2>Jenis aset tidak ditemukan</h2>
                <p>Ubah filter atau tambahkan jenis aset baru.</p>
                <a class="button button--primary admin-button" href="{{ route('admin.asset-kinds.create', $createParameters) }}">+ Tambah jenis aset</a>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Jenis aset</th>
                        <th>Nomor</th>
                        <th>Jumlah aset</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assetKinds as $kind)
                        @php
                            $lastNumber = (int) ($kind->numberSequence?->last_number ?? 0);
                            $hasHistory = $lastNumber > 0 || $kind->assets_count > 0;
                        @endphp
                        <tr>
                            <td><strong>{{ $kind->category_code }}</strong><small>{{ $kind->categoryLabel() }}</small></td>
                            <td><strong>{{ $kind->name }} | {{ $kind->code }}</strong><small>{{ $hasHistory ? 'Kode identitas dikunci' : 'Belum pernah digunakan' }}</small></td>
                            <td>
                                <strong>{{ $lastNumber > 0 ? $kind->codeFor($lastNumber) : 'Belum ada' }}</strong>
                                <small>Berikutnya {{ $kind->codeFor($lastNumber + 1) }}</small>
                            </td>
                            <td><strong>{{ $kind->assets_count }}</strong><small>aset terhubung</small></td>
                            <td>
                                <x-admin.status-badge :status="$kind->is_active ? 'active' : 'inactive'">
                                    {{ $kind->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-admin.status-badge>
                            </td>
                            <td>
                                <div class="admin-action-group">
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.asset-kinds.edit', ['assetKind' => $kind, ...$listContext]) }}">
                                        <x-ui.icon name="edit" size="13" /> Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.asset-kinds.toggle', $kind) }}" data-confirm-dialog="toggle-kind-{{ $kind->id }}">
                                        @csrf
                                        @method('PATCH')
                                        @foreach ($listContext as $name => $value)
                                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                        @endforeach
                                        <button class="admin-action-button {{ $kind->is_active ? 'admin-action-button--delete' : 'admin-action-button--view' }}" type="submit">
                                            <x-ui.icon :name="$kind->is_active ? 'x-circle' : 'check-circle'" size="13" />
                                            {{ $kind->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    @unless ($hasHistory)
                                        <form method="POST" action="{{ route('admin.asset-kinds.destroy', $kind) }}" data-confirm-dialog="delete-kind-{{ $kind->id }}">
                                            @csrf
                                            @method('DELETE')
                                            @foreach ($listContext as $name => $value)
                                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                            @endforeach
                                            <button class="admin-action-button admin-action-button--delete" type="submit">
                                                <x-ui.icon name="trash" size="13" /> Hapus
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
            <x-ui.pagination :paginator="$assetKinds" />
        @endif
    </section>

    @foreach ($assetKinds as $kind)
        <x-ui.confirmation
            :id="'toggle-kind-'.$kind->id"
            :title="$kind->is_active ? 'Nonaktifkan jenis aset?' : 'Aktifkan kembali jenis aset?'"
            :confirm-label="$kind->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan'"
            :tone="$kind->is_active ? 'danger' : 'success'"
        >
            <strong>{{ $kind->name }} | {{ $kind->code }}</strong>
            {{ $kind->is_active
                ? 'tidak akan muncul pada registrasi aset baru. Aset dan nomor yang sudah ada tetap aman.'
                : 'akan tersedia kembali pada pilihan registrasi aset baru.' }}
        </x-ui.confirmation>

        @if ((int) ($kind->numberSequence?->last_number ?? 0) === 0 && $kind->assets_count === 0)
            <x-ui.confirmation :id="'delete-kind-'.$kind->id" title="Hapus jenis aset?" confirm-label="Ya, hapus permanen">
                <strong>{{ $kind->name }} | {{ $kind->code }}</strong> belum pernah digunakan dan akan dihapus permanen.
            </x-ui.confirmation>
        @endif
    @endforeach
@endsection
