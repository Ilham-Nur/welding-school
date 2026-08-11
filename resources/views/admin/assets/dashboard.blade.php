@extends('layouts.admin')

@section('title', 'Dashboard Aset')
@section('eyebrow', 'Manajemen aset')
@section('heading', 'Dashboard Aset')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Ringkasan aset dan inspeksi</h1>
            <p>Pantau kondisi aset, jadwal inspeksi, dan kalibrasi dari satu halaman.</p>
        </div>
        <div class="admin-actions">
            @can('assets.inspect')
                <button class="button button--outline admin-button" type="button" data-open-asset-scanner><x-ui.icon name="scan" size="16" /> Buka pemindai</button>
            @endcan
            <a class="button button--primary admin-button" href="{{ route('admin.assets.index') }}">Lihat daftar aset</a>
        </div>
    </section>

    <section class="admin-stat-grid" aria-label="Ringkasan aset">
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Total aset</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="asset" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['total']) }}</strong>
            <p>Seluruh aset terdaftar</p>
        </article>
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Aset aktif</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="check-circle" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['active']) }}</strong>
            <p>Siap digunakan</p>
        </article>
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Inspeksi jatuh tempo</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="alert-triangle" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['inspectionDue']) }}</strong>
            <p>Perlu segera diperiksa</p>
        </article>
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Perhatian kalibrasi</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="calendar" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['calibrationAlerts']) }}</strong>
            <p>Jatuh tempo dalam 30 hari</p>
        </article>
    </section>

    <div class="asset-dashboard-grid">
        <section class="admin-panel">
            <header class="admin-panel__header">
                <div><h2>Inspeksi yang perlu dilakukan</h2><p>Aset yang jatuh tempo atau akan jatuh tempo dalam 7 hari.</p></div>
            </header>
            @if ($dueAssets->isEmpty())
                <div class="admin-empty"><span aria-hidden="true"><x-ui.icon name="check-circle" size="25" /></span><h2>Jadwal terkendali</h2><p>Tidak ada inspeksi yang perlu segera dilakukan.</p></div>
            @else
                <x-ui.table class="admin-table-wrap">
                    <thead><tr><th>Aset</th><th>Lokasi</th><th>Jadwal</th></tr></thead>
                    <tbody>
                        @foreach ($dueAssets as $asset)
                            <tr>
                                <td><strong>{{ $asset->asset_code }}</strong><small>{{ $asset->equipment_name }}</small></td>
                                <td>{{ $asset->location }}</td>
                                <td><span class="asset-monitor-badge asset-monitor-badge--{{ $asset->inspectionTone() }}">{{ $asset->inspectionStatusLabel() }}</span><small>{{ $asset->next_inspection_at?->translatedFormat('d M Y') ?? 'Belum dijadwalkan' }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @endif
        </section>

        <section class="admin-panel">
            <header class="admin-panel__header">
                <div><h2>Aset per kategori</h2><p>Jumlah inventaris berdasarkan kelompok Asset ID.</p></div>
            </header>
            <div class="asset-category-summary">
                @foreach (\App\Models\Asset::CATEGORIES as $code => $label)
                    <article><span>{{ $code }}</span><div><strong>{{ number_format((int) ($categoryCounts[$code] ?? 0)) }}</strong><small>{{ $label }}</small></div></article>
                @endforeach
            </div>
        </section>
    </div>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div><h2>Aktivitas inspeksi terbaru</h2><p>Hasil pemeriksaan terakhir yang tersimpan.</p></div>
        </header>
        @if ($recentInspections->isEmpty())
            <div class="admin-empty"><span aria-hidden="true"><x-ui.icon name="clipboard-check" size="25" /></span><h2>Belum ada inspeksi</h2><p>Riwayat akan tampil setelah checklist pertama disimpan.</p></div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead><tr><th>Waktu</th><th>Aset</th><th>Inspector</th><th>Hasil</th><th>Kondisi / Status</th></tr></thead>
                <tbody>
                    @foreach ($recentInspections as $inspection)
                        <tr>
                            <td><strong>{{ $inspection->inspected_at->translatedFormat('d M Y') }}</strong><small>{{ $inspection->inspected_at->format('H:i') }} WIB</small></td>
                            <td><strong>{{ $inspection->asset->asset_code }}</strong><small>{{ $inspection->asset->equipment_name }}</small></td>
                            <td>{{ $inspection->inspector?->name ?? 'Akun sudah tidak aktif' }}</td>
                            <td>
                                @if ($inspection->failed_items_count)
                                    <span class="asset-monitor-badge asset-monitor-badge--danger">{{ $inspection->failed_items_count }} jawaban Tidak</span>
                                @else
                                    <span class="asset-monitor-badge asset-monitor-badge--success">Semua Ya</span>
                                @endif
                            </td>
                            <td><strong>{{ \App\Models\Asset::CONDITIONS[$inspection->condition] ?? $inspection->condition }}</strong><small>{{ \App\Models\Asset::STATUSES[$inspection->status] ?? $inspection->status }}</small></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </section>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/assets.css') }}?v={{ filemtime(public_path('templates/welding-school/assets.css')) }}">
@endpush
