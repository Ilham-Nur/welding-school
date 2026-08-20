@extends('layouts.admin')
@section('title', 'Stok Consumable')
@section('eyebrow', 'Storage')
@section('heading', 'Stok Consumable')
@section('content')
    <section class="admin-page-heading"><div><h1>Stok consumable</h1><p>Master dan saldo barang habis pakai seperti kawat las, elektroda, mata gerinda, gas, dan APD sekali pakai.</p></div>@can('storage.items.manage')<a class="button button--primary admin-button" href="{{ route('admin.storage-items.create') }}">+ Tambah consumable</a>@endcan</section>
    <form class="admin-filter" method="GET"><label class="admin-field"><span>Cari barang</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Kode, nama, atau kategori"></label><label class="admin-field"><span>Status</span><select name="status"><option value="">Semua status</option><option value="active" @selected(request('status')==='active')>Aktif</option><option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option></select></label><div class="admin-actions"><button class="button button--primary admin-button">Terapkan</button><a class="button button--outline admin-button" href="{{ route('admin.storage-items.index') }}">Reset</a></div></form>
    <section class="admin-panel"><header class="admin-panel__header"><div><h2>Daftar consumable</h2><p>{{ $items->total() }} jenis barang ditemukan.</p></div></header>
        @if($items->isEmpty())<div class="admin-empty"><span><x-ui.icon name="storage" /></span><h2>Belum ada consumable</h2><p>Buat master barang, lalu tambahkan stok melalui Penerimaan Barang.</p></div>@else
            <x-ui.table class="admin-table-wrap"><thead><tr><th>Barang</th><th>Kategori</th><th>Stok total</th><th>Minimum</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @foreach($items as $item)@php($total=(float)($item->stocks_sum_quantity ?? 0))<tr>
                <td><strong>{{ $item->name }}</strong><small>{{ $item->code }} · {{ $item->unit }}</small></td><td>{{ $item->category }}</td>
                <td><strong>{{ number_format($total, 3) }} {{ $item->unit }}</strong></td><td>{{ number_format((float)$item->minimum_stock, 3) }} {{ $item->unit }}</td>
                <td>@if(!$item->is_active)<x-admin.status-badge status="inactive" />@elseif($total<=0)<x-admin.status-badge status="inactive">Habis</x-admin.status-badge>@elseif($total<=(float)$item->minimum_stock)<x-admin.status-badge status="pending">Menipis</x-admin.status-badge>@else<x-admin.status-badge status="active">Aman</x-admin.status-badge>@endif</td>
                <td><div class="admin-action-group"><a class="admin-action-button admin-action-button--view" href="{{ route('admin.storage-items.show', $item) }}"><x-ui.icon name="eye" size="14" /> Detail</a>@can('storage.items.manage')<a class="admin-action-button admin-action-button--edit" href="{{ route('admin.storage-items.edit', $item) }}"><x-ui.icon name="edit" size="14" /> Edit</a>@endcan</div></td>
            </tr>@endforeach</tbody></x-ui.table><x-ui.pagination :paginator="$items" />
        @endif
    </section>
@endsection
