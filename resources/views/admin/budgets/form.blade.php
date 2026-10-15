@extends('layouts.admin')
@section('title', 'Susun RAB')
@section('heading', 'Susun RAB')
@push('styles')<link rel="stylesheet" href="{{ asset('templates/welding-school/budgets.css') }}?v={{ filemtime(public_path('templates/welding-school/budgets.css')) }}">@endpush
@section('content')
@php
    $inputs = $budget->inputs;
    $batchInputs = collect(old('batches', $inputs['batches']))->keyBy('id');
    $planning = (bool) ($inputs['planning'] ?? false);
    $locked = $budget->approved_price !== null;
    $choiceBatches = $planning ? $batchInputs->map(fn($row) => (object) ['id'=>$row['id'], 'code'=>$row['name'] ?? 'Rencana batch']) : $batches;
    $items = old('items', $inputs['items']);
    $categories = collect($items)->groupBy('category', preserveKeys: true);
    $drivers = ['participants' => ['Target peserta', 15], 'minimum_participants' => ['Minimum (simulasi)', 10], 'days' => ['Hari pelatihan', 10], 'practice_days' => ['Hari praktik', 8], 'instructors' => ['Instruktur', 2], 'assistants' => ['Asisten', 1], 'machines' => ['Mesin las', 10], 'tools' => ['Set alat kerja', 10]];
@endphp
<section class="admin-page-heading"><div><h1>{{ $budget->exists ? 'Edit RAB' : 'Susun RAB Welder' }}</h1><p>Susun biaya per kategori. Subtotal dan harga final diperbarui saat Anda mengubah angka.</p></div><a class="button button--outline admin-button" href="{{ route('admin.budgets.index') }}">Kembali</a></section>
<form method="POST" action="{{ $budget->exists ? route('admin.budgets.update', $budget) : route('admin.budgets.store') }}" data-budget-form data-preview-url="{{ route('admin.budgets.preview') }}">
@csrf @if($planning)<input type="hidden" name="planning" value="1">@endif @if($budget->exists) @method('PUT') @endif
<input type="hidden" name="revision" value="{{ $budget->revision }}">
<aside class="rab-live-summary" aria-label="Ringkasan perhitungan RAB">
    <div><span>Harga final / peserta</span><strong class="rab-final-price" data-live="price">—</strong></div>
    <div><span>Total anggaran</span><strong data-live="all_in_cost">—</strong></div>
    <div><span>Target peserta</span><strong data-live="participants">—</strong></div>
    <div><span>Estimasi keuntungan</span><strong data-live="profit">—</strong></div>
    <p data-preview-status role="status">Pilih program dan batch untuk melihat perhitungan.</p>
</aside>
<section class="admin-panel"><header class="admin-panel__header"><h2>1. Program dan batch</h2></header><div class="admin-panel__body">
<div class="admin-form-grid"><x-ui.text-input label="Nama RAB" name="name" :value="$budget->name" required />
<label class="ui-field admin-field"><span class="ui-field__label">Program</span><select name="training_program_id" required data-budget-program><option value="">Pilih program</option>@foreach($programs as $program)<option value="{{ $program->id }}" @selected(old('training_program_id', $budget->training_program_id) == $program->id)>{{ $program->code }} · {{ $program->title }}</option>@endforeach</select></label></div>
@if($planning)
<p>Tentukan rencana batch di sini. Batch operasional dibuat otomatis setelah RAB disetujui, dengan kapasitas sesuai target peserta. Minimum hanya untuk simulasi.</p>
@if($locked)<p>Batch sudah terbentuk. Ubah nama dan jadwal pelaksanaan melalui menu Batch Pelatihan.</p>@endif
<div data-budget-plans>@foreach($batchInputs as $planIndex => $plan) @include('admin.budgets.plan') @endforeach</div>
@if(!$locked)<button type="button" class="button button--outline admin-button" data-add-plan>+ Rencana batch</button>
<template data-plan-template>@include('admin.budgets.plan', ['planIndex'=>'__PLAN__', 'plan'=>[], 'locked'=>false])</template>@endif
@else
<p>Centang batch yang dibiayai. Minimum peserta dipakai untuk simulasi, bukan syarat pelaksanaan.</p>
@forelse($batches as $batch)
@php($selected = $batchInputs->get($batch->id))
<div class="rab-batch" data-budget-batch data-program="{{ $batch->training_program_id }}">
<label><input type="checkbox" data-batch-toggle @checked($selected)> <strong>{{ $batch->code }} · {{ $batch->name }}</strong> <small>Kapasitas {{ $batch->capacity }}</small></label>
<fieldset @disabled(!$selected)><input type="hidden" name="batches[{{ $batch->id }}][id]" value="{{ $batch->id }}">
<div class="rab-drivers">@foreach($drivers as $key => [$label, $default])<label>{{ $label }}<input aria-label="{{ $label }} {{ $batch->code }}" type="number" name="batches[{{ $batch->id }}][{{ $key }}]" value="{{ $selected[$key] ?? (in_array($key, ['participants','minimum_participants']) ? min($default, $batch->capacity) : $default) }}" min="{{ in_array($key, ['participants','minimum_participants','days']) ? 1 : 0 }}" required></label>@endforeach</div>
</fieldset></div>
@empty<p>Belum ada batch tersedia.</p>@endforelse
@endif
</div></section>
<section class="admin-panel rab-components"><header class="admin-panel__header"><div><h2>2. Komponen biaya</h2><p>Ubah angka langsung di tabel. Buka Detail untuk cakupan batch, dasar perhitungan, dan catatan.</p></div><button type="button" class="button button--outline admin-button" data-add-item>+ Komponen</button></header>
<div class="rab-table-toolbar"><label>Tampilkan biaya<select data-budget-filter><option value="all">Semua batch & biaya bersama</option><option value="shared">Biaya bersama saja</option></select></label><p>Ringkasan harga selalu mencakup seluruh RAB.</p></div>
<div data-budget-items>@foreach($categories as $category => $categoryItems) @include('admin.budgets.category') @endforeach</div>
<p class="rab-empty" data-empty-items hidden>Belum ada komponen pada pilihan ini.</p>
</section>
@include('admin.budgets.stock')
<datalist id="rab-category-options">@foreach($categories->keys() as $category)<option value="{{ $category }}">@endforeach</datalist>
<template data-category-template>@include('admin.budgets.category', ['category'=>'Kategori baru','categoryItems'=>[]])</template>
<template data-item-template>@include('admin.budgets.item', ['index' => '__INDEX__', 'item' => ['scope'=>'batch','quantity_basis'=>'manual','frequency_basis'=>'manual','quantity'=>1,'frequency'=>1,'unit_price'=>0]])</template>
<section class="admin-panel"><header class="admin-panel__header"><div><h2>3. Harga final dan biaya pembayaran</h2><p>Keuntungan layanan tercakup dalam markup. Tidak ada tambahan administrasi Rp150.000 pada harga RAB.</p></div></header><div class="admin-panel__body"><div class="admin-form-grid">
@foreach(['contingency_percent'=>'Cadangan (%)','markup_percent'=>'Markup keuntungan layanan (%)','sales_tax_percent'=>'Pajak harga jual (%)','gateway_percent'=>'Estimasi gateway (% nilai pembayaran)','gateway_fixed'=>'Estimasi gateway tetap (Rp / transaksi)','gateway_tax_percent'=>'Pajak atas biaya gateway (%)','payments_per_participant'=>'Perkiraan transaksi per peserta'] as $key=>$label)
<x-ui.text-input :label="$label" :name="$key" type="number" :value="$inputs[$key]" min="0" :step="in_array($key, ['gateway_fixed','payments_per_participant']) ? '1' : '0.01'" required />@endforeach
<x-ui.text-input wrapper-class="ui-field--full" label="Dasar estimasi biaya pembayaran" name="gateway_notes" :value="$inputs['gateway_notes'] ?? ''" placeholder="Asumsi metode pembayaran, tarif merchant, atau alasan jika biaya nol" required />
</div><p>Tarif gateway dan pajak diisi sesuai akun merchant. Nilai awal nol perlu ditinjau. Jumlah transaksi hanya asumsi biaya; fitur cicilan belum ditambahkan.</p></div></section>
<section class="admin-panel"><header class="admin-panel__header"><h2>Rincian harga akhir</h2></header><div class="admin-panel__body"><dl class="rab-summary">
@foreach(['base'=>'Biaya dasar','reserve'=>'Cadangan','gateway'=>'Gateway termasuk pajaknya','tax'=>'Pajak harga jual','revenue'=>'Pendapatan kotor','all_in_cost'=>'Total anggaran'] as $key=>$label)<div><dt>{{ $label }}</dt><dd data-live="{{ $key }}">—</dd></div>@endforeach
</dl><p>Nilai di atas merupakan estimasi seluruh RAB. Harga diterapkan setelah persetujuan.</p></div></section>
<div class="admin-form-actions"><button class="button button--primary admin-button" type="submit">Simpan RAB</button></div></form>
@endsection
@push('scripts')<script src="{{ asset('templates/welding-school/budgets.js') }}?v={{ filemtime(public_path('templates/welding-school/budgets.js')) }}" defer></script>@endpush
