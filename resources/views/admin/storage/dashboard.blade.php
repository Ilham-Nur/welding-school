@extends('layouts.admin')
@section('title', 'Dashboard Storage')
@section('eyebrow', 'Storage')
@section('heading', 'Dashboard Storage')
@section('content')
    <section class="admin-page-heading"><div><h1>Kontrol Storage</h1><p>Pantau stok consumable dan aset yang dipinjamkan keluar tanpa mencatat pemakaian alat harian di workshop.</p></div><div class="admin-actions">@can('storage.transactions.manage')<a class="button button--outline admin-button" href="{{ route('admin.storage.receipts.create') }}">+ Terima barang</a><a class="button button--primary admin-button" href="{{ route('admin.storage.issues.create') }}">Keluarkan barang</a>@endcan</div></section>
    <section class="admin-stat-grid">
        <article class="admin-stat"><span class="admin-stat__icon"><x-ui.icon name="storage" /></span><strong>{{ number_format($itemCount) }}</strong><small>Jenis consumable</small><p>Master barang aktif</p></article>
        <article class="admin-stat"><span class="admin-stat__icon"><x-ui.icon name="alert-triangle" /></span><strong>{{ number_format($lowStockCount) }}</strong><small>Stok minimum</small><p>{{ $emptyStockCount }} barang kosong</p></article>
        <article class="admin-stat"><span class="admin-stat__icon"><x-ui.icon name="asset" /></span><strong>{{ number_format($activeLoanCount) }}</strong><small>Pinjaman keluar</small><p>Aset sedang di luar area</p></article>
        <article class="admin-stat"><span class="admin-stat__icon"><x-ui.icon name="calendar" /></span><strong>{{ number_format($overdueLoanCount) }}</strong><small>Terlambat kembali</small><p>Perlu ditindaklanjuti</p></article>
    </section>
    <div class="storage-dashboard-grid">
        <section class="admin-panel"><header class="admin-panel__header"><div><h2>Stok perlu perhatian</h2><p>Jumlah total sama atau di bawah minimum.</p></div><a class="admin-link" href="{{ route('admin.storage-items.index') }}">Lihat stok</a></header>
            @if($lowStockItems->isEmpty())<div class="admin-empty admin-empty--compact"><h2>Semua stok aman</h2><p>Belum ada consumable di bawah batas minimum.</p></div>@else<x-ui.table class="admin-table-wrap"><thead><tr><th>Barang</th><th>Stok</th><th>Status</th></tr></thead><tbody>@foreach($lowStockItems as $item)<tr><td><a class="admin-link" href="{{ route('admin.storage-items.show', $item) }}">{{ $item->name }}</a><small>{{ $item->code }}</small></td><td><strong>{{ format_quantity($item->stocks_sum_quantity ?? 0) }} {{ $item->unit->symbol }}</strong><small>Minimum {{ format_quantity($item->minimum_stock) }}</small></td><td><x-admin.status-badge :status="(float)($item->stocks_sum_quantity ?? 0) <= 0 ? 'inactive' : 'pending'">{{ (float)($item->stocks_sum_quantity ?? 0) <= 0 ? 'Habis' : 'Menipis' }}</x-admin.status-badge></td></tr>@endforeach</tbody></x-ui.table>@endif
        </section>
        <section class="admin-panel"><header class="admin-panel__header"><div><h2>Transaksi terbaru</h2><p>Penerimaan, pengeluaran, dan penyesuaian terakhir.</p></div></header>
            @if($recentTransactions->isEmpty())<div class="admin-empty admin-empty--compact"><h2>Belum ada transaksi</h2><p>Aktivitas stok akan tampil di sini.</p></div>@else<div class="storage-activity-list">@foreach($recentTransactions as $transaction)<article><span class="storage-activity-list__icon"><x-ui.icon :name="$transaction->type === 'receipt' ? 'upload' : 'download'" size="16" /></span><div><strong>{{ $transaction->number }}</strong><small>{{ ucfirst($transaction->type) }} · {{ $transaction->location->name }} · {{ $transaction->lines_count }} barang</small></div><time>{{ $transaction->transaction_date->format('d/m/Y') }}</time></article>@endforeach</div>@endif
        </section>
    </div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('templates/welding-school/storage.css') }}?v={{ filemtime(public_path('templates/welding-school/storage.css')) }}">@endpush
