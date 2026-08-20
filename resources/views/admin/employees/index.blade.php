@extends('layouts.admin')

@section('title', 'Data Karyawan')
@section('eyebrow', 'Kepegawaian & SDM')
@section('heading', 'Data Karyawan')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Data dan Manajemen Karyawan</h1>
            <p>Kelola data profil staf, riwayat pendidikan, dan berkas digital kepegawaian.</p>
        </div>
        @can('employees.manage')
            <a class="button button--primary admin-button" href="{{ route('admin.employees.create') }}">+ Tambah Karyawan</a>
        @endcan
    </section>

    <section class="admin-stat-grid" aria-label="Ringkasan data karyawan">
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Total Karyawan</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="users" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['total']) }}</strong>
            <p>Seluruh staf dan tenaga kerja</p>
        </article>
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Karyawan Tetap</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="check-circle" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['permanent']) }}</strong>
            <p>Staf berstatus permanen</p>
        </article>
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Karyawan Kontrak</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="clipboard-check" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['contract']) }}</strong>
            <p>Staf berstatus kontrak kerja</p>
        </article>
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Magang / Lainnya</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="calendar" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['other']) }}</strong>
            <p>Magang, freelance, dan harian</p>
        </article>
    </section>

    <form class="admin-filter" method="GET" action="{{ route('admin.employees.index') }}">
        <label class="admin-field">
            <span>Cari karyawan</span>
            <input name="search" type="search" value="{{ request('search') }}" placeholder="Nama, NIK, kode, posisi, kontak...">
        </label>
        <label class="admin-field">
            <span>Status kerja</span>
            <select name="status">
                <option value="">Semua status</option>
                @foreach (\App\Models\Employee::EMPLOYMENT_STATUSES as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="admin-field">
            <span>Jenis Kelamin</span>
            <select name="gender">
                <option value="">Semua gender</option>
                @foreach (\App\Models\Employee::GENDERS as $val => $label)
                    <option value="{{ $val }}" @selected(request('gender') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="admin-actions">
            <button class="button button--primary admin-button" type="submit">Terapkan</button>
            <a class="button button--outline admin-button" href="{{ route('admin.employees.index') }}">Reset</a>
        </div>
    </form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar Karyawan</h2>
                <p>{{ $employees->total() }} data karyawan ditemukan.</p>
            </div>
        </header>

        @if ($employees->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true"><x-ui.icon name="users" size="28" /></span>
                <h2>Belum ada data karyawan</h2>
                <p>Mulai tambahkan profil karyawan pertama untuk mencatat riwayat pendidikan dan arsip dokumen.</p>
                @can('employees.manage')
                    <div style="margin-top: 14px">
                        <a class="button button--primary admin-button" href="{{ route('admin.employees.create') }}">+ Tambah Karyawan</a>
                    </div>
                @endcan
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Kode & Posisi</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Tgl Masuk</th>
                        <th>Arsip & Berkas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employee)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px">
                                    @if ($employee->photo_path)
                                        <img
                                            src="{{ $employee->photoUrl() }}"
                                            alt="{{ $employee->full_name }}"
                                            style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(0,0,0,0.1);"
                                        >
                                    @else
                                        <div style="width: 42px; height: 42px; border-radius: 50%; background: #e2e8f0; color: #475569; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px;">
                                            {{ strtoupper(substr($employee->full_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <strong style="display: block; font-size: 14px">{{ $employee->full_name }}</strong>
                                        <small style="color: #64748b">
                                            @if ($employee->identity_number)
                                                NIK: {{ $employee->identity_number }}
                                            @elseif ($employee->gender)
                                                {{ $employee->genderLabel() }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong>{{ $employee->position ?: '-' }}</strong>
                                <small style="display: block; color: #64748b">
                                    {{ $employee->employee_code ?: 'Tanpa Kode' }}
                                </small>
                            </td>
                            <td>
                                <div>{{ $employee->phone ?: '-' }}</div>
                                @if ($employee->emergency_contact_name)
                                    <small style="color: #64748b" title="Kontak Darurat: {{ $employee->emergency_contact_phone }}">
                                        Darurat: {{ $employee->emergency_contact_name }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <x-admin.status-badge :status="$employee->employment_status">
                                    {{ $employee->employmentStatusLabel() }}
                                </x-admin.status-badge>
                            </td>
                            <td>
                                {{ $employee->hire_date?->translatedFormat('d M Y') ?? '-' }}
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap">
                                    <span class="admin-badge admin-badge--neutral" style="font-size: 12px; padding: 2px 6px; border-radius: 4px; background: #f1f5f9; color: #475569;">
                                        {{ $employee->educations_count }} Pendidikan
                                    </span>
                                    <span class="admin-badge admin-badge--neutral" style="font-size: 12px; padding: 2px 6px; border-radius: 4px; background: #f1f5f9; color: #475569;">
                                        {{ $employee->documents_count }} Dokumen
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="admin-action-group">
                                    <a class="admin-action-button admin-action-button--view" href="{{ route('admin.employees.show', $employee) }}">
                                        <x-ui.icon name="eye" size="14" /> Detail
                                    </a>
                                    @can('employees.manage')
                                        <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.employees.edit', $employee) }}">
                                            <x-ui.icon name="edit" size="14" /> Edit
                                        </a>
                                        <button
                                            class="admin-action-button admin-action-button--delete"
                                            type="button"
                                            data-modal-open="delete-employee-{{ $employee->id }}"
                                        >
                                            <x-ui.icon name="trash" size="14" /> Hapus
                                        </button>

                                        <x-ui.modal id="delete-employee-{{ $employee->id }}" title="Hapus Data Karyawan">
                                            <div style="display: grid; gap: 14px">
                                                <p>Apakah Anda yakin ingin menghapus data karyawan <strong>{{ $employee->full_name }}</strong>?</p>
                                                <p style="color: #dc2626; font-size: 13px">Semua riwayat pendidikan dan berkas dokumen terkait juga akan dihapus secara permanen.</p>
                                                <div class="admin-actions" style="justify-content: flex-end; margin-top: 10px">
                                                    <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                                                    <form method="POST" action="{{ route('admin.employees.destroy', $employee) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="button button--danger admin-button" type="submit">Ya, Hapus Karyawan</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </x-ui.modal>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div style="padding: 16px 20px">
                <x-ui.pagination :paginator="$employees" />
            </div>
        @endif
    </section>
@endsection
