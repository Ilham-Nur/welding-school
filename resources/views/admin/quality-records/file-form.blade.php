@extends('layouts.admin')

@php($editing = $file->exists)
@section('title', $editing ? 'Edit File Quality Record' : 'Upload File Quality Record')
@section('eyebrow', 'Quality Record · '.$record->name)
@section('heading', $editing ? 'Edit File' : 'Upload File')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $editing ? 'Edit '.$file->label : 'Upload file baru' }}</h1>
            <p>Tambahkan label dan deskripsi agar isi file mudah ditemukan dan dipahami.</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.quality-records.show', $record) }}">&larr; Kembali</a>
    </section>

    <form method="POST" action="{{ $editing ? route('admin.quality-records.files.update', [$record, $file]) : route('admin.quality-records.files.store', $record) }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="qd-audit-form-grid">
            <section class="admin-panel">
                <header class="admin-panel__header"><div><h2>Informasi File</h2><p>Label merupakan nama yang akan terlihat di tabel.</p></div></header>
                <div class="admin-panel__body admin-form-grid">
                    <label class="admin-field admin-field--full">
                        <span>Label file</span>
                        <input name="label" value="{{ old('label', $file->label) }}" placeholder="Contoh: Laporan Internal Audit 2026" maxlength="255" required>
                        @error('label')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="admin-field admin-field--full">
                        <span>Deskripsi <em>opsional</em></span>
                        <textarea name="description" maxlength="10000" placeholder="Jelaskan isi atau kegunaan file">{{ old('description', $file->description) }}</textarea>
                        @error('description')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </section>

            <aside class="admin-panel">
                <header class="admin-panel__header"><div><h2>{{ $editing ? 'Ganti File' : 'Pilih File' }}</h2><p>Maksimum 20 MB.</p></div></header>
                <div class="admin-panel__body qd-file-fields">
                    @if ($editing)
                        <div class="qd-current-file"><span>File saat ini</span><strong>{{ $file->file_name }}</strong><small>{{ number_format(($file->file_size ?? 0) / 1024, 0, ',', '.') }} KB</small></div>
                    @endif
                    <x-ui.file-input
                        label="{{ $editing ? 'File pengganti (opsional)' : 'File yang diupload' }}"
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
            <a class="button button--outline admin-button" href="{{ route('admin.quality-records.show', $record) }}">Batal</a>
            <button class="button button--primary admin-button" type="submit">{{ $editing ? 'Simpan perubahan' : 'Upload file' }}</button>
        </div>
    </form>
@endsection
