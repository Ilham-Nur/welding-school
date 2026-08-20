@extends('layouts.admin')
@section('title', 'Pinjaman Keluar')
@section('eyebrow', 'Storage')
@section('heading', 'Pinjaman Keluar')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Pinjaman aset ke luar area</h1>
            <p>Hanya dokumentasikan aset yang meninggalkan area pengawasan. Pemakaian alat harian di workshop tidak perlu dicatat.</p>
        </div>

        @can('storage.loans.manage')
            <a class="button button--primary admin-button" href="{{ route('admin.storage.loans.create') }}">+ Catat pinjaman</a>
        @endcan
    </section>

    <form class="admin-filter" method="GET">
        <label class="admin-field">
            <span>Cari pinjaman</span>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Nomor, aset, atau peminjam">
        </label>

        <label class="admin-field">
            <span>Status</span>
            <select name="status">
                <option value="">Semua</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="returned" @selected(request('status') === 'returned')>Dikembalikan</option>
            </select>
        </label>

        <div class="admin-actions">
            <button class="button button--primary admin-button">Terapkan</button>
            <a class="button button--outline admin-button" href="{{ route('admin.storage.loans.index') }}">Reset</a>
        </div>
    </form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Riwayat pinjaman</h2>
                <p>{{ $loans->total() }} transaksi ditemukan.</p>
            </div>
        </header>

        @if($loans->isEmpty())
            <div class="admin-empty">
                <span><x-ui.icon name="asset" /></span>
                <h2>Belum ada pinjaman keluar</h2>
                <p>Aset yang digunakan di area internal tidak perlu masuk ke daftar ini.</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead>
                    <tr>
                        <th>Pinjaman</th>
                        <th>Aset</th>
                        <th>Karyawan</th>
                        <th>Periode</th>
                        <th>Kondisi</th>
                        <th>Status / Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                        <tr>
                            <td>
                                <strong>{{ $loan->number }}</strong>
                                <small>{{ $loan->loaned_at->format('d M Y H:i') }}</small>
                            </td>
                            <td>
                                <strong>{{ $loan->items->count() }} aset</strong>
                                <small>{{ $loan->items->pluck('asset.equipment_name')->filter()->join(', ') }}</small>
                            </td>
                            <td>
                                <strong>{{ $loan->borrower?->name ?? $loan->borrower_name }}</strong>
                                <small>{{ $loan->purpose }}</small>
                            </td>
                            <td>
                                <strong>{{ $loan->due_at ? 'Rencana '.$loan->due_at->format('d M Y H:i') : 'Tidak dijadwalkan' }}</strong>
                                <small>{{ $loan->returned_at ? 'Kembali '.$loan->returned_at->format('d M Y H:i') : 'Keluar '.$loan->loaned_at->format('d M Y H:i') }}</small>
                            </td>
                            <td>
                                {{ \App\Models\Asset::CONDITIONS[$loan->condition_out] ?? $loan->condition_out }}
                                <small>{{ $loan->condition_in ? 'Kembali: '.(\App\Models\Asset::CONDITIONS[$loan->condition_in] ?? $loan->condition_in) : 'Belum kembali' }}</small>
                            </td>
                            <td>
                                <div class="storage-loan-status-actions">
                                    @if($loan->isOverdue())
                                        <x-admin.status-badge status="rejected">Terlambat</x-admin.status-badge>
                                    @else
                                        <x-admin.status-badge :status="$loan->status" />
                                    @endif

                                    @can('storage.loans.manage')
                                        @if($loan->status === 'active')
                                            <button
                                                class="button button--outline admin-button storage-return-trigger"
                                                type="button"
                                                data-modal-open="loan-return-{{ $loan->id }}"
                                            >Catat kembali</button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$loans" />
        @endif
    </section>

    @can('storage.loans.manage')
        @foreach($loans as $loan)
            @if($loan->status === 'active')
                @php
                    $returnFormId = 'loan-return-form-'.$loan->id;
                    $returnModalHasErrors = (int) old('_loan_id') === $loan->id
                        && ($errors->has('returned_at') || $errors->has('condition_in') || $errors->has('return_notes') || $errors->has('loan'));
                @endphp

                <x-ui.modal
                    :id="'loan-return-'.$loan->id"
                    title="Catat pengembalian aset"
                    :description="$loan->number"
                    size="medium"
                >
                    <div class="storage-loan-return-summary">
                        <article>
                            <span>Aset</span>
                            <strong>{{ $loan->items->count() }} aset</strong>
                            <small>{{ $loan->items->pluck('asset.equipment_name')->filter()->join(', ') }}</small>
                        </article>
                        <article>
                            <span>Karyawan</span>
                            <strong>{{ $loan->borrower?->name ?? $loan->borrower_name }}</strong>
                            <small>{{ $loan->purpose }}</small>
                        </article>
                    </div>

                    <form
                        id="{{ $returnFormId }}"
                        class="storage-loan-return-form"
                        method="POST"
                        action="{{ route('admin.storage.loans.return', $loan) }}"
                        @if($returnModalHasErrors) data-loan-return-errors @endif
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_loan_id" value="{{ $loan->id }}">

                        @if($returnModalHasErrors)
                            <p class="ui-field__error storage-loan-return-error">{{ $errors->first() }}</p>
                        @endif

                        <div class="admin-form-grid">
                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Waktu kembali <em>Wajib</em></span>
                                <input
                                    type="datetime-local"
                                    name="returned_at"
                                    value="{{ $returnModalHasErrors ? old('returned_at') : now()->format('Y-m-d\TH:i') }}"
                                    required
                                >
                            </label>

                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Kondisi kembali <em>Wajib</em></span>
                                <select name="condition_in" required>
                                    @foreach(\App\Models\Asset::CONDITIONS as $value => $label)
                                        <option value="{{ $value }}" @selected($returnModalHasErrors && old('condition_in') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="ui-field admin-field admin-field--full">
                                <span class="ui-field__label">Catatan pengembalian</span>
                                <textarea name="return_notes" rows="3" maxlength="2000" placeholder="Catat kerusakan atau informasi penting bila ada">{{ $returnModalHasErrors ? old('return_notes') : '' }}</textarea>
                            </label>
                        </div>
                    </form>

                    <x-slot:footer>
                        <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                        <button class="button button--primary admin-button" type="submit" form="{{ $returnFormId }}">Simpan pengembalian</button>
                    </x-slot:footer>
                </x-ui.modal>
            @endif
        @endforeach
    @endcan
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/storage.css') }}?v={{ filemtime(public_path('templates/welding-school/storage.css')) }}">
@endpush

@push('scripts')
    @vite('resources/js/storage.js')
@endpush
