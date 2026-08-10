@extends('layouts.admin')

@section('title', 'Aktivitas')
@section('eyebrow', 'Konten publik')
@section('heading', 'Aktivitas')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Aktivitas Alpha Academy</h1>
            <p>Kelola dokumentasi kegiatan yang tampil di beranda dan halaman aktivitas.</p>
        </div>
        @can('activities.manage')
            <a class="button button--primary admin-button" href="{{ route('admin.activities.create') }}">+ Upload aktivitas</a>
        @endcan
    </section>

    <form class="admin-filter" method="GET" action="{{ route('admin.activities.index') }}" style="grid-template-columns: minmax(220px, 1fr) minmax(150px, .3fr) auto">
        <label class="admin-field">
            <span>Cari aktivitas</span>
            <input name="search" type="search" value="{{ request('search') }}" placeholder="Judul, kategori, atau ringkasan">
        </label>
        <label class="admin-field">
            <span>Status</span>
            <select name="status">
                <option value="">Semua status</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="published" @selected(request('status') === 'published')>Terbit</option>
                <option value="archived" @selected(request('status') === 'archived')>Diarsipkan</option>
            </select>
        </label>
        <div class="admin-actions">
            <button class="button button--primary admin-button" type="submit">Terapkan</button>
            <a class="button button--outline admin-button" href="{{ route('admin.activities.index') }}">Reset</a>
        </div>
    </form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar aktivitas</h2>
                <p>{{ $activities->total() }} aktivitas ditemukan.</p>
            </div>
        </header>

        @if ($activities->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true"><x-ui.icon name="file" /></span>
                <h2>Belum ada aktivitas</h2>
                <p>Upload dokumentasi pertama agar tampil di situs utama.</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead>
                    <tr>
                        <th>Aktivitas</th>
                        <th>Kategori</th>
                        <th>Publikasi</th>
                        <th>Dilihat</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activities as $activity)
                        <tr>
                            <td>
                                <div class="admin-activity-title">
                                    <img src="{{ $activity->imageUrl() }}" alt="">
                                    <span>
                                        <strong>{{ $activity->title }}</strong>
                                        <small>{{ Str::limit($activity->excerpt, 78) }}</small>
                                        @if ($activity->is_featured)
                                            <em>Aktivitas unggulan</em>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td>{{ $activity->category }}</td>
                            <td>
                                {{ $activity->published_at?->translatedFormat('d M Y, H:i') ?? 'Belum dijadwalkan' }}
                                <small>{{ $activity->author?->name ?? 'Admin tidak tersedia' }}</small>
                            </td>
                            <td>{{ number_format($activity->view_count, 0, ',', '.') }}</td>
                            <td>
                                @if ($activity->status === 'published' && $activity->published_at?->isFuture())
                                    <x-admin.status-badge status="pending">Terjadwal</x-admin.status-badge>
                                @else
                                    <x-admin.status-badge :status="$activity->status" />
                                @endif
                            </td>
                            <td>
                                <div class="admin-action-group">
                                    @can('activities.manage')
                                        <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.activities.edit', $activity) }}">
                                            <x-ui.icon name="edit" size="14" /> Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" data-confirm-dialog="delete-activity-{{ $activity->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="admin-action-button admin-action-button--delete" type="submit">
                                                <x-ui.icon name="trash" size="14" /> Hapus
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
            <x-ui.pagination :paginator="$activities" />
        @endif
    </section>

    @can('activities.manage')
        @foreach ($activities as $activity)
            <x-ui.confirmation
                :id="'delete-activity-'.$activity->id"
                title="Hapus aktivitas?"
                confirm-label="Ya, hapus aktivitas"
            >
                Aktivitas <strong>{{ $activity->title }}</strong> dan foto unggahannya akan dihapus permanen.
            </x-ui.confirmation>
        @endforeach
    @endcan
@endsection
