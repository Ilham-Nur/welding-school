@extends('layouts.admin')

@section('title', 'Data Audit')
@section('eyebrow', 'Quality Documents')
@section('heading', 'Data Audit')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Data Audit</h1>
            <p>Kumpulan dokumen pendukung yang disiapkan untuk pemeriksaan auditor.</p>
        </div>
        <div class="admin-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.quality-documents.index') }}">Kembali</a>
            @can('quality-documents.manage')
                <a class="button button--primary admin-button" href="{{ route('admin.quality-documents.audit.create') }}">+ Tambah dokumen</a>
            @endcan
        </div>
    </section>

    <section class="admin-panel">
        <header class="admin-panel__header qd-audit-header">
            <div><h2>Daftar Dokumen</h2><p>{{ $documents->total() }} dokumen tersedia.</p></div>
            <form method="GET" class="qd-audit-search">
                <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama atau keterangan...">
                <button class="button button--outline admin-button" type="submit">Cari</button>
                @if ($search !== '')<a href="{{ route('admin.quality-documents.audit.index') }}">Reset</a>@endif
            </form>
        </header>

        @if ($documents->isEmpty())
            <div class="admin-empty">
                <span><x-ui.icon name="file" /></span>
                <h2>{{ $search !== '' ? 'Dokumen tidak ditemukan' : 'Data Audit masih kosong' }}</h2>
                <p>{{ $search !== '' ? 'Coba gunakan kata pencarian yang lain.' : 'Tambahkan NIB, Company Profile, contoh Quotation, PO, atau dokumen pendukung lainnya.' }}</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap qd-audit-table">
                <thead><tr><th>Nama Dokumen</th><th>Keterangan</th><th>Terakhir Diperbarui</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($documents as $document)
                        <tr>
                            <td>
                                <strong>{{ $document->title }}</strong>
                                <small>{{ $document->file_name }} · {{ number_format(($document->file_size ?? 0) / 1024, 0, ',', '.') }} KB</small>
                            </td>
                            <td>{{ $document->description ?: 'Tidak ada keterangan.' }}</td>
                            <td>{{ $document->updated_at->translatedFormat('d M Y, H:i') }}<small>{{ $document->updater?->name ?? 'Pengguna tidak tersedia' }}</small></td>
                            <td>
                                <div class="admin-action-group">
                                    @if ($document->canPreview())
                                        <a class="admin-action-button admin-action-button--view" href="{{ route('admin.quality-documents.audit.preview', $document) }}" target="_blank" rel="noopener"><x-ui.icon name="eye" size="14" /> Lihat</a>
                                    @endif
                                    <a class="admin-action-button" href="{{ route('admin.quality-documents.audit.download', $document) }}"><x-ui.icon name="download" size="14" /> Unduh</a>
                                    @can('quality-documents.manage')
                                        <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.quality-documents.audit.edit', $document) }}"><x-ui.icon name="edit" size="14" /> Edit</a>
                                        <form method="POST" action="{{ route('admin.quality-documents.audit.destroy', $document) }}" data-confirm-dialog="delete-audit-document-{{ $document->id }}">
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
            <x-ui.pagination :paginator="$documents" />

            @can('quality-documents.manage')
                @foreach ($documents as $document)
                    <x-ui.confirmation
                        :id="'delete-audit-document-'.$document->id"
                        title="Hapus dokumen audit?"
                        confirm-label="Ya, hapus dokumen"
                    >
                        Dokumen <strong>{{ $document->title }}</strong> dan file <strong>{{ $document->file_name }}</strong> akan dihapus permanen.
                    </x-ui.confirmation>
                @endforeach
            @endcan
        @endif
    </section>
@endsection
