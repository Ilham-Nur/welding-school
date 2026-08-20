@extends('layouts.admin')
@php($receipt=$type==='receipt')
@section('title', $receipt ? 'Penerimaan Barang' : 'Pengeluaran Barang')
@section('eyebrow', 'Storage')
@section('heading', $receipt ? 'Penerimaan Barang' : 'Pengeluaran Barang')
@section('content')
    <section class="admin-page-heading"><div><h1>{{ $receipt ? 'Penerimaan barang' : 'Pengeluaran barang' }}</h1><p>{{ $receipt ? 'Setiap transaksi yang dikonfirmasi langsung menambah stok lokasi.' : 'Catat consumable saat diserahkan untuk kelas, kegiatan, atau pekerjaan.' }}</p></div>@can('storage.transactions.manage')<a class="button button--primary admin-button" href="{{ $receipt ? route('admin.storage.receipts.create') : route('admin.storage.issues.create') }}">+ {{ $receipt ? 'Terima barang' : 'Keluarkan barang' }}</a>@endcan</section>
    <form class="admin-filter" method="GET"><label class="admin-field"><span>Cari transaksi</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Nomor, referensi, supplier, atau tujuan"></label><div class="admin-actions"><button class="button button--primary admin-button">Cari</button><a class="button button--outline admin-button" href="{{ $receipt ? route('admin.storage.receipts.index') : route('admin.storage.issues.index') }}">Reset</a></div></form>
    <section class="admin-panel"><header class="admin-panel__header"><div><h2>Riwayat {{ $receipt ? 'penerimaan' : 'pengeluaran' }}</h2><p>{{ $transactions->total() }} transaksi ditemukan.</p></div></header>
        @if($transactions->isEmpty())<div class="admin-empty"><span><x-ui.icon :name="$receipt ? 'upload' : 'download'" /></span><h2>Belum ada transaksi</h2><p>{{ $receipt ? 'Penerimaan pertama akan menambah saldo stok.' : 'Pengeluaran pertama akan tercatat sebagai pemakaian.' }}</p></div>@else
            <x-ui.table class="admin-table-wrap"><thead><tr><th>Nomor / Tanggal</th><th>Lokasi</th><th>{{ $receipt ? 'Supplier' : 'Tujuan' }}</th><th>Referensi</th><th>Barang</th><th>Petugas</th><th>Status</th></tr></thead><tbody>@foreach($transactions as $transaction)<tr>
                <td><strong>{{ $transaction->number }}</strong><small>{{ $transaction->transaction_date->format('d M Y') }}</small></td><td>{{ $transaction->location->name }}</td><td><strong>{{ $receipt ? ($transaction->supplier ?: 'Tidak dicatat') : $transaction->purpose }}</strong>@if($transaction->trainingBatch)<small>{{ $transaction->trainingBatch->code }}</small>@endif</td><td>{{ $transaction->reference ?: 'Tanpa referensi' }}</td><td>{{ $transaction->lines_count }} jenis</td><td>{{ $transaction->handler?->name ?? 'Belum ditentukan' }}</td><td><x-admin.status-badge :status="$transaction->status" /></td>
            </tr>@endforeach</tbody></x-ui.table><x-ui.pagination :paginator="$transactions" />
        @endif
    </section>
@endsection
