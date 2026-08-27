@extends('layouts.admin')

@section('title', 'Daftar Aset')
@section('eyebrow', 'Manajemen aset')
@section('heading', 'Daftar Aset')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Daftar dan registrasi aset</h1>
            <p>Kelola identitas, kondisi, kalibrasi, jadwal, dan label QR seluruh aset.</p>
        </div>
        @can('assets.manage')
            <a class="button button--primary admin-button" href="{{ route('admin.assets.create') }}">+ Registrasi aset</a>
        @endcan
    </section>

    <form class="admin-filter admin-asset-filter" method="GET" action="{{ route('admin.assets.index') }}">
        <label class="admin-field">
            <span>Cari aset</span>
            <input name="search" type="search" value="{{ request('search') }}" placeholder="ID, alat, merek, serial, atau lokasi">
        </label>
        <label class="admin-field">
            <span>Kategori</span>
            <select name="category">
                <option value="">Semua kategori</option>
                @foreach (\App\Models\Asset::CATEGORIES as $code => $label)
                    <option value="{{ $code }}" @selected(request('category') === $code)>{{ $code }} | {{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="admin-field">
            <span>Status</span>
            <select name="status">
                <option value="">Semua status</option>
                @foreach (\App\Models\Asset::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="admin-actions">
            <button class="button button--primary admin-button" type="submit">Terapkan</button>
            <a class="button button--outline admin-button" href="{{ route('admin.assets.index') }}">Reset</a>
        </div>
    </form>

    <form id="asset-label-selection" method="GET" action="{{ route('admin.assets.labels') }}" target="_blank"></form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar aset</h2>
                <p>{{ $assets->total() }} aset ditemukan. Pilih beberapa aset untuk mencetak label sekaligus.</p>
            </div>
            <div class="admin-actions">
                <a class="button button--outline admin-button" href="{{ route('admin.assets.export', request()->only(['search', 'category', 'status'])) }}" data-loading-download data-loading-title="Menyiapkan Excel aset">
                    <x-ui.icon name="download" size="16" /> Export Excel
                </a>
                @if ($assets->isNotEmpty())
                    <button class="button button--outline admin-button" type="submit" form="asset-label-selection" data-asset-label-selection disabled>
                        <x-ui.icon name="printer" size="16" /> Cetak label terpilih <span data-asset-selection-count>(0)</span>
                    </button>
                @endif
            </div>
        </header>

        @if ($assets->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true"><x-ui.icon name="asset" size="25" /></span>
                <h2>Belum ada aset</h2>
                <p>Registrasikan aset pertama. Asset ID akan dibuat otomatis saat disimpan.</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead>
                    <tr>
                        <th class="asset-select-cell"><input type="checkbox" data-asset-select-all aria-label="Pilih semua aset pada halaman ini"></th>
                        <th>Aset</th>
                        <th>Kategori</th>
                        <th>Lokasi / Jadwal</th>
                        <th>Kondisi / Status</th>
                        <th>Monitoring</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assets as $asset)
                        <tr>
                            <td class="asset-select-cell">
                                <input type="checkbox" name="assets[]" value="{{ $asset->id }}" form="asset-label-selection" data-asset-select aria-label="Pilih aset {{ $asset->asset_code }}">
                            </td>
                            <td>
                                <div class="asset-list-identity">
                                    @if ($asset->photoUrl())
                                        <img src="{{ $asset->photoUrl() }}" alt="Foto {{ $asset->equipment_name }}" loading="lazy">
                                    @else
                                        <span class="asset-list-identity__placeholder" aria-hidden="true"><x-ui.icon name="image" size="20" /></span>
                                    @endif
                                    <span>
                                        <strong>{{ $asset->equipment_name }}</strong>
                                        <small>{{ $asset->asset_code }}{{ $asset->serial_number ? ' · S/N '.$asset->serial_number : '' }}</small>
                                        @if ($asset->brand || $asset->model)
                                            <small>{{ collect([$asset->brand, $asset->model])->filter()->join(' · ') }}</small>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td><strong>{{ $asset->category_code }}</strong><small>{{ $asset->categoryLabel() }}</small></td>
                            <td><strong>{{ $asset->location }}</strong><small>{{ $asset->inspectionIntervalLabel() }}</small></td>
                            <td>
                                <strong>{{ $asset->conditionLabel() }}</strong>
                                <small><x-admin.status-badge :status="$asset->status" /></small>
                            </td>
                            <td>
                                <span class="asset-monitor-badge asset-monitor-badge--{{ $asset->inspectionTone() }}">{{ $asset->inspectionStatusLabel() }}</span>
                                <small>Berikutnya: {{ $asset->next_inspection_at?->translatedFormat('d M Y') ?? 'belum dijadwalkan' }}</small>
                                @if ($asset->requires_calibration)
                                    <small>Kalibrasi: {{ $asset->calibrationStatusLabel() }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="admin-action-group">
                                    <a class="admin-action-button admin-action-button--view" href="{{ route('admin.assets.labels', ['assets' => [$asset->id]]) }}" target="_blank"><x-ui.icon name="printer" size="14" /> Label</a>
                                    @can('assets.manage')
                                        <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.assets.edit', $asset) }}"><x-ui.icon name="edit" size="14" /> Edit</a>
                                        <form method="POST" action="{{ route('admin.assets.destroy', $asset) }}" data-confirm-dialog="delete-asset-{{ $asset->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="admin-action-button admin-action-button--delete" type="submit"><x-ui.icon name="trash" size="14" /> Hapus</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
            <x-ui.pagination :paginator="$assets" />
        @endif
    </section>

    @can('assets.manage')
        @foreach ($assets as $asset)
            <x-ui.confirmation :id="'delete-asset-'.$asset->id" title="Hapus aset?" confirm-label="Ya, hapus aset">
                Aset <strong>{{ $asset->asset_code }}, {{ $asset->equipment_name }}</strong> akan dihapus. Nomor Asset ID ini tidak akan digunakan ulang.
            </x-ui.confirmation>
        @endforeach
    @endcan
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/assets.css') }}?v={{ filemtime(public_path('templates/welding-school/assets.css')) }}">
@endpush

@push('scripts')
    @vite('resources/js/assets.js')
@endpush
