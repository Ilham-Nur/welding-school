@extends('layouts.admin')
@section('title', 'Stock Opname')
@section('eyebrow', 'Storage')
@section('heading', 'Stock Opname')
@section('content')
    <section class="admin-page-heading"><div><h1>Stock opname consumable</h1><p>Bandingkan saldo sistem dengan hitungan fisik per lokasi Storage.</p></div>@can('storage.stocktakes.manage')<a class="button button--primary admin-button" href="{{ route('admin.storage.opnames.create') }}">+ Mulai stock opname</a>@endcan</section>
    <section class="admin-panel"><header class="admin-panel__header"><div><h2>Riwayat stock opname</h2><p>{{ $opnames->total() }} sesi tercatat.</p></div></header>
        @if($opnames->isEmpty())<div class="admin-empty"><span><x-ui.icon name="clipboard-check" /></span><h2>Belum ada stock opname</h2><p>Mulai sesi pertama untuk mencocokkan stok fisik.</p></div>@else<x-ui.table class="admin-table-wrap"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Lokasi</th><th>Barang</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@foreach($opnames as $opname)<tr><td><strong>{{ $opname->number }}</strong></td><td>{{ $opname->counted_at->format('d M Y') }}</td><td>{{ $opname->location->name }}</td><td>{{ $opname->lines_count }} jenis</td><td><x-admin.status-badge :status="$opname->status" /></td><td><a class="admin-action-button admin-action-button--view" href="{{ route('admin.storage.opnames.show',$opname) }}"><x-ui.icon name="eye" size="14" /> {{ $opname->status==='counting' ? 'Lanjutkan' : 'Lihat' }}</a></td></tr>@endforeach</tbody></x-ui.table><x-ui.pagination :paginator="$opnames" />@endif
    </section>
@endsection
