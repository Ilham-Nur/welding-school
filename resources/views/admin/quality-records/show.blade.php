@extends('layouts.admin')

@section('title', $record->name)
@section('eyebrow', 'Quality Documents · Quality Record')
@section('heading', $record->name)

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $record->name }}</h1>
            <p>{{ $record->description ?: 'Record ini belum memiliki deskripsi.' }}</p>
        </div>
        <div class="admin-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.quality-records.index') }}">&larr; Daftar record</a>
            @can('quality-documents.structure.manage')
                <a class="button button--outline admin-button" href="{{ route('admin.quality-records.edit', $record) }}">Edit record</a>
            @endcan
            @can('quality-documents.manage')
                <a class="button button--primary admin-button" href="{{ route('admin.quality-records.files.create', $record) }}">+ Upload file</a>
            @endcan
        </div>
    </section>

    <section class="qd-summary-grid">
        <article><span>Total file</span><strong>{{ $record->files_count }}</strong><small>file tersimpan</small></article>
        <article><span>Dibuat</span><strong>{{ $record->created_at->translatedFormat('d M Y') }}</strong><small>oleh {{ $record->creator?->name ?? 'pengguna lama' }}</small></article>
        <article><span>Terakhir diperbarui</span><strong>{{ $record->updated_at->translatedFormat('d M Y') }}</strong><small>{{ $record->updated_at->translatedFormat('H:i') }} WIB</small></article>
    </section>

    <section class="admin-panel">
        <header class="admin-panel__header qd-audit-header">
            <div><h2>Daftar File</h2><p>Setiap file memiliki label dan deskripsi agar mudah dikenali.</p></div>
            <form method="GET" class="qd-audit-search">
                <input type="search" name="search" value="{{ $search }}" placeholder="Cari label, deskripsi, atau nama file...">
                <button class="button button--outline admin-button" type="submit">Cari</button>
                @if ($search !== '')<a href="{{ route('admin.quality-records.show', $record) }}">Reset</a>@endif
            </form>
        </header>

        @if ($files->isEmpty())
            <div class="admin-empty">
                <span><x-ui.icon name="file" /></span>
                <h2>{{ $search !== '' ? 'File tidak ditemukan' : 'Belum ada file' }}</h2>
                <p>{{ $search !== '' ? 'Coba gunakan kata pencarian yang lain.' : 'Upload file pertama, lalu tambahkan label dan deskripsi yang jelas.' }}</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap qd-audit-table">
                <thead><tr><th>Label File</th><th>Deskripsi</th><th>File Asli</th><th>Terakhir Diperbarui</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($files as $file)
                        <tr>
                            <td><strong>{{ $file->label }}</strong></td>
                            <td>{{ $file->description ?: 'Tidak ada deskripsi.' }}</td>
                            <td><strong>{{ $file->file_name }}</strong><small>{{ strtoupper($file->file_type ?: 'FILE') }} · {{ number_format(($file->file_size ?? 0) / 1024, 0, ',', '.') }} KB</small></td>
                            <td>
                                <div class="qd-audit-updated">
                                    <time datetime="{{ $file->updated_at->toIso8601String() }}">{{ $file->updated_at->translatedFormat('d M Y, H:i') }}</time>
                                    <small>Oleh <span>{{ $file->updater?->name ?? 'Pengguna tidak tersedia' }}</span></small>
                                </div>
                            </td>
                            <td>
                                <div class="admin-action-group">
                                    @if ($file->canPreview())
                                        <a class="admin-action-button admin-action-button--view" href="{{ route('admin.quality-records.files.preview', [$record, $file]) }}" target="_blank" rel="noopener"><x-ui.icon name="eye" size="14" /> Lihat</a>
                                    @endif
                                    @can('quality-documents.manage')
                                        <a class="admin-action-button" href="{{ route('admin.quality-records.files.download', [$record, $file]) }}"><x-ui.icon name="download" size="14" /> Unduh</a>
                                        <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.quality-records.files.edit', [$record, $file]) }}"><x-ui.icon name="edit" size="14" /> Edit</a>
                                        <form method="POST" action="{{ route('admin.quality-records.files.destroy', [$record, $file]) }}" data-confirm-dialog="delete-quality-record-file-{{ $file->id }}">
                                            @csrf @method('DELETE')
                                            <button class="admin-action-button admin-action-button--delete" type="submit"><x-ui.icon name="trash" size="14" /> Hapus</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
            <x-ui.pagination :paginator="$files" />

            @can('quality-documents.manage')
                @foreach ($files as $file)
                    <x-ui.confirmation :id="'delete-quality-record-file-'.$file->id" title="Hapus file?" confirm-label="Ya, hapus file">
                        File berlabel <strong>{{ $file->label }}</strong> dengan nama asli <strong>{{ $file->file_name }}</strong> akan dihapus permanen.
                    </x-ui.confirmation>
                @endforeach
            @endcan
        @endif
    </section>
@endsection
