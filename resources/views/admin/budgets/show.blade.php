@extends('layouts.admin')
@section('title', $budget->name)
@section('heading', 'Anggaran / RAB')
@push('styles')<link rel="stylesheet" href="{{ asset('templates/welding-school/budgets.css') }}?v={{ filemtime(public_path('templates/welding-school/budgets.css')) }}">@endpush
@section('content')
@php
    $money = fn($value) => 'Rp '.number_format($value, 0, ',', '.');
    $resultTone = fn($value) => $value > 0 ? 'profit' : ($value < 0 ? 'loss' : 'even');
    $resultLabel = fn($value) => $value > 0 ? 'Untung' : ($value < 0 ? 'Rugi' : 'Impas');
@endphp
<section class="admin-page-heading"><div><h1>{{ $budget->name }}</h1><p>{{ $budget->trainingProgram->title }} · {{ \App\Models\TrainingBudget::STATUSES[$budget->status] }} · Versi {{ $budget->revision }}</p></div><a class="button button--outline admin-button" href="{{ route('admin.budgets.index') }}">Kembali</a></section>
<section class="admin-panel"><header class="admin-panel__header"><div><h2>Harga final usulan: {{ $money($totals['price']) }} / peserta</h2><p>Sudah mencakup cadangan, keuntungan layanan, estimasi gateway, dan pajak. Berlaku sama untuk {{ count($budget->inputs['batches']) }} batch.</p>
@if($budget->approved_price !== null)<p>Harga yang sedang berlaku: <strong>{{ $money($budget->approved_price) }}</strong> (persetujuan versi {{ $budget->approved_revision }}). Selama revisi, harga ini tetap berlaku untuk pendaftaran baru.</p>@else<p>Belum ada harga yang disetujui. Pendaftaran batch belum dapat dibuka.</p>@endif
</div></header><div class="admin-panel__body"><dl class="rab-summary">
@foreach(['participants'=>'Target peserta','base'=>'Biaya dasar','reserve'=>'Cadangan','total'=>'Biaya pelatihan termasuk cadangan','cost_per_participant'=>'Biaya pelatihan per peserta','gateway'=>'Estimasi gateway termasuk pajaknya','tax'=>'Estimasi pajak harga jual','all_in_cost'=>'Total anggaran termasuk gateway & pajak','revenue'=>'Pendapatan kotor','profit'=>'Estimasi hasil setelah biaya & pajak'] as $key=>$label)
<div><dt>{{ $label }}</dt><dd @class(['rab-result-value', 'rab-result--'.$resultTone($totals[$key]) => $key === 'profit'])>{{ $key === 'participants' ? $totals[$key].' orang' : $money($totals[$key]) }}</dd>@if($key === 'profit')<span class="rab-result-label rab-result--{{ $resultTone($totals[$key]) }}">{{ $resultLabel($totals[$key]) }}</span>@endif</div>@endforeach
</dl>
@include('admin.budgets.financial-notes')
</div></section>
<section class="admin-panel"><header class="admin-panel__header"><h2>Alokasi biaya dasar per batch</h2></header><x-ui.table class="admin-table-wrap rab-read-table"><thead><tr><th>Batch</th><th>Target / Hari</th><th scope="col" class="rab-read-money">Biaya langsung</th><th scope="col" class="rab-read-money">Alokasi bersama</th><th scope="col" class="rab-read-money">Total dasar</th></tr></thead><tbody>
@foreach($budget->inputs['batches'] as $driver)
@php($batch = $budget->batches->firstWhere('id', $driver['id']))
@php($allocation = $totals['allocation'][$driver['id']])
<tr><td>@if($batch){{ $batch->code }} · {{ $batch->name }}@else{{ $driver['name'] ?? 'Rencana batch' }} · belum terbentuk @endif</td><td>{{ $driver['participants'] }} peserta / {{ $driver['days'] }} hari ({{ $driver['practice_days'] }} praktik)</td><td class="rab-read-money">{{ $money($allocation['direct']) }}</td><td class="rab-read-money">{{ $money($allocation['shared']) }}</td><td class="rab-read-money">{{ $money($allocation['base']) }}</td></tr>@endforeach
</tbody></x-ui.table><div class="admin-panel__body"><p>Biaya bersama dibagi menurut proporsi target peserta. Cadangan dan biaya pembayaran dihitung pada ringkasan gabungan.</p></div></section>
<section class="admin-panel">
    <header class="admin-panel__header"><h2>Rincian perhitungan</h2></header>
    <x-ui.table class="admin-table-wrap rab-read-table rab-calculation-table">
        <thead>
            <tr>
                <th scope="col">Komponen</th>
                <th scope="col">Cakupan</th>
                <th scope="col">Dasar hitung</th>
                <th scope="col" class="rab-read-money">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($budget->inputs['items'] as $item)
                <tr>
                    <td>
                        <strong>{{ $item['description'] }}</strong>
                        <small>{{ $item['category'] }}</small>
                        @if (!empty($item['notes']))<small>{{ $item['notes'] }}</small>@endif
                    </td>
                    <td>{{ $item['scope'] === 'shared' ? 'Bersama' : (!empty($item['batch_id']) ? ($budget->batches->firstWhere('id', $item['batch_id'])?->code ?? collect($budget->inputs['batches'])->firstWhere('id', $item['batch_id'])['name'] ?? 'Rencana batch') : 'Setiap batch') }}</td>
                    <td>
                        <small>Qty: {{ \App\Services\Training\BudgetCalculator::QUANTITIES[$item['quantity_basis']] }} × {{ $item['quantity'] }}</small>
                        <small>Frekuensi: {{ \App\Services\Training\BudgetCalculator::FREQUENCIES[$item['frequency_basis']] }} × {{ $item['frequency'] }}</small>
                        <small>Harga satuan: {{ $money($item['unit_price']) }} / {{ $item['unit'] }}</small>
                    </td>
                    <td class="rab-read-money"><strong>{{ $money($totals['lines'][$loop->index]['amount']) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>
</section>
@include('admin.budgets.stock')
<section class="admin-panel"><header class="admin-panel__header"><h2>Pengajuan & persetujuan</h2></header><div class="admin-panel__body">
@can('budgets.manage') @if(in_array($budget->status, ['draft','revision']))<a class="button button--outline admin-button" href="{{ route('admin.budgets.edit', $budget) }}">Edit RAB</a>@endif @endcan
@foreach(['submit'=>'Ajukan untuk persetujuan','approve'=>'Setujui & terapkan harga','revise'=>'Kembalikan / mulai revisi'] as $action=>$label)
@if(($action === 'submit' && in_array($budget->status, ['draft','revision'])) || ($action === 'approve' && $budget->status === 'submitted') || ($action === 'revise' && in_array($budget->status, ['submitted','approved'])))
@can($action === 'approve' ? 'budgets.approve' : 'budgets.manage')
<form method="POST" action="{{ route('admin.budgets.transition', $budget) }}" class="rab-transition">@csrf<input type="hidden" name="action" value="{{ $action }}"><input type="hidden" name="revision" value="{{ $budget->revision }}"><label>Catatan {{ $action === 'revise' ? '(wajib)' : '(opsional)' }}<input name="notes" maxlength="2000" @required($action === 'revise')></label><button type="submit" class="button button--primary admin-button">{{ $label }}</button></form>
@endcan @endif @endforeach
<p>Persetujuan pertama membuat batch dari rencana RAB dengan status Draft dan menerapkan harga untuk pendaftaran baru. Buka pendaftaran melalui menu Batch Pelatihan setelah siap. Persetujuan RAB tidak melakukan pembelian atau mengurangi stok.</p></div></section>
<section class="admin-panel"><header class="admin-panel__header"><h2>Riwayat versi & persetujuan</h2></header><div class="admin-panel__body">
@foreach($budget->events->sortByDesc('id') as $event)<details class="rab-history"><summary>{{ $event->created_at->format('d/m/Y H:i') }} · {{ $event->user?->name ?? 'Pengguna dihapus' }} · {{ ['saved'=>'Disimpan','submit'=>'Diajukan','approve'=>'Disetujui','revise'=>'Revisi'][$event->action] }} · v{{ $event->snapshot['revision'] }}</summary><p>{{ $event->notes }}</p><p>Harga saat itu: {{ $money($event->snapshot['totals']['price']) }} · RAB {{ $money($event->snapshot['totals']['total']) }} · {{ $event->snapshot['totals']['participants'] }} peserta.</p>
<x-ui.table class="admin-table-wrap rab-read-table"><thead><tr><th>Komponen saat versi disimpan</th><th scope="col" class="rab-read-money">Jumlah</th></tr></thead><tbody>@foreach($event->snapshot['totals']['lines'] as $line)<tr><td>{{ $line['description'] }}</td><td class="rab-read-money">{{ $money($line['amount']) }}</td></tr>@endforeach</tbody></x-ui.table>
</details>@endforeach
</div></section>
@endsection
