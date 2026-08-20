@extends('layouts.admin')
@section('title', $opname->number)
@section('eyebrow', 'Storage · Stock opname')
@section('heading', $opname->number)
@section('content')
    <section class="admin-page-heading"><div><h1>{{ $opname->number }}</h1><p>{{ $opname->location->fullName() }} · {{ $opname->counted_at->format('d M Y') }}</p></div><a class="button button--outline admin-button" href="{{ route('admin.storage.opnames.index') }}">&larr; Daftar opname</a></section>
    @if($opname->status==='counting')
        <x-ui.alert type="info" title="Masukkan hasil hitung fisik">Setelah diselesaikan, selisih akan memperbarui saldo dan tercatat pada kartu stok. Proses ini tidak dapat diulang.</x-ui.alert>
        <form method="POST" action="{{ route('admin.storage.opnames.complete',$opname) }}">@csrf @method('PATCH')
    @endif
    <section class="admin-panel"><header class="admin-panel__header"><div><h2>Hasil per barang</h2><p>{{ $opname->lines->count() }} consumable dalam sesi ini.</p></div><x-admin.status-badge :status="$opname->status" /></header>
        <x-ui.table class="admin-table-wrap"><thead><tr><th>Barang</th><th>Stok sistem</th><th>Hitung fisik</th><th>Selisih</th><th>Catatan</th></tr></thead><tbody>@foreach($opname->lines as $line)<tr>
            <td><strong>{{ $line->item->name }}</strong><small>{{ $line->item->code }} · {{ $line->item->unit }}</small></td><td>{{ number_format((float)$line->system_quantity,3) }}</td>
            @if($opname->status==='counting')<td><input class="storage-count-input" type="text" inputmode="decimal" data-number-format data-number-decimals="3" name="counts[{{ $line->id }}]" value="{{ rtrim(rtrim(number_format((float) old('counts.'.$line->id, $line->system_quantity), 3, ',', '.'), '0'), ',') }}" required></td><td><span data-opname-difference>Otomatis</span></td><td><input type="text" name="line_notes[{{ $line->id }}]" value="{{ old('line_notes.'.$line->id) }}" maxlength="255" placeholder="Alasan jika selisih"></td>
            @else<td>{{ number_format((float)$line->counted_quantity,3) }}</td><td><strong class="{{ (float)$line->difference!==0.0 ? 'storage-difference' : '' }}">{{ (float)$line->difference>0?'+':'' }}{{ number_format((float)$line->difference,3) }}</strong></td><td>{{ $line->notes ?: 'Tanpa catatan' }}</td>@endif
        </tr>@endforeach</tbody></x-ui.table>
    </section>
    @if($opname->status==='counting')<div class="admin-form-actions"><a class="button button--outline admin-button" href="{{ route('admin.storage.opnames.index') }}">Simpan untuk nanti</a>@can('storage.stocktakes.manage')<button class="button button--primary admin-button">Selesaikan & sesuaikan stok</button>@endcan</div></form>@endif
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('templates/welding-school/storage.css') }}?v={{ filemtime(public_path('templates/welding-school/storage.css')) }}">@endpush
