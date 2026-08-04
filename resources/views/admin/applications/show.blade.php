@extends('layouts.admin')

@section('title', 'Detail Pendaftaran')
@section('eyebrow', $application->registration_number)
@section('heading', 'Detail Pendaftaran')

@php
    $snapshot = $application->personal_data_snapshot ?? [];
    $profile = $application->user->participantProfile;
@endphp

@section('content')
    <section class="admin-page-heading">
        <div>
            <div style="margin-bottom: 10px"><x-admin.status-badge :status="$application->status" /></div>
            <h1>{{ $application->user->name }}</h1>
            <p>{{ $application->registration_number }} · Dikirim {{ $application->submitted_at?->translatedFormat('d F Y, H:i') ?? 'belum dikirim' }}</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.applications.index') }}">← Kembali ke daftar</a>
    </section>

    <div class="admin-detail-grid">
        <div style="display: grid; gap: 18px">
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Program dan batch pilihan</h2>
                        <p>Informasi pelatihan yang dipilih peserta.</p>
                    </div>
                </header>
                <dl class="admin-description-list">
                    <div>
                        <dt>Program</dt>
                        <dd>{{ $application->trainingProgram->title }}<br><small>{{ $application->trainingProgram->code }}</small></dd>
                    </div>
                    <div>
                        <dt>Batch</dt>
                        <dd>{{ $application->trainingBatch?->name ?? 'Belum ditentukan' }}<br><small>{{ $application->trainingBatch?->code }}</small></dd>
                    </div>
                    <div>
                        <dt>Jadwal</dt>
                        <dd>
                            {{ $application->trainingBatch?->start_date?->translatedFormat('d M Y') ?? 'Belum tersedia' }}
                            @if ($application->trainingBatch?->end_date)
                                – {{ $application->trainingBatch->end_date->translatedFormat('d M Y') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Biaya program</dt>
                        <dd>Rp {{ number_format($application->trainingProgram->price, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Data peserta</h2>
                        <p>Snapshot data pada saat pendaftaran dikirim.</p>
                    </div>
                </header>
                <dl class="admin-description-list">
                    <div>
                        <dt>Nama lengkap</dt>
                        <dd>{{ data_get($snapshot, 'full_name', $application->user->name) }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd>{{ $application->user->email }}</dd>
                    </div>
                    <div>
                        <dt>Nomor telepon</dt>
                        <dd>{{ data_get($snapshot, 'phone', $profile?->phone ?? 'Belum tersedia') }}</dd>
                    </div>
                    <div>
                        <dt>Tempat, tanggal lahir</dt>
                        <dd>
                            {{ data_get($snapshot, 'birth_place', $profile?->birth_place ?? 'Belum tersedia') }},
                            {{ data_get($snapshot, 'birth_date', $profile?->birth_date?->translatedFormat('d M Y') ?? 'Belum tersedia') }}
                        </dd>
                    </div>
                    <div>
                        <dt>Pendidikan terakhir</dt>
                        <dd>{{ data_get($snapshot, 'education', $profile?->last_education ?? 'Belum tersedia') }}</dd>
                    </div>
                    <div>
                        <dt>Kota / provinsi</dt>
                        <dd>{{ data_get($snapshot, 'city', $profile?->city ?? 'Belum tersedia') }} / {{ data_get($snapshot, 'province', $profile?->province ?? 'Belum tersedia') }}</dd>
                    </div>
                    <div class="admin-field--full">
                        <dt>Alamat</dt>
                        <dd>{{ data_get($snapshot, 'address', $profile?->address ?? 'Belum tersedia') }}</dd>
                    </div>
                    <div>
                        <dt>Kontak darurat</dt>
                        <dd>{{ data_get($snapshot, 'emergency_name', $profile?->emergency_contact_name ?? 'Belum tersedia') }}</dd>
                    </div>
                    <div>
                        <dt>Telepon darurat</dt>
                        <dd>{{ data_get($snapshot, 'emergency_phone', $profile?->emergency_contact_phone ?? 'Belum tersedia') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Dokumen persyaratan</h2>
                        <p>{{ $application->documents->count() }} dokumen terhubung ke pendaftaran ini.</p>
                    </div>
                </header>
                @if ($application->documents->isEmpty())
                    <div class="admin-empty" style="min-height: 170px">
                        <span aria-hidden="true">□</span>
                        <h2>Belum ada dokumen</h2>
                        <p>Dokumen peserta belum tersimpan pada pendaftaran ini.</p>
                    </div>
                @else
                    <div class="admin-document-grid">
                        @foreach ($application->documents as $document)
                            @php
                                $previewUrl = route('admin.applications.documents.preview', [$application, $document]);
                                $downloadUrl = route('admin.applications.documents.download', [$application, $document]);
                            @endphp
                            <article class="admin-document-card">
                                <div class="admin-document-card__preview">
                                    @if (! $document->file_exists)
                                        <div class="admin-document-card__placeholder admin-document-card__placeholder--missing">
                                            <x-ui.icon name="file" size="30" />
                                            <strong>File tidak ditemukan</strong>
                                        </div>
                                    @elseif ($document->isImage())
                                        <button
                                            type="button"
                                            data-modal-open="preview-document-{{ $document->id }}"
                                            aria-label="Lihat {{ $document->typeLabel() }}"
                                        >
                                            <img
                                                src="{{ $previewUrl }}"
                                                alt="Preview {{ $document->typeLabel() }} milik {{ $application->user->name }}"
                                                loading="lazy"
                                            >
                                        </button>
                                    @elseif ($document->isPdf())
                                        <button
                                            class="admin-document-card__placeholder"
                                            type="button"
                                            data-modal-open="preview-document-{{ $document->id }}"
                                            aria-label="Lihat PDF {{ $document->typeLabel() }}"
                                        >
                                            <x-ui.icon name="file" size="34" />
                                            <strong>Dokumen PDF</strong>
                                            <span>Klik untuk melihat</span>
                                        </button>
                                    @else
                                        <div class="admin-document-card__placeholder">
                                            <x-ui.icon name="file" size="34" />
                                            <strong>Preview tidak tersedia</strong>
                                        </div>
                                    @endif
                                </div>

                                <div class="admin-document-card__body">
                                    <div class="admin-document-card__heading">
                                        <div>
                                            <span>{{ $document->typeLabel() }}</span>
                                            <strong>{{ $document->original_name }}</strong>
                                        </div>
                                        <x-admin.status-badge :status="$document->status" />
                                    </div>
                                    <small>
                                        {{ $document->mime_type ?? 'Tipe tidak diketahui' }}
                                        · {{ $document->file_size ? number_format($document->file_size / 1024, 1).' KB' : 'Ukuran tidak diketahui' }}
                                    </small>
                                    <div class="admin-document-card__actions">
                                        @if ($document->file_exists && $document->isPreviewable())
                                            <button class="admin-action-button admin-action-button--view" type="button" data-modal-open="preview-document-{{ $document->id }}">
                                                <x-ui.icon name="eye" size="14" /> Lihat file
                                            </button>
                                        @endif
                                        @if ($document->file_exists)
                                            <a class="admin-action-button" href="{{ $downloadUrl }}">
                                                <x-ui.icon name="download" size="14" /> Unduh
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside style="display: grid; align-content: start; gap: 18px">
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Keputusan admin</h2>
                        <p>Peserta akan menerima notifikasi email.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    @if (in_array($application->status, ['submitted', 'under_review'], true))
                        @can('applications.approve')
                            <div class="admin-review-box">
                                <form
                                    method="POST"
                                    action="{{ route('admin.applications.review', $application) }}"
                                    data-confirm-dialog="approve-application"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input name="decision" type="hidden" value="approved">
                                    <label class="admin-field">
                                        <span>Catatan persetujuan (opsional)</span>
                                        <textarea name="notes" placeholder="Catatan untuk peserta atau tim internal"></textarea>
                                    </label>
                                    <button class="button button--primary admin-button" type="submit" style="margin-top: 10px; width: 100%">✓ Setujui pendaftaran</button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('admin.applications.review', $application) }}"
                                    data-confirm-dialog="reject-application"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input name="decision" type="hidden" value="rejected">
                                    <label class="admin-field">
                                        <span>Alasan penolakan</span>
                                        <textarea name="notes" placeholder="Jelaskan data atau dokumen yang perlu diperbaiki" required></textarea>
                                    </label>
                                    <button class="button button--primary ui-button--danger admin-button" type="submit" style="margin-top: 10px; width: 100%">Tolak pendaftaran</button>
                                </form>
                            </div>
                        @else
                            <x-ui.alert type="warning" title="Akses terbatas">
                                Anda dapat melihat detail, tetapi tidak memiliki izin untuk memberi keputusan.
                            </x-ui.alert>
                        @endcan
                    @else
                        <div class="admin-review-box">
                            <x-admin.status-badge :status="$application->status" />
                            <p style="color: var(--admin-muted); font-size: 10px; line-height: 1.65; margin: 0">
                                {{ $application->verification_notes ?: 'Tidak ada catatan dari admin.' }}
                            </p>
                            <small>
                                Diproses oleh {{ $application->verifier?->name ?? 'sistem' }}
                                {{ $application->verified_at?->diffForHumans() }}
                            </small>
                        </div>
                    @endif
                </div>
            </section>

            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Riwayat status</h2>
                        <p>Jejak perubahan pendaftaran.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <ol class="admin-timeline">
                        @forelse ($application->statusHistories->sortByDesc('created_at') as $history)
                            <li>
                                <strong>{{ str_replace('_', ' ', ucfirst($history->to_status)) }}</strong>
                                <small>{{ $history->actor?->name ?? 'Sistem' }} · {{ $history->created_at?->translatedFormat('d M Y, H:i') }}</small>
                                @if ($history->notes)
                                    <p>{{ $history->notes }}</p>
                                @endif
                            </li>
                        @empty
                            <li>
                                <strong>Pendaftaran dikirim</strong>
                                <small>{{ $application->submitted_at?->translatedFormat('d M Y, H:i') ?? 'Belum tersedia' }}</small>
                            </li>
                        @endforelse
                    </ol>
                </div>
            </section>
        </aside>
    </div>

    @foreach ($application->documents as $document)
        @if ($document->file_exists && $document->isPreviewable())
            @php
                $previewUrl = route('admin.applications.documents.preview', [$application, $document]);
                $downloadUrl = route('admin.applications.documents.download', [$application, $document]);
            @endphp
            <x-ui.modal
                id="preview-document-{{ $document->id }}"
                :title="$document->typeLabel()"
                :description="$document->original_name"
                size="large"
            >
                <div class="admin-document-viewer">
                    @if ($document->isImage())
                        <img
                            src="{{ $previewUrl }}"
                            alt="{{ $document->typeLabel() }} milik {{ $application->user->name }}"
                        >
                    @elseif ($document->isPdf())
                        <iframe
                            src="{{ $previewUrl }}#toolbar=1"
                            title="{{ $document->typeLabel() }} milik {{ $application->user->name }}"
                        ></iframe>
                    @endif
                </div>

                <x-slot:footer>
                    <button class="button button--secondary" type="button" data-modal-close>Tutup</button>
                    <a class="button button--secondary" href="{{ $previewUrl }}" target="_blank" rel="noopener">
                        <x-ui.icon name="eye" size="15" /> Buka tab baru
                    </a>
                    <a class="button button--primary" href="{{ $downloadUrl }}">
                        <x-ui.icon name="download" size="15" /> Unduh file
                    </a>
                </x-slot:footer>
            </x-ui.modal>
        @endif
    @endforeach

    @if (in_array($application->status, ['submitted', 'under_review'], true) && auth()->user()->can('applications.approve'))
        <x-ui.confirmation
            id="approve-application"
            title="Setujui pendaftaran?"
            confirm-label="Ya, setujui"
            tone="success"
        >
            <p>
                Pendaftaran {{ $application->registration_number }} akan disetujui.
                Peserta akan menerima notifikasi email dan dapat melanjutkan proses berikutnya.
            </p>
        </x-ui.confirmation>

        <x-ui.confirmation
            id="reject-application"
            title="Tolak pendaftaran?"
            confirm-label="Ya, tolak"
        >
            <p>
                Pendaftaran akan ditolak dan alasan yang Anda tuliskan akan dikirim kepada peserta.
            </p>
        </x-ui.confirmation>
    @endif
@endsection
