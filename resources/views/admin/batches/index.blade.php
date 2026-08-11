@extends('layouts.admin')

@section('title', 'Batch Pelatihan')
@section('eyebrow', 'Jadwal pelatihan')
@section('heading', 'Batch Pelatihan')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Batch pelatihan</h1>
            <p>Atur periode pendaftaran, jadwal pelaksanaan, kapasitas, dan status setiap batch.</p>
        </div>
        @can('batches.manage')
            <a class="button button--primary admin-button" href="{{ route('admin.batches.create') }}">+ Tambah batch</a>
        @endcan
    </section>

    <form class="admin-filter" method="GET" action="{{ route('admin.batches.index') }}" style="grid-template-columns: minmax(220px, 1fr) minmax(150px, .3fr) auto">
        <label class="admin-field">
            <span>Cari batch</span>
            <input name="search" type="search" value="{{ request('search') }}" placeholder="Kode, nama batch, atau program">
        </label>
        <label class="admin-field">
            <span>Status</span>
            <select name="status">
                <option value="">Semua status</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="open" @selected(request('status') === 'open')>Dibuka</option>
                <option value="closed" @selected(request('status') === 'closed')>Ditutup</option>
                <option value="completed" @selected(request('status') === 'completed')>Selesai</option>
            </select>
        </label>
        <div class="admin-actions">
            <button class="button button--primary admin-button" type="submit">Terapkan</button>
            <a class="button button--outline admin-button" href="{{ route('admin.batches.index') }}">Reset</a>
        </div>
    </form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar batch</h2>
                <p>{{ $batches->total() }} batch pelatihan ditemukan.</p>
            </div>
        </header>

        @if ($batches->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true">▦</span>
                <h2>Batch tidak ditemukan</h2>
                <p>Tambahkan batch baru atau ubah filter pencarian.</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                    <thead>
                        <tr>
                            <th>Batch</th>
                            <th>Program</th>
                            <th>Periode</th>
                            <th>Kapasitas</th>
                            <th>Pendaftar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batches as $batch)
                            @php
                                $batchDeleteBlocked = $batch->applications_count > 0
                                    || $batch->enrollments_count > 0;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $batch->name }}</strong>
                                    <small>{{ $batch->code }}</small>
                                </td>
                                <td>
                                    <strong>{{ $batch->trainingProgram->title }}</strong>
                                    <small>{{ $batch->trainingProgram->code }}</small>
                                </td>
                                <td>
                                    <strong>{{ $batch->start_date->translatedFormat('d M Y') }}</strong>
                                    <small>s.d. {{ $batch->end_date?->translatedFormat('d M Y') ?? 'belum ditentukan' }}</small>
                                </td>
                                <td>{{ $batch->capacity }} peserta</td>
                                <td>{{ $batch->applications_count }} pendaftar</td>
                                <td><x-admin.status-badge :status="$batch->status" /></td>
                                <td>
                                    <div class="admin-action-group">
                                        <button class="admin-action-button admin-action-button--view" type="button" data-modal-open="detail-batch-{{ $batch->id }}">
                                            <x-ui.icon name="eye" size="14" /> Detail
                                        </button>
                                        @can('batches.manage')
                                            <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.batches.edit', $batch) }}">
                                                <x-ui.icon name="edit" size="14" /> Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.batches.destroy', $batch) }}" data-confirm-dialog="delete-batch-{{ $batch->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="admin-action-button admin-action-button--delete"
                                                    type="submit"
                                                    @disabled($batchDeleteBlocked)
                                                    title="{{ $batchDeleteBlocked ? 'Batch memiliki data terkait dan tidak dapat dihapus' : 'Hapus batch' }}"
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
            <x-ui.pagination :paginator="$batches" />
        @endif
    </section>

    @foreach ($batches as $batch)
        @php
            $batchDeleteBlocked = $batch->applications_count > 0
                || $batch->enrollments_count > 0;
        @endphp

        <x-ui.modal :id="'detail-batch-'.$batch->id" title="Detail batch pelatihan" :description="$batch->code">
            <dl class="admin-modal-details">
                <div>
                    <dt>Nama batch</dt>
                    <dd>{{ $batch->name }}</dd>
                </div>
                <div>
                    <dt>Program</dt>
                    <dd>{{ $batch->trainingProgram->title }}</dd>
                </div>
                <div>
                    <dt>Batas pendaftaran</dt>
                    <dd>{{ $batch->registration_deadline?->translatedFormat('d M Y') ?? 'Belum ditentukan' }}</dd>
                </div>
                <div>
                    <dt>Periode pelatihan</dt>
                    <dd>
                        {{ $batch->start_date->translatedFormat('d M Y') }}
                        sampai {{ $batch->end_date?->translatedFormat('d M Y') ?? 'Belum ditentukan' }}
                    </dd>
                </div>
                <div>
                    <dt>Kapasitas</dt>
                    <dd>{{ number_format($batch->capacity) }} peserta</dd>
                </div>
                <div>
                    <dt>Pendaftar</dt>
                    <dd>{{ $batch->applications_count }} pendaftar</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd><x-admin.status-badge :status="$batch->status" /></dd>
                </div>
                <div>
                    <dt>Kode program</dt>
                    <dd>{{ $batch->trainingProgram->code }}</dd>
                </div>
            </dl>
            <x-slot:footer>
                <button class="button button--outline admin-button" type="button" data-modal-close>Tutup</button>
                @can('batches.manage')
                    <a class="button button--primary admin-button" href="{{ route('admin.batches.edit', $batch) }}">Edit batch</a>
                @endcan
            </x-slot:footer>
        </x-ui.modal>

        @can('batches.manage')
            @unless ($batchDeleteBlocked)
                <x-ui.confirmation
                    :id="'delete-batch-'.$batch->id"
                    title="Hapus batch pelatihan?"
                    confirm-label="Ya, hapus batch"
                >
                    Batch <strong>{{ $batch->name }}</strong> akan dihapus permanen.
                </x-ui.confirmation>
            @endunless
        @endcan
    @endforeach
@endsection
