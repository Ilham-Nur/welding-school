@extends('layouts.admin')

@section('title', 'Quality Record')
@section('eyebrow', 'Quality Documents')
@section('heading', 'Quality Record')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Quality Record</h1>
            <p>Kelola daftar record beserta file bukti, label, dan deskripsinya.</p>
        </div>
        @can('quality-documents.structure.manage')
            <a class="button button--primary admin-button" href="{{ route('admin.quality-records.create') }}">+ Tambah record</a>
        @endcan
    </section>

    <form class="admin-filter" method="GET">
        <label class="admin-field">
            <span>Cari Quality Record</span>
            <input type="search" name="search" value="{{ $search }}" placeholder="Nama atau deskripsi record">
        </label>
        <div class="admin-actions">
            <button class="button button--primary admin-button">Cari</button>
            <a class="button button--outline admin-button" href="{{ route('admin.quality-records.index') }}">Reset</a>
        </div>
    </form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div><h2>Daftar Quality Record</h2><p>{{ $records->total() }} record terdaftar.</p></div>
        </header>

        @if ($records->isEmpty())
            <div class="admin-empty">
                <span><x-ui.icon name="list" /></span>
                <h2>{{ $search !== '' ? 'Quality Record tidak ditemukan' : 'Belum ada Quality Record' }}</h2>
                <p>{{ $search !== '' ? 'Coba gunakan kata pencarian yang lain.' : 'Tambahkan record pertama untuk mulai menyimpan dokumen pendukung.' }}</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap qd-audit-table">
                <thead><tr><th>Nama Record</th><th>Deskripsi</th><th>Jumlah File</th><th>Terakhir Diperbarui</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr>
                            <td><strong>{{ $record->name }}</strong></td>
                            <td>{{ $record->description ?: 'Tidak ada deskripsi.' }}</td>
                            <td><strong>{{ $record->files_count }}</strong> file</td>
                            <td>
                                <div class="qd-audit-updated">
                                    <time datetime="{{ $record->updated_at->toIso8601String() }}">{{ $record->updated_at->translatedFormat('d M Y, H:i') }}</time>
                                    <small>Oleh <span>{{ $record->updater?->name ?? $record->creator?->name ?? 'Pengguna tidak tersedia' }}</span></small>
                                </div>
                            </td>
                            <td>
                                <div class="admin-action-group">
                                    <a class="admin-action-button admin-action-button--view" href="{{ route('admin.quality-records.show', $record) }}"><x-ui.icon name="eye" size="14" /> Detail</a>
                                    @can('quality-documents.structure.manage')
                                        <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.quality-records.edit', $record) }}"><x-ui.icon name="edit" size="14" /> Edit</a>
                                        <form method="POST" action="{{ route('admin.quality-records.destroy', $record) }}" data-confirm-dialog="delete-quality-record-{{ $record->id }}">
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
            <x-ui.pagination :paginator="$records" />

            @can('quality-documents.structure.manage')
                @foreach ($records as $record)
                    <x-ui.confirmation :id="'delete-quality-record-'.$record->id" title="Hapus Quality Record?" confirm-label="Ya, hapus record">
                        Record <strong>{{ $record->name }}</strong> dan seluruh <strong>{{ $record->files_count }} file</strong> di dalamnya akan dihapus permanen.
                    </x-ui.confirmation>
                @endforeach
            @endcan
        @endif
    </section>
@endsection
