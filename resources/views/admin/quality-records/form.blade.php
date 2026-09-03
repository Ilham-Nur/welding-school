@extends('layouts.admin')

@php($editing = $record->exists)
@section('title', $editing ? 'Edit Quality Record' : 'Tambah Quality Record')
@section('eyebrow', 'Quality Documents · Quality Record')
@section('heading', $editing ? 'Edit Quality Record' : 'Tambah Quality Record')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $editing ? 'Edit '.$record->name : 'Quality Record baru' }}</h1>
            <p>{{ $editing ? 'Perbarui nama dan deskripsi record.' : 'Buat wadah baru untuk mengelompokkan file quality.' }}</p>
        </div>
        <a class="button button--outline admin-button" href="{{ $editing ? route('admin.quality-records.show', $record) : route('admin.quality-records.index') }}">&larr; Kembali</a>
    </section>

    <section class="admin-panel qd-narrow-form">
        <header class="admin-panel__header"><div><h2>Informasi Record</h2><p>Nama dan deskripsi akan tampil pada daftar Quality Record.</p></div></header>
        <div class="admin-panel__body">
            <form method="POST" action="{{ $editing ? route('admin.quality-records.update', $record) : route('admin.quality-records.store') }}">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="admin-form-grid">
                    <label class="admin-field admin-field--full">
                        <span>Nama record</span>
                        <input name="name" value="{{ old('name', $record->name) }}" placeholder="Contoh: Internal Audit 2026" maxlength="255" required>
                        @error('name')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="admin-field admin-field--full">
                        <span>Deskripsi <em>opsional</em></span>
                        <textarea name="description" maxlength="10000" placeholder="Jelaskan isi atau tujuan record ini">{{ old('description', $record->description) }}</textarea>
                        @error('description')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>
                </div>
                <div class="admin-form-actions">
                    <a class="button button--outline admin-button" href="{{ $editing ? route('admin.quality-records.show', $record) : route('admin.quality-records.index') }}">Batal</a>
                    <button class="button button--primary admin-button" type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan record' }}</button>
                </div>
            </form>
        </div>
    </section>
@endsection
