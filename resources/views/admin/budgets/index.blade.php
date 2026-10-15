@extends('layouts.admin')

@section('title', 'Anggaran / RAB')
@section('heading', 'Anggaran / RAB')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Anggaran & harga pelatihan</h1>
            <p>Satu RAB untuk satu atau beberapa batch dalam program yang sama.</p>
        </div>
        @can('budgets.manage')
            <a class="button button--primary admin-button" href="{{ route('admin.budgets.create') }}">+ Susun RAB</a>
        @endcan
    </section>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar RAB</h2>
                <p>{{ $budgets->total() }} RAB pelatihan ditemukan.</p>
            </div>
        </header>

        <x-ui.table class="admin-table-wrap">
            <thead>
                <tr>
                    <th scope="col">RAB</th>
                    <th scope="col">Program</th>
                    <th scope="col">Batch</th>
                    <th scope="col">Status</th>
                    <th scope="col">Harga final / peserta</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($budgets as $budget)
                    <tr>
                        <td>
                            <strong>{{ $budget->name }}</strong>
                            <small>Versi {{ $budget->revision }}</small>
                        </td>
                        <td>
                            <strong>{{ $budget->trainingProgram->title }}</strong>
                            <small>{{ $budget->trainingProgram->code }}</small>
                        </td>
                        <td>
                            <strong>{{ count($budget->inputs['batches']) }} rencana</strong>
                            <small>{{ $budget->batches_count }} batch terbentuk</small>
                        </td>
                        <td>
                            <x-admin.status-badge :status="$budget->status">{{ \App\Models\TrainingBudget::STATUSES[$budget->status] }}</x-admin.status-badge>
                        </td>
                        <td>
                            @if ($budget->approved_price !== null)
                                <strong>Rp {{ number_format($budget->approved_price, 0, ',', '.') }}</strong>
                                <small>Disetujui pada versi {{ $budget->approved_revision }}</small>
                            @else
                                <small>Belum disetujui</small>
                            @endif
                        </td>
                        <td>
                            <div class="admin-action-group">
                                <a class="admin-action-button admin-action-button--view" href="{{ route('admin.budgets.show', $budget) }}">
                                    <x-ui.icon name="eye" size="14" /> Detail
                                </a>
                                @can('budgets.manage')
                                    @if (in_array($budget->status, ['draft', 'revision']))
                                        <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.budgets.edit', $budget) }}">
                                            <x-ui.icon name="edit" size="14" /> Edit
                                        </a>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="admin-empty">
                                <span aria-hidden="true">▦</span>
                                <h2>Belum ada RAB</h2>
                                <p>Susun RAB dan tentukan rencana batch. Batch terbentuk setelah persetujuan.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <x-ui.pagination :paginator="$budgets" />
    </section>
@endsection
