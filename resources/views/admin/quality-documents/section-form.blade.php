@extends('layouts.admin')

@section('title', $section->exists ? 'Edit Bab Quality' : 'Tambah Bab Quality')
@section('eyebrow', 'Quality Documents · '.$standard->name)
@section('heading', $section->exists ? 'Edit Bab Utama' : 'Tambah Bab Utama')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $section->exists ? 'Edit Bab Utama' : 'Tambah Bab Utama' }}</h1>
            <p>Bab utama {{ $standard->name }} akan menjadi daftar navigasi dokumen pada halaman Review.</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.quality-documents.standards.show', ['standard' => $standard, 'tab' => 'manual-mutu']) }}">Kembali</a>
    </section>

    <section class="admin-panel qd-narrow-form">
        <header class="admin-panel__header"><div><h2>Informasi Bab</h2><p>Gunakan nomor Bab utama yang berurutan, misalnya 1, 2, 3, dan seterusnya.</p></div></header>
        <form class="admin-panel__body" method="POST" action="{{ $section->exists ? route('admin.quality-documents.sections.update', [$standard, $section]) : route('admin.quality-documents.sections.store', $standard) }}">
            @csrf
            @if ($section->exists) @method('PUT') @endif
            <div class="admin-form-grid">
                <label class="admin-field">
                    <span>Nomor bab</span>
                    <input name="chapter_number" value="{{ old('chapter_number', $section->chapter_number) }}" placeholder="Contoh: 1" required>
                </label>
                <label class="admin-field admin-field--full">
                    <span>Judul bab</span>
                    <input name="title" value="{{ old('title', $section->title) }}" placeholder="Contoh: Pengendalian Informasi Terdokumentasi" required>
                </label>
            </div>
            <div class="admin-form-actions">
                <a class="button button--outline admin-button" href="{{ route('admin.quality-documents.standards.show', ['standard' => $standard, 'tab' => 'manual-mutu']) }}">Batal</a>
                <button class="button button--primary admin-button" type="submit">Simpan Bab</button>
            </div>
        </form>
    </section>
@endsection
