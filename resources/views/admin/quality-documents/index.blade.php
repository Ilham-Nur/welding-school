@extends('layouts.admin')

@section('title', 'Quality Documents')
@section('eyebrow', 'Document Control')
@section('heading', 'Quality Documents')

@php
    $tabs = [
        'review' => ['label' => 'Review', 'code' => null],
        'manual-mutu' => ['label' => 'Manual Mutu', 'code' => 'MM'],
        'quality-procedure' => ['label' => 'Quality Procedure', 'code' => 'QP'],
        'working-instruction' => ['label' => 'Working Instruction', 'code' => 'IK'],
        'form' => ['label' => 'Form', 'code' => 'F'],
    ];
    $categoryCounts = $allDocuments->groupBy('category.code')->map->count();
@endphp

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $standard ? 'Dokumen '.$standard->name : 'Dokumen Quality' }}</h1>
            <p>{{ $standard ? 'Review relasi bab, dokumen, dan revisi '.$standard->name.' dari Manual Mutu sampai Form.' : 'Siapkan Data Audit atau pilih standar ISO untuk membuka dokumen terkendali.' }}</p>
        </div>
        @if ($standard)
            <a class="button button--outline admin-button" href="{{ route('admin.quality-documents.index') }}">← Semua standar</a>
        @else
            @can('quality-documents.structure.manage')
                <details class="qd-add-standard">
                    <summary class="button button--outline admin-button">+ Standar ISO</summary>
                    <form method="POST" action="{{ route('admin.quality-documents.standards.store') }}">
                        @csrf
                        <label class="admin-field">
                            <span>Nama standar</span>
                            <input name="name" value="{{ old('name') }}" placeholder="Contoh: ISO 3834" required>
                        </label>
                        <button class="button button--primary admin-button" type="submit">Simpan</button>
                    </form>
                </details>
            @endcan
        @endif
    </section>

    @if (! $standard)
        <section class="qd-quality-home-section">
            <header><div><span>Dokumen terkendali</span><h2>Standar ISO</h2><p>Pilih standar untuk membuka Review, Manual Mutu, QP, WI, dan Form.</p></div></header>
        @if ($standards->isEmpty())
            <section class="admin-panel">
                <div class="admin-empty">
                    <span><x-ui.icon name="file" /></span>
                    <h2>Belum ada standar Quality</h2>
                    <p>Tambahkan standar ISO pertama untuk mulai menyusun dokumen.</p>
                </div>
            </section>
        @else
            <section class="qd-quality-library" aria-label="Daftar standar Quality">
                @foreach ($standards as $standardOption)
                    <a href="{{ route('admin.quality-documents.standards.show', $standardOption) }}">
                        <span class="qd-quality-library__icon"><x-ui.icon name="shield" size="28" /></span>
                        <span class="qd-quality-library__copy">
                            <small>Quality standard</small>
                            <strong>{{ $standardOption->name }}</strong>
                            <span>{{ $standardOption->sections_count }} Bab Utama · {{ $standardOption->documents_count }} dokumen</span>
                        </span>
                        <span class="qd-quality-library__arrow" aria-hidden="true">→</span>
                    </a>
                @endforeach
            </section>
        @endif
        </section>

        <section class="qd-quality-home-section">
            <header><div><span>Persiapan auditor</span><h2>Data Audit</h2></div></header>
            <a class="qd-audit-library-card" href="{{ route('admin.quality-documents.audit.index') }}">
                <span class="qd-quality-library__icon"><x-ui.icon name="file" size="28" /></span>
                <span class="qd-quality-library__copy"><small>Audit document</small><strong>Data Audit</strong><span>{{ $auditDocumentCount }} dokumen tersedia</span></span>
                <span class="qd-quality-library__arrow" aria-hidden="true">→</span>
            </a>
        </section>
    @else
        <section class="qd-standard-hero">
            <div>
                <span>Standar aktif</span>
                <h2>{{ $standard->name }}</h2>
                <p>{{ $sections->count() }} Bab Utama · {{ $allDocuments->count() }} dokumen terkendali</p>
            </div>
            @can('quality-documents.manage')
                <a class="button button--primary admin-button" href="{{ route('admin.quality-documents.documents.create', ['standard' => $standard, 'tab' => $activeTab]) }}">+ Tambah dokumen</a>
            @endcan
        </section>

        <nav class="qd-tabs" aria-label="Kategori dokumen">
            @foreach ($tabs as $tabKey => $tab)
                <a @class(['is-active' => $activeTab === $tabKey]) href="{{ route('admin.quality-documents.standards.show', ['standard' => $standard, 'tab' => $tabKey]) }}">
                    {{ $tab['label'] }}
                    @if ($tab['code'])
                        <small>{{ $categoryCounts->get($tab['code'], 0) }}</small>
                    @endif
                </a>
            @endforeach
        </nav>

        @if ($activeTab === 'review')
            <section class="admin-panel qd-review-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Review Dokumen</h2>
                        <p>Pilih Bab untuk menampilkan Manual Mutu dan dokumen pendukungnya. Draft diberi penanda agar dapat diperiksa sebelum diterbitkan.</p>
                    </div>
                </header>
                <div class="qd-review-columns">
                    <article class="qd-review-main-document">
                        <header class="qd-review-column-heading">
                            <div><span>Main Document</span><strong>Manual Mutu</strong></div>
                            <small data-review-main-revision @if (! $manualMutu) hidden @endif>@if ($manualMutu) Rev. {{ str_pad((string) $manualMutu->currentRevisionNumber(), 2, '0', STR_PAD_LEFT) }} @endif</small>
                        </header>
                        <div class="qd-review-main-preview" data-review-main-document>
                            <div class="qd-review-preview-toolbar" data-review-main-toolbar @if (! $manualMutu) hidden @endif>
                                @if ($manualMutu)
                                    <span class="qd-review-file-icon"><x-ui.icon name="file" size="22" /></span>
                                    <div>
                                        <strong data-review-main-title>{{ $manualMutu->title }}</strong>
                                        <small data-review-main-meta>{{ $manualMutu->document_code }} · {{ $manualMutu->status === 'draft' ? 'Draft' : 'Aktif' }} · Berlaku {{ $manualMutu->effective_date?->translatedFormat('d M Y') ?? 'belum ditentukan' }}</small>
                                    </div>
                                    <div class="admin-action-group">
                                        <a class="admin-action-button admin-action-button--view" data-review-main-detail href="{{ route('admin.quality-documents.documents.show', [$standard, $manualMutu]) }}"><x-ui.icon name="eye" size="14" /> Detail</a>
                                        <a class="admin-action-button" data-review-main-download href="{{ route('admin.quality-documents.documents.download', [$standard, $manualMutu]) }}"><x-ui.icon name="download" size="14" /> Asli</a>
                                    </div>
                                @endif
                            </div>
                            <iframe data-review-main-frame @if ($manualMutu?->currentPreviewPath()) src="{{ route('admin.quality-documents.documents.preview', [$standard, $manualMutu]) }}" @else hidden @endif title="Manual Mutu {{ $standard->name }}"></iframe>
                            <div class="admin-empty qd-review-empty" data-review-main-empty @if ($manualMutu?->currentPreviewPath()) hidden @endif>
                                <span><x-ui.icon name="file" /></span>
                                <h2 data-review-empty-title>{{ $manualMutu ? 'Preview PDF belum tersedia' : 'Pilih Bab Manual Mutu' }}</h2>
                                <p data-review-empty-copy>{{ $manualMutu ? 'File asli tetap dapat dibuka melalui tombol Detail atau diunduh.' : 'Pilih Bab pada Second Document untuk menampilkan file Manual Mutu.' }}</p>
                            </div>
                        </div>
                    </article>

                    <article class="qd-review-related-documents">
                        <header class="qd-review-column-heading">
                            <div><span>Second Document</span><strong>Bab &amp; Dokumen Terkait</strong></div>
                            <small>{{ $sections->count() }} bagian</small>
                        </header>
                        <div class="qd-review-related-body">
                            <label class="qd-review-search">
                                <span aria-hidden="true">⌕</span>
                                <input type="search" data-review-search placeholder="Cari Bab atau kode dokumen...">
                            </label>
                            <div class="qd-review-tree" data-review-tree>
                                @forelse ($reviewRows as $row)
                                    @php
                                        $section = $row['section'];
                                        $groupedDocuments = $row['documents'];
                                    @endphp
                                    <section class="qd-review-section" data-review-section>
                                        @php($chapterManual = $row['manual'])
                                        <button
                                            type="button"
                                            @class(['qd-review-section-title', 'is-active' => $manualMutu && $chapterManual?->is($manualMutu)])
                                            data-review-chapter
                                            aria-pressed="{{ $manualMutu && $chapterManual?->is($manualMutu) ? 'true' : 'false' }}"
                                            data-chapter-label="{{ $section->chapter_number }}. {{ mb_strtoupper($section->title) }}"
                                            data-manual-title="{{ $chapterManual?->title }}"
                                            data-manual-meta="{{ $chapterManual ? $chapterManual->document_code.' · '.($chapterManual->status === 'draft' ? 'Draft' : 'Aktif').' · Berlaku '.($chapterManual->effective_date?->translatedFormat('d M Y') ?? 'belum ditentukan') : '' }}"
                                            data-manual-revision="{{ $chapterManual ? 'Rev. '.str_pad((string) $chapterManual->currentRevisionNumber(), 2, '0', STR_PAD_LEFT) : '' }}"
                                            data-manual-preview="{{ $chapterManual?->currentPreviewPath() ? route('admin.quality-documents.documents.preview', [$standard, $chapterManual]) : '' }}"
                                            data-manual-detail="{{ $chapterManual ? route('admin.quality-documents.documents.show', [$standard, $chapterManual]) : '' }}"
                                            data-manual-download="{{ $chapterManual ? route('admin.quality-documents.documents.download', [$standard, $chapterManual]) : '' }}"
                                        >
                                            <strong><span class="qd-review-chapter-name">{{ $section->chapter_number }}. {{ $section->title }}</span><small>{{ $chapterManual ? 'Manual Mutu '.($chapterManual->status === 'draft' ? 'Draft' : 'Aktif') : 'Manual Mutu belum tersedia' }}</small></strong>
                                        </button>
                                        @foreach (['QP' => 'Quality Procedure', 'IK' => 'Working Instruction', 'F' => 'Form'] as $code => $label)
                                            @php($linkedItems = $groupedDocuments->get($code, collect()))
                                            @if ($linkedItems->isNotEmpty())
                                                <div class="qd-review-doc-group">
                                                    <div class="qd-review-doc-label">{{ $label }}</div>
                                                    <ul>
                                                        @foreach ($linkedItems as $linkedDocument)
                                                            <li>
                                                                <a href="{{ route('admin.quality-documents.documents.show', [$standard, $linkedDocument]) }}" target="_blank" rel="noopener">
                                                                    {{ $linkedDocument->document_code }} · {{ $linkedDocument->title }}
                                                                    <span>{{ $linkedDocument->status === 'draft' ? 'Draft' : 'Aktif' }} · Rev. {{ str_pad((string) $linkedDocument->currentRevisionNumber(), 2, '0', STR_PAD_LEFT) }}</span>
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endforeach
                                    </section>
                                @empty
                                    <div class="admin-empty qd-review-empty"><span><x-ui.icon name="list" /></span><h2>Bab Utama belum tersedia</h2><p>Tambahkan Bab Utama agar review dokumen dapat dikelompokkan.</p></div>
                                @endforelse
                            </div>
                            <div class="qd-review-no-results" data-review-no-results hidden>Tidak ada bab atau dokumen yang sesuai dengan pencarian.</div>
                        </div>
                    </article>
                </div>
            </section>
        @else
            @if ($activeTab === 'manual-mutu')
                <section class="admin-panel qd-section-panel">
                    <header class="admin-panel__header">
                        <div>
                            <h2>Struktur Bab Utama</h2>
                            <p>Setiap Bab menjadi navigasi Manual Mutu dan dokumen terkait pada tampilan Review.</p>
                        </div>
                        @can('quality-documents.structure.manage')
                            <a class="button button--outline admin-button" href="{{ route('admin.quality-documents.sections.create', $standard) }}">+ Tambah bab</a>
                        @endcan
                    </header>
                    @if ($sections->isEmpty())
                        <div class="admin-empty admin-empty--compact"><span><x-ui.icon name="list" /></span><h2>Belum ada Bab Utama</h2><p>Tambahkan Bab Utama pertama untuk mulai menyusun Manual Mutu.</p></div>
                    @else
                        <x-ui.table class="admin-table-wrap">
                            <thead><tr><th>Nomor</th><th>Judul Bab Utama</th>@can('quality-documents.structure.manage')<th>Aksi</th>@endcan</tr></thead>
                            <tbody>
                                @foreach ($sections as $section)
                                    <tr>
                                        <td><strong>{{ $section->chapter_number }}</strong></td>
                                        <td>{{ $section->title }}</td>
                                        @can('quality-documents.structure.manage')
                                            <td><div class="admin-action-group">
                                                <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.quality-documents.sections.edit', [$standard, $section]) }}"><x-ui.icon name="edit" size="14" /> Edit</a>
                                                <form method="POST" action="{{ route('admin.quality-documents.sections.destroy', [$standard, $section]) }}" data-confirm-dialog="delete-quality-section-{{ $section->id }}">@csrf @method('DELETE')<button class="admin-action-button admin-action-button--delete" type="submit"><x-ui.icon name="trash" size="14" /> Hapus</button></form>
                                            </div></td>
                                        @endcan
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>

                        @can('quality-documents.structure.manage')
                            @foreach ($sections as $section)
                                <x-ui.confirmation
                                    :id="'delete-quality-section-'.$section->id"
                                    title="Hapus struktur Bab?"
                                    confirm-label="Ya, hapus Bab"
                                >
                                    Bab <strong>{{ $section->chapter_number }}. {{ $section->title }}</strong> akan dihapus. Bab yang masih memiliki dokumen terkait tidak dapat dihapus.
                                </x-ui.confirmation>
                            @endforeach
                        @endcan
                    @endif
                </section>
            @endif

            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>{{ $tabs[$activeTab]['label'] }}</h2>
                        <p>{{ $activeDocuments?->total() ?? 0 }} dokumen ditemukan pada {{ $standard->name }}.</p>
                    </div>
                    @can('quality-documents.manage')
                        <a class="button button--primary admin-button" href="{{ route('admin.quality-documents.documents.create', ['standard' => $standard, 'tab' => $activeTab]) }}">+ Tambah dokumen</a>
                    @endcan
                </header>
                @if (! $activeDocuments || $activeDocuments->isEmpty())
                    <div class="admin-empty"><span><x-ui.icon name="file" /></span><h2>Belum ada {{ $tabs[$activeTab]['label'] }}</h2><p>Unggah dokumen pertama untuk kategori ini.</p></div>
                @else
                    <x-ui.table class="admin-table-wrap">
                        <thead><tr><th>Dokumen</th><th>Bab terkait</th><th>Revisi</th><th>Tanggal berlaku</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($activeDocuments as $document)
                                <tr>
                                    <td><strong>{{ $document->document_code }}</strong><small>{{ $document->title }}</small></td>
                                    <td>
                                        @if ($document->sections->isEmpty())<small>Tidak menggunakan relasi bab</small>@else
                                            <div class="qd-section-chips">@foreach ($document->sections as $section)<span>{{ $section->chapter_number }}</span>@endforeach</div>
                                        @endif
                                    </td>
                                    <td><strong>Rev. {{ str_pad((string) $document->currentRevisionNumber(), 2, '0', STR_PAD_LEFT) }}</strong></td>
                                    <td>{{ $document->effective_date?->translatedFormat('d M Y') ?? 'Belum ditentukan' }}</td>
                                    <td><x-admin.status-badge :status="$document->status" /></td>
                                    <td><div class="admin-action-group">
                                        <a class="admin-action-button admin-action-button--view" href="{{ route('admin.quality-documents.documents.show', [$standard, $document]) }}"><x-ui.icon name="eye" size="14" /> Buka</a>
                                        @can('quality-documents.manage')
                                            <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.quality-documents.documents.edit', [$standard, $document]) }}"><x-ui.icon name="edit" size="14" /> Edit</a>
                                            @if ($document->status === 'active')<a class="admin-action-button" href="{{ route('admin.quality-documents.documents.revise', [$standard, $document]) }}"><x-ui.icon name="upload" size="14" /> Revisi</a>@endif
                                        @endcan
                                    </div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                    <x-ui.pagination :paginator="$activeDocuments" />
                @endif
            </section>
        @endif
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const search = document.querySelector('[data-review-search]');
            const sections = [...document.querySelectorAll('[data-review-section]')];
            const noResults = document.querySelector('[data-review-no-results]');
            const chapterButtons = [...document.querySelectorAll('[data-review-chapter]')];
            const mainFrame = document.querySelector('[data-review-main-frame]');
            const mainToolbar = document.querySelector('[data-review-main-toolbar]');
            const mainTitle = document.querySelector('[data-review-main-title]');
            const mainMeta = document.querySelector('[data-review-main-meta]');
            const mainRevision = document.querySelector('[data-review-main-revision]');
            const mainDetail = document.querySelector('[data-review-main-detail]');
            const mainDownload = document.querySelector('[data-review-main-download]');
            const mainEmpty = document.querySelector('[data-review-main-empty]');
            const emptyTitle = document.querySelector('[data-review-empty-title]');
            const emptyCopy = document.querySelector('[data-review-empty-copy]');

            chapterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    chapterButtons.forEach((item) => {
                        item.classList.remove('is-active');
                        item.setAttribute('aria-pressed', 'false');
                    });
                    button.classList.add('is-active');
                    button.setAttribute('aria-pressed', 'true');

                    const hasManual = Boolean(button.dataset.manualDetail);
                    const hasPreview = Boolean(button.dataset.manualPreview);
                    if (mainToolbar) mainToolbar.hidden = !hasManual;
                    if (mainRevision) {
                        mainRevision.hidden = !hasManual;
                        mainRevision.textContent = button.dataset.manualRevision || '';
                    }
                    if (mainTitle) mainTitle.textContent = button.dataset.manualTitle || '';
                    if (mainMeta) mainMeta.textContent = button.dataset.manualMeta || '';
                    if (mainDetail) mainDetail.href = button.dataset.manualDetail || '#';
                    if (mainDownload) mainDownload.href = button.dataset.manualDownload || '#';
                    if (mainFrame) {
                        mainFrame.hidden = !hasPreview;
                        mainFrame.title = button.dataset.chapterLabel || 'Manual Mutu';
                        if (hasPreview && mainFrame.getAttribute('src') !== button.dataset.manualPreview) {
                            mainFrame.setAttribute('src', button.dataset.manualPreview);
                        } else if (!hasPreview) {
                            mainFrame.removeAttribute('src');
                        }
                    }
                    if (mainEmpty) mainEmpty.hidden = hasPreview;
                    if (emptyTitle) emptyTitle.textContent = hasManual ? 'Preview PDF belum tersedia' : 'Manual Mutu Bab belum tersedia';
                    if (emptyCopy) emptyCopy.textContent = hasManual
                        ? 'File asli tetap dapat dibuka melalui tombol Detail atau diunduh.'
                        : `${button.dataset.chapterLabel} belum memiliki file Manual Mutu aktif.`;
                });
            });

            if (!search || sections.length === 0) return;

            search.addEventListener('input', () => {
                const keyword = search.value.toLocaleLowerCase('id').trim();
                let visibleCount = 0;

                sections.forEach((section) => {
                    const matches = !keyword || section.textContent.toLocaleLowerCase('id').includes(keyword);
                    section.hidden = !matches;
                    if (matches) visibleCount += 1;
                });

                if (noResults) noResults.hidden = visibleCount !== 0;
            });
        })();
    </script>
@endpush
