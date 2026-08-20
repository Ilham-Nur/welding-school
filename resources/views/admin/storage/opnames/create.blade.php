@extends('layouts.admin')
@section('title', 'Mulai Stock Opname')
@section('eyebrow', 'Storage')
@section('heading', 'Mulai Stock Opname')
@section('content')
    <section class="admin-page-heading"><div><h1>Mulai stock opname</h1><p>Sistem mengambil snapshot saldo saat sesi dibuat. Hitung fisik lalu selesaikan pada halaman berikutnya.</p></div><a class="button button--outline admin-button" href="{{ route('admin.storage.opnames.index') }}">&larr; Kembali</a></section>
    <section class="admin-panel"><header class="admin-panel__header"><div><h2>Informasi sesi</h2><p>Pilih satu lokasi Storage untuk setiap sesi.</p></div></header><div class="admin-panel__body"><form method="POST" action="{{ route('admin.storage.opnames.store') }}">@csrf<div class="admin-form-grid">
        <label class="ui-field admin-field"><span class="ui-field__label">Lokasi Storage <em>Wajib</em></span><select name="location_id" required><option value="">Pilih lokasi</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected((string)old('location_id')===(string)$location->id)>{{ $location->fullName() }}</option>@endforeach</select></label>
        <x-ui.text-input label="Tanggal hitung" name="counted_at" type="date" :value="now()->format('Y-m-d')" required />
        <label class="ui-field admin-field admin-field--full"><span class="ui-field__label">Catatan</span><textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
    </div><div class="admin-form-actions"><a class="button button--outline admin-button" href="{{ route('admin.storage.opnames.index') }}">Batal</a><button class="button button--primary admin-button" @disabled($locations->isEmpty())>Mulai hitung</button></div></form></div></section>
@endsection
