@extends('layouts.admin')

@php($editing = $document->exists)
@section('title', $editing ? 'Edit Data Audit' : 'Tambah Data Audit')
@section('eyebrow', 'Quality Documents · Data Audit')
@section('heading', $editing ? 'Edit Data Audit' : 'Tambah Data Audit')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $editing ? 'Edit '.$document->title : 'Dokumen Audit baru' }}</h1>
            <p>{{ $editing ? 'Perbaiki nama, keterangan, atau ganti file yang tersimpan.' : 'Unggah dokumen pendukung yang akan disiapkan untuk auditor.' }}</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.quality-documents.audit.index') }}">Kembali</a>
    </section>

    <form method="POST" action="{{ $editing ? route('admin.quality-documents.audit.update', $document) : route('admin.quality-documents.audit.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="qd-audit-form-grid">
            <section class="admin-panel">
                <header class="admin-panel__header"><div><h2>Informasi Dokumen</h2><p>Tidak memerlukan kode, jenis, atau standar ISO.</p></div></header>
                <div class="admin-panel__body admin-form-grid">
                    <label class="admin-field admin-field--full">
                        <span>Nama dokumen</span>
                        <input name="title" value="{{ old('title', $document->title) }}" placeholder="Contoh: NIB Perusahaan" required>
                        @error('title')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="admin-field admin-field--full">
                        <span>Keterangan <em>opsional</em></span>
                        <textarea name="description" placeholder="Keterangan singkat mengenai dokumen">{{ old('description', $document->description) }}</textarea>
                        @error('description')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </section>

            <aside class="admin-panel">
                <header class="admin-panel__header"><div><h2>{{ $editing ? 'Ganti File' : 'Upload File' }}</h2><p>Maksimum 20 MB.</p></div></header>
                <div class="admin-panel__body qd-file-fields">
                    @if ($editing)
                        <div class="qd-current-file"><span>File saat ini</span><strong>{{ $document->file_name }}</strong><small>{{ number_format(($document->file_size ?? 0) / 1024, 0, ',', '.') }} KB</small></div>
                    @endif
                    <x-ui.file-input
                        label="{{ $editing ? 'File pengganti (opsional)' : 'File dokumen' }}"
                        name="file"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.jpg,.jpeg,.png,.webp,.zip"
                        hint="PDF, Office, gambar, CSV, teks, atau ZIP. Maksimal 20 MB."
                        :max-size-mb="20"
                        :required="! $editing"
                    />
                </div>
            </aside>
        </div>

        <div class="admin-form-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.quality-documents.audit.index') }}">Batal</a>
            <button class="button button--primary admin-button" type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan dokumen' }}</button>
        </div>
    </form>
@endsection
