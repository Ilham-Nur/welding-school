@extends('layouts.admin')

@section('title', 'Approval Pendaftaran')
@section('eyebrow', 'Pendaftaran peserta')
@section('heading', 'Approval Pendaftaran')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Approval pendaftaran</h1>
            <p>Periksa data peserta, program, batch, dan dokumen sebelum memberikan keputusan.</p>
        </div>
    </section>

    <form class="admin-filter" method="GET" action="{{ route('admin.applications.index') }}" style="grid-template-columns: minmax(240px, 1fr) minmax(170px, .35fr) auto">
        <label class="admin-field">
            <span>Cari pendaftaran</span>
            <input name="search" type="search" value="{{ request('search') }}" placeholder="Nomor, nama, email, atau program">
        </label>
        <label class="admin-field">
            <span>Status</span>
            <select name="status">
                <option value="">Semua status</option>
                <option value="submitted" @selected(request('status') === 'submitted')>Menunggu review</option>
                <option value="under_review" @selected(request('status') === 'under_review')>Dalam review</option>
                <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
            </select>
        </label>
        <div class="admin-actions">
            <button class="button button--primary admin-button" type="submit">Terapkan</button>
            <a class="button button--outline admin-button" href="{{ route('admin.applications.index') }}">Reset</a>
        </div>
    </form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar pendaftaran</h2>
                <p>{{ $applications->total() }} pendaftaran ditemukan.</p>
            </div>
        </header>

        @if ($applications->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true">✓</span>
                <h2>Tidak ada pendaftaran</h2>
                <p>Pendaftaran yang sudah dikirim peserta akan masuk ke antrean ini.</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                    <thead>
                        <tr>
                            <th>Peserta</th>
                            <th>Nomor pendaftaran</th>
                            <th>Program & batch</th>
                            <th>Dikirim</th>
                            <th>Diproses oleh</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td>
                                    <strong>{{ $application->user->name }}</strong>
                                    <small>{{ $application->user->email }}</small>
                                </td>
                                <td><strong>{{ $application->registration_number }}</strong></td>
                                <td>
                                    <strong>{{ $application->trainingProgram->title }}</strong>
                                    <small>{{ $application->trainingBatch?->name ?? 'Batch belum dipilih' }}</small>
                                </td>
                                <td>{{ $application->submitted_at?->translatedFormat('d M Y, H:i') ?? 'Belum tersedia' }}</td>
                                <td>{{ $application->verifier?->name ?? 'Belum diproses' }}</td>
                                <td><x-admin.status-badge :status="$application->status" /></td>
                                <td>
                                    <a class="admin-action-button admin-action-button--view" href="{{ route('admin.applications.show', $application) }}">
                                        <x-ui.icon name="eye" size="14" />
                                        {{ in_array($application->status, ['submitted', 'under_review'], true) ? 'Periksa' : 'Detail' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
            </x-ui.table>
            <x-ui.pagination :paginator="$applications" />
        @endif
    </section>
@endsection
