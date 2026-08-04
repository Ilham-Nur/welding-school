@extends('layouts.admin')

@section('title', 'Program Pelatihan')
@section('eyebrow', 'Katalog pelatihan')
@section('heading', 'Program Pelatihan')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Program pelatihan</h1>
            <p>Kelola kode, kategori, durasi, harga, tanggal mulai, dan status publikasi program.</p>
        </div>
        @can('programs.manage')
            <a class="button button--primary admin-button" href="{{ route('admin.programs.create') }}">+ Tambah program</a>
        @endcan
    </section>

    <form class="admin-filter" method="GET" action="{{ route('admin.programs.index') }}" style="grid-template-columns: minmax(220px, 1fr) minmax(150px, .3fr) auto">
        <label class="admin-field">
            <span>Cari program</span>
            <input name="search" type="search" value="{{ request('search') }}" placeholder="Kode, nama, atau kategori">
        </label>
        <label class="admin-field">
            <span>Status</span>
            <select name="status">
                <option value="">Semua status</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="closed" @selected(request('status') === 'closed')>Ditutup</option>
            </select>
        </label>
        <div class="admin-actions">
            <button class="button button--primary admin-button" type="submit">Terapkan</button>
            <a class="button button--outline admin-button" href="{{ route('admin.programs.index') }}">Reset</a>
        </div>
    </form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar program</h2>
                <p>{{ $programs->total() }} program pelatihan ditemukan.</p>
            </div>
        </header>

        @if ($programs->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true">◇</span>
                <h2>Program tidak ditemukan</h2>
                <p>Tambahkan program baru atau ubah filter pencarian.</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Kategori</th>
                            <th>Durasi</th>
                            <th>Biaya</th>
                            <th>Batch / Pendaftar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($programs as $program)
                            @php
                                $programDeleteBlocked = $program->batches_count > 0
                                    || $program->applications_count > 0
                                    || $program->enrollments_count > 0;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $program->title }}</strong>
                                    <small>{{ $program->code }} · Mulai {{ $program->start_date?->translatedFormat('d M Y') ?? 'belum ditentukan' }}</small>
                                </td>
                                <td>{{ $program->category }}</td>
                                <td>{{ number_format($program->duration_hours) }} jam</td>
                                <td>Rp {{ number_format($program->price, 0, ',', '.') }}</td>
                                <td>{{ $program->batches_count }} batch · {{ $program->applications_count }} pendaftar</td>
                                <td><x-admin.status-badge :status="$program->status" /></td>
                                <td>
                                    <div class="admin-action-group">
                                        <button class="admin-action-button admin-action-button--view" type="button" data-modal-open="detail-program-{{ $program->id }}">
                                            <x-ui.icon name="eye" size="14" /> Detail
                                        </button>
                                        @can('programs.manage')
                                            <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.programs.edit', $program) }}">
                                                <x-ui.icon name="edit" size="14" /> Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.programs.destroy', $program) }}" data-confirm-dialog="delete-program-{{ $program->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="admin-action-button admin-action-button--delete"
                                                    type="submit"
                                                    @disabled($programDeleteBlocked)
                                                    title="{{ $programDeleteBlocked ? 'Program memiliki data terkait dan tidak dapat dihapus' : 'Hapus program' }}"
                                                >
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
            <x-ui.pagination :paginator="$programs" />
        @endif
    </section>

    @foreach ($programs as $program)
        @php
            $programDeleteBlocked = $program->batches_count > 0
                || $program->applications_count > 0
                || $program->enrollments_count > 0;
        @endphp

        <x-ui.modal :id="'detail-program-'.$program->id" title="Detail program pelatihan" :description="$program->code">
            <dl class="admin-modal-details">
                <div>
                    <dt>Nama program</dt>
                    <dd>{{ $program->title }}</dd>
                </div>
                <div>
                    <dt>Kategori</dt>
                    <dd>{{ $program->category }}</dd>
                </div>
                <div>
                    <dt>Durasi</dt>
                    <dd>{{ number_format($program->duration_hours) }} jam</dd>
                </div>
                <div>
                    <dt>Biaya</dt>
                    <dd>Rp {{ number_format($program->price, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt>Tanggal mulai</dt>
                    <dd>{{ $program->start_date?->translatedFormat('d M Y') ?? 'Belum ditentukan' }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd><x-admin.status-badge :status="$program->status" /></dd>
                </div>
                <div>
                    <dt>Batch</dt>
                    <dd>{{ $program->batches_count }} batch</dd>
                </div>
                <div>
                    <dt>Pendaftar</dt>
                    <dd>{{ $program->applications_count }} pendaftar</dd>
                </div>
            </dl>
            <x-slot:footer>
                <button class="button button--outline admin-button" type="button" data-modal-close>Tutup</button>
                @can('programs.manage')
                    <a class="button button--primary admin-button" href="{{ route('admin.programs.edit', $program) }}">Edit program</a>
                @endcan
            </x-slot:footer>
        </x-ui.modal>

        @can('programs.manage')
            @unless ($programDeleteBlocked)
                <x-ui.confirmation
                    :id="'delete-program-'.$program->id"
                    title="Hapus program pelatihan?"
                    confirm-label="Ya, hapus program"
                >
                    Program <strong>{{ $program->title }}</strong> akan dihapus permanen.
                </x-ui.confirmation>
            @endunless
        @endcan
    @endforeach
@endsection
