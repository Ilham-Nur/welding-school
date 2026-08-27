@extends('layouts.admin')

@section('title', $document->document_code)
@section('eyebrow', 'Quality Documents · '.$standard->name)
@section('heading', $document->document_code)

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $document->document_code }} · {{ $document->title }}</h1>
            <p>{{ $document->category->name }} · Rev. {{ str_pad((string) $document->currentRevisionNumber(), 2, '0', STR_PAD_LEFT) }}</p>
        </div>
        <div class="admin-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.quality-documents.standards.show', ['standard' => $standard, 'tab' => match ($document->category->code) { 'MM' => 'manual-mutu', 'QP' => 'quality-procedure', 'IK' => 'working-instruction', 'F' => 'form', default => 'review' }]) }}">Kembali</a>
            @can('quality-documents.manage')
                <a class="button button--outline admin-button" href="{{ route('admin.quality-documents.documents.edit', [$standard, $document]) }}">Edit informasi</a>
                @if ($document->status === 'draft')
                    <form method="POST" action="{{ route('admin.quality-documents.documents.publish', [$standard, $document]) }}" data-confirm-dialog="publish-quality-document-{{ $document->id }}">@csrf<button class="button button--primary admin-button" type="submit">Terbitkan Dokumen</button></form>
                    <form method="POST" action="{{ route('admin.quality-documents.documents.destroy', [$standard, $document]) }}" data-confirm-dialog="delete-quality-document-{{ $document->id }}">@csrf @method('DELETE')<button class="button button--danger admin-button" type="submit">Hapus Draft</button></form>
                @elseif ($document->status === 'active')
                    <a class="button button--primary admin-button" href="{{ route('admin.quality-documents.documents.revise', [$standard, $document]) }}">+ Buat Revisi</a>
                    <form method="POST" action="{{ route('admin.quality-documents.documents.archive', [$standard, $document]) }}" data-confirm-dialog="archive-quality-document-{{ $document->id }}">@csrf<button class="button button--outline admin-button" type="submit">Arsipkan</button></form>
                @endif
            @endcan
        </div>
    </section>

    <div class="qd-document-meta">
        <article><span>Status</span><x-admin.status-badge :status="$document->status" /></article>
        <article><span>Revisi aktif</span><strong>Rev. {{ str_pad((string) $document->currentRevisionNumber(), 2, '0', STR_PAD_LEFT) }}</strong></article>
        <article><span>Tanggal berlaku</span><strong>{{ $document->effective_date?->translatedFormat('d M Y') ?? 'Belum ditentukan' }}</strong></article>
        <article><span>Bab terkait</span><strong>{{ $document->sections->count() ?: 'Tidak ada' }}</strong></article>
    </div>

    <section class="admin-panel qd-preview-panel">
        <header class="admin-panel__header">
            <div><h2>Preview dokumen aktif</h2><p>{{ $document->currentOriginalName() }} · {{ number_format(($latestRevision?->original_file_size ?? $document->original_file_size) / 1024, 0, ',', '.') }} KB</p></div>
            @can('quality-documents.manage')
                <a class="button button--outline admin-button" href="{{ route('admin.quality-documents.documents.download', [$standard, $document]) }}"><x-ui.icon name="download" size="15" /> Download asli</a>
            @endcan
        </header>
        @if ($document->currentPreviewPath())
            <iframe src="{{ route('admin.quality-documents.documents.preview', [$standard, $document]) }}" title="Preview {{ $document->document_code }}"></iframe>
        @else
            <div class="admin-empty"><span><x-ui.icon name="file" /></span><h2>Preview PDF belum tersedia</h2><p>@can('quality-documents.manage') File asli tetap dapat diunduh. Unggah PDF preview pada revisi berikutnya jika konversi otomatis tidak tersedia. @else Hubungi pengelola dokumen untuk membuka file asli. @endcan</p></div>
        @endif
    </section>

    @if ($previousRevision)
        <section class="admin-panel qd-compare-panel">
            <header class="admin-panel__header">
                <div><h2>Perbandingan revisi</h2><p>Bandingkan revisi aktif dengan satu revisi sebelumnya.</p></div>
            </header>
            <div class="qd-compare-grid">
                <article>
                    <header><strong>Rev. {{ str_pad((string) $latestRevision->revision_number, 2, '0', STR_PAD_LEFT) }}</strong><small>Revisi aktif</small></header>
                    @if ($latestRevision->preview_file_path)<iframe src="{{ route('admin.quality-documents.revisions.preview', $latestRevision) }}" title="Revisi aktif"></iframe>@else<div class="qd-no-preview">Preview tidak tersedia</div>@endif
                </article>
                <article>
                    <header><strong>Rev. {{ str_pad((string) $previousRevision->revision_number, 2, '0', STR_PAD_LEFT) }}</strong><small>Revisi pembanding</small></header>
                    @if ($previousRevision->preview_file_path)<iframe src="{{ route('admin.quality-documents.revisions.preview', $previousRevision) }}" title="Revisi sebelumnya"></iframe>@else<div class="qd-no-preview">Preview tidak tersedia</div>@endif
                </article>
            </div>
        </section>
    @endif

    <div class="qd-detail-grid">
        <section class="admin-panel">
            <header class="admin-panel__header"><div><h2>Informasi dokumen</h2><p>Metadata dokumen aktif.</p></div></header>
            <div class="admin-panel__body qd-description-list">
                <div><span>Standar</span><strong>{{ $standard->name }}</strong></div>
                <div><span>Kategori</span><strong>{{ $document->category->name }}</strong></div>
                <div><span>Deskripsi</span><p>{{ $document->description ?: 'Tidak ada deskripsi.' }}</p></div>
                <div><span>Bab terkait</span>
                    @if ($document->sections->isEmpty())<p>Tidak menggunakan relasi bab.</p>@else<div class="qd-section-chips">@foreach ($document->sections as $section)<span>{{ $section->chapter_number }} · {{ $section->title }}</span>@endforeach</div>@endif
                </div>
            </div>
        </section>

        <section class="admin-panel">
            <header class="admin-panel__header"><div><h2>Histori revisi</h2><p>{{ $document->revisions->count() }} versi tersimpan.</p></div></header>
            <x-ui.table class="admin-table-wrap qd-history-table">
                <thead><tr><th>Revisi</th><th>Tanggal</th><th>Catatan</th><th>File</th></tr></thead>
                <tbody>
                    @foreach ($document->revisions as $revision)
                        <tr>
                            <td><strong>Rev. {{ str_pad((string) $revision->revision_number, 2, '0', STR_PAD_LEFT) }}</strong>@if ($revision->revision_number === $document->currentRevisionNumber())<small>Aktif</small>@endif</td>
                            <td>
                                <div class="qd-history-date">
                                    <time datetime="{{ ($revision->effective_date ?? $revision->created_at)->format('Y-m-d') }}">{{ $revision->effective_date?->translatedFormat('d M Y') ?? $revision->created_at->translatedFormat('d M Y') }}</time>
                                    <small>Oleh <span>{{ $revision->creator?->name ?? 'Pengguna tidak tersedia' }}</span></small>
                                </div>
                            </td>
                            <td>{{ $revision->notes ?: 'Tidak ada catatan.' }}</td>
                            <td><div class="admin-action-group">
                                @if ($revision->preview_file_path)<a class="admin-action-button admin-action-button--view" href="{{ route('admin.quality-documents.revisions.preview', $revision) }}" target="_blank"><x-ui.icon name="eye" size="14" /> PDF</a>@endif
                                @can('quality-documents.manage')
                                    <a class="admin-action-button" href="{{ route('admin.quality-documents.revisions.download', $revision) }}"><x-ui.icon name="download" size="14" /> Asli</a>
                                @endcan
                                @if (! $revision->preview_file_path && ! auth()->user()->can('quality-documents.manage'))<small>Tidak tersedia</small>@endif
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </section>
    </div>

    <section class="admin-panel qd-activity-panel">
        <header class="admin-panel__header"><div><h2>Aktivitas dokumen</h2><p>Catatan koreksi, penerbitan, revisi, dan pengarsipan.</p></div></header>
        @if ($document->activityLogs->isEmpty())
            <div class="admin-empty admin-empty--compact"><span><x-ui.icon name="list" /></span><h2>Belum ada aktivitas</h2></div>
        @else
            <div class="admin-panel__body qd-activity-list">
                @foreach ($document->activityLogs as $activity)
                    <article><span></span><div><strong>{{ $activity->description }}</strong><small>{{ $activity->creator?->name ?? 'Pengguna tidak tersedia' }} · {{ $activity->created_at->translatedFormat('d M Y, H:i') }}</small></div></article>
                @endforeach
            </div>
        @endif
    </section>

    @can('quality-documents.manage')
        @if ($document->status === 'draft')
            <x-ui.confirmation
                :id="'publish-quality-document-'.$document->id"
                title="Terbitkan dokumen?"
                confirm-label="Ya, terbitkan"
                tone="success"
            >
                Dokumen <strong>{{ $document->document_code }} · {{ $document->title }}</strong> akan menjadi aktif. Setelah diterbitkan, perubahan file harus dibuat melalui revisi.
            </x-ui.confirmation>

            <x-ui.confirmation
                :id="'delete-quality-document-'.$document->id"
                title="Hapus Draft dokumen?"
                confirm-label="Ya, hapus Draft"
            >
                Draft <strong>{{ $document->document_code }} · {{ $document->title }}</strong>, file yang diunggah, dan histori Draft akan dihapus permanen.
            </x-ui.confirmation>
        @elseif ($document->status === 'active')
            <x-ui.confirmation
                :id="'archive-quality-document-'.$document->id"
                title="Arsipkan dokumen?"
                confirm-label="Ya, arsipkan"
            >
                Dokumen <strong>{{ $document->document_code }} · {{ $document->title }}</strong> akan diarsipkan dan tidak lagi ditampilkan pada halaman Review.
            </x-ui.confirmation>
        @endif
    @endcan
@endsection
