@extends('layouts.admin')

@section('title', 'Ringkasan Admin')
@section('eyebrow', 'Pusat kendali')
@section('heading', 'Dashboard Admin')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Selamat datang, {{ Str::before(auth()->user()->name, ' ') }}</h1>
            <p>Pantau pendaftaran peserta, program aktif, dan kapasitas batch dari satu tempat.</p>
        </div>
        <div class="admin-actions">
            @can('applications.view')
                <a class="button button--outline admin-button" href="{{ route('admin.applications.index', ['status' => 'submitted']) }}">Lihat antrean approval</a>
            @endcan
            @can('programs.manage')
                <a class="button button--primary admin-button" href="{{ route('admin.programs.create') }}">+ Tambah program</a>
            @endcan
        </div>
    </section>

    <section class="admin-stat-grid" aria-label="Ringkasan data">
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Total peserta</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="users" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['participants']) }}</strong>
            <p>Akun dengan role peserta</p>
        </article>
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Menunggu approval</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="clipboard-check" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['pendingApplications']) }}</strong>
            <p>Pendaftaran perlu diperiksa admin</p>
        </article>
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Program aktif</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="book-open" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['activePrograms']) }}</strong>
            <p>Program tampil dan dapat dipilih</p>
        </article>
        <article class="admin-stat">
            <div class="admin-stat__top">
                <small>Batch dibuka</small>
                <span class="admin-stat__icon" aria-hidden="true"><x-ui.icon name="calendar" size="21" /></span>
            </div>
            <strong>{{ number_format($stats['openBatches']) }}</strong>
            <p>Batch menerima pendaftaran</p>
        </article>
    </section>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Pendaftaran terbaru</h2>
                <p>Data terbaru yang sudah dikirim oleh calon peserta.</p>
            </div>
            @can('applications.view')
                <a class="admin-link" href="{{ route('admin.applications.index') }}">Lihat semua →</a>
            @endcan
        </header>

        @if ($recentApplications->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true">□</span>
                <h2>Belum ada pendaftaran</h2>
                <p>Pendaftaran peserta yang sudah dikirim akan muncul di bagian ini.</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                    <thead>
                        <tr>
                            <th>Peserta</th>
                            <th>Nomor pendaftaran</th>
                            <th>Program & batch</th>
                            <th>Dikirim</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentApplications as $application)
                            <tr>
                                <td>
                                    <strong>{{ $application->user->name }}</strong>
                                    <small>{{ $application->user->email }}</small>
                                </td>
                                <td><strong>{{ $application->registration_number }}</strong></td>
                                <td>
                                    <strong>{{ $application->trainingProgram->title }}</strong>
                                    <small>{{ $application->trainingBatch?->name ?? 'Batch belum ditentukan' }}</small>
                                </td>
                                <td>{{ $application->submitted_at?->translatedFormat('d M Y, H:i') ?? 'Belum tersedia' }}</td>
                                <td><x-admin.status-badge :status="$application->status" /></td>
                                <td>
                                    @can('applications.view')
                                        <a class="admin-action-button admin-action-button--view" href="{{ route('admin.applications.show', $application) }}">
                                            <x-ui.icon name="eye" size="14" /> Detail
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
            </x-ui.table>
        @endif
    </section>
@endsection
