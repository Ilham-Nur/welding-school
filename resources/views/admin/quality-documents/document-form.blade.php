@extends('layouts.admin')

@php
    $isRevision = $formMode === 'revision';
    $isEditing = $formMode === 'edit';
    $isCreate = $formMode === 'create';
    $selectedCategoryId = (int) old('category_id', $selectedCategory?->id);
    $selectedCategoryCode = $selectedCategory?->code;
    $selectedSectionIds = collect(old('section_ids', $document->exists ? $document->sections->pluck('id')->all() : []))->map(fn ($id) => (int) $id);
    $manualChapterId = $selectedSectionIds->first();
    $defaultRelatedIds = $manualChapterId
        ? $relatedDocuments->filter(fn ($item) => $item->sections->contains('id', $manualChapterId))->pluck('id')->all()
        : [];
    $selectedRelatedIds = collect(old('related_document_ids', $defaultRelatedIds))->map(fn ($id) => (int) $id);
    $backUrl = $document->exists
        ? route('admin.quality-documents.documents.show', [$standard, $document])
        : route('admin.quality-documents.standards.show', $standard);
    $formAction = $isEditing
        ? route('admin.quality-documents.documents.update', [$standard, $document])
        : route('admin.quality-documents.documents.store', $standard);
@endphp

@section('title', $isRevision ? 'Tambah Revisi' : ($isEditing ? 'Edit Dokumen' : 'Tambah Dokumen Quality'))
@section('eyebrow', 'Quality Documents · '.$standard->name)
@section('heading', $isRevision ? 'Tambah Revisi' : ($isEditing ? 'Edit Dokumen' : 'Tambah Dokumen'))

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $isRevision ? 'Revisi '.$document->document_code : ($isEditing ? 'Edit '.$document->document_code : 'Dokumen Quality baru') }}</h1>
            <p>
                @if ($isRevision) Versi sebelumnya tetap disimpan dalam histori dokumen.
                @elseif ($isEditing && $document->status === 'draft') Perbaiki informasi atau file Draft sebelum diterbitkan.
                @elseif ($isEditing) Perbaiki informasi dan relasi tanpa mengubah isi file aktif.
                @else Dokumen baru disimpan sebagai Draft agar dapat diperiksa terlebih dahulu.
                @endif
            </p>
        </div>
        <a class="button button--outline admin-button" href="{{ $backUrl }}">Kembali</a>
    </section>

    <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" data-quality-document-form>
        @csrf
        @if ($isEditing) @method('PUT') @endif
        @if ($isRevision)<input type="hidden" name="document_id" value="{{ $document->id }}">@endif
        @if (! $isCreate)<input type="hidden" name="category_id" value="{{ $document->category_id }}">@endif
        <input type="hidden" name="status" value="{{ $isRevision ? 'active' : 'draft' }}">

        <div class="qd-form-layout">
            <section class="admin-panel">
                <header class="admin-panel__header"><div><h2>Informasi dokumen</h2><p>Identitas dan pengelompokan dokumen terkendali.</p></div></header>
                <div class="admin-panel__body admin-form-grid">
                    <label class="admin-field">
                        <span>Kategori</span>
                        @if ($isCreate)
                            <select name="category_id" required data-document-category>
                                @foreach ($categories as $category)<option value="{{ $category->id }}" data-category-code="{{ $category->code }}" @selected($selectedCategoryId === $category->id)>{{ $category->name }} ({{ $category->code }})</option>@endforeach
                            </select>
                        @else
                            <input value="{{ $document->category->name }} ({{ $document->category->code }})" disabled>
                        @endif
                    </label>
                    <label class="admin-field">
                        <span>Kode dokumen</span>
                        <input name="document_code" value="{{ old('document_code', $document->document_code) }}" placeholder="Contoh: QP-QA-001" required>
                        @error('document_code')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="admin-field admin-field--full">
                        <span>Judul dokumen</span>
                        <input name="title" value="{{ old('title', $document->title) }}" placeholder="Contoh: Prosedur Pengendalian Dokumen" required>
                        @error('title')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="admin-field admin-field--full">
                        <span>Deskripsi</span>
                        <textarea name="description" placeholder="Ringkasan tujuan atau ruang lingkup dokumen">{{ old('description', $document->description) }}</textarea>
                    </label>
                    <label class="admin-field">
                        <span>Tanggal berlaku</span>
                        <input type="date" name="effective_date" value="{{ old('effective_date', $document->effective_date?->format('Y-m-d')) }}">
                    </label>
                    <div class="admin-field">
                        <span>Status setelah disimpan</span>
                        <div class="qd-form-status"><x-admin.status-badge :status="$isRevision ? 'active' : ($document->status ?: 'draft')" /><small>{{ $isRevision ? 'Revisi langsung menjadi versi aktif.' : ($document->status === 'active' ? 'Isi file tidak dapat diganti melalui Edit.' : 'Terbitkan setelah data dan file diperiksa.') }}</small></div>
                    </div>
                </div>
            </section>

            <aside class="qd-form-sidebar">
                <section class="admin-panel">
                    <header class="admin-panel__header"><div><h2>File dokumen</h2><p>Maksimum 20 MB per file.</p></div></header>
                    <div class="admin-panel__body qd-file-fields">
                        @if ($isEditing)
                            <div class="qd-current-file"><span>File saat ini</span><strong>{{ $document->currentOriginalName() }}</strong><small>Rev. {{ str_pad((string) $document->currentRevisionNumber(), 2, '0', STR_PAD_LEFT) }}</small></div>
                        @endif

                        @if (! $isEditing || $document->status === 'draft')
                            <x-ui.file-input label="{{ $isEditing ? 'File asli pengganti (opsional)' : 'File asli' }}" name="original_file" accept=".pdf,.doc,.docx,.xls,.xlsx" hint="PDF, Word, atau Excel. Maksimal 20 MB." :max-size-mb="20" :required="! $isEditing" />
                            <x-ui.file-input label="PDF preview (opsional)" name="preview_file" accept="application/pdf,.pdf" hint="PDF maksimal 20 MB. Kosongkan jika file asli sudah berupa PDF." :max-size-mb="20" />
                            <p class="qd-file-conversion-note">File Word atau Excel akan dicoba dikonversi otomatis. Unggah PDF preview apabila konverter server belum tersedia.</p>
                        @else
                            <div class="qd-inline-empty">File dokumen aktif hanya dapat diganti melalui <strong>Buat Revisi</strong>.</div>
                        @endif

                        @if (! $isEditing)
                            <label class="admin-field"><span>Catatan {{ $isRevision ? 'revisi' : 'penerbitan' }}</span><textarea name="notes" placeholder="Jelaskan perubahan atau keterangan penerbitan">{{ old('notes') }}</textarea></label>
                        @endif
                    </div>
                </section>
            </aside>
        </div>

        <section class="admin-panel qd-section-picker-panel" data-standard-section-picker data-fixed-category="{{ $isCreate ? '' : $selectedCategoryCode }}">
            <header class="admin-panel__header"><div><h2>Bab dokumen</h2><p>Hubungkan dokumen dengan Bab Utama yang sesuai.</p></div></header>
            <div class="admin-panel__body">
                @error('section_ids')<div class="qd-validation-message">{{ $message }}</div>@enderror

                <div data-manual-section-group @if ($selectedCategoryCode !== 'MM') hidden @endif>
                    @if ($sections->isEmpty())
                        <div class="qd-inline-empty">Tambahkan Bab utama terlebih dahulu sebelum mengunggah Manual Mutu.</div>
                    @else
                        <p class="qd-picker-intro">Pilih tepat satu Bab utama untuk file Manual Mutu ini.</p>
                        <div class="qd-section-picker">
                            @foreach ($sections as $section)
                                <label><input type="radio" name="section_ids[]" value="{{ $section->id }}" @checked($selectedSectionIds->contains($section->id)) @disabled($selectedCategoryCode !== 'MM')><span><strong>{{ $section->chapter_number }}</strong>{{ $section->title }}</span></label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div data-supporting-section-group @if ($selectedCategoryCode === 'MM') hidden @endif>
                    @if ($sections->isEmpty())
                        <div class="qd-inline-empty">Struktur bab belum tersedia. Tambahkan Bab terlebih dahulu agar dokumen dapat tampil pada Review.</div>
                    @else
                        <p class="qd-picker-intro">Dokumen pendukung dapat dihubungkan ke satu atau beberapa Bab Utama.</p>
                        <div class="qd-section-picker">
                            @foreach ($sections as $section)
                                <label><input type="checkbox" name="section_ids[]" value="{{ $section->id }}" @checked($selectedSectionIds->contains($section->id)) @disabled($selectedCategoryCode === 'MM')><span><strong>{{ $section->chapter_number }}</strong>{{ $section->title }}</span></label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="admin-panel qd-related-picker-panel" data-related-document-picker @if ($selectedCategoryCode !== 'MM') hidden @endif>
            <header class="admin-panel__header"><div><h2>Dokumen terkait</h2><p>Pilih QP, Working Instruction, dan Form yang berkaitan dengan Bab Manual Mutu ini.</p></div></header>
            <div class="admin-panel__body">
                @if ($relatedDocuments->isEmpty())
                    <div class="qd-inline-empty">Belum ada QP, Working Instruction, atau Form. Relasi dapat ditambahkan kemudian melalui menu Edit.</div>
                @else
                    <div class="qd-related-document-groups">
                        @foreach (['QP' => 'Quality Procedure', 'IK' => 'Working Instruction', 'F' => 'Form'] as $code => $label)
                            @php($items = $relatedDocuments->where('category.code', $code))
                            @if ($items->isNotEmpty())
                                <fieldset><legend>{{ $label }}</legend>
                                    @foreach ($items as $item)
                                        <label><input type="checkbox" name="related_document_ids[]" value="{{ $item->id }}" @checked($selectedRelatedIds->contains($item->id)) @disabled($selectedCategoryCode !== 'MM')><span><strong>{{ $item->document_code }}</strong>{{ $item->title }}</span></label>
                                    @endforeach
                                </fieldset>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <div class="admin-form-actions">
            <a class="button button--outline admin-button" href="{{ $backUrl }}">Batal</a>
            <button class="button button--primary admin-button" type="submit">{{ $isRevision ? 'Simpan revisi berikutnya' : ($isEditing ? 'Simpan perubahan' : 'Simpan sebagai Draft') }}</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-quality-document-form]');
            const picker = document.querySelector('[data-standard-section-picker]');
            if (!form || !picker) return;

            const categorySelect = form.querySelector('[data-document-category]');
            const manualGroup = picker.querySelector('[data-manual-section-group]');
            const supportingGroup = picker.querySelector('[data-supporting-section-group]');
            const relatedPicker = form.querySelector('[data-related-document-picker]');
            const fixedCategory = picker.dataset.fixedCategory;

            const updateGroups = () => {
                const code = fixedCategory || categorySelect?.selectedOptions[0]?.dataset.categoryCode || '';
                const isManual = code === 'MM';
                manualGroup.hidden = !isManual;
                supportingGroup.hidden = isManual;
                if (relatedPicker) relatedPicker.hidden = !isManual;
                manualGroup.querySelectorAll('input').forEach((input) => input.disabled = !isManual);
                supportingGroup.querySelectorAll('input').forEach((input) => input.disabled = isManual);
                relatedPicker?.querySelectorAll('input').forEach((input) => input.disabled = !isManual);
            };

            categorySelect?.addEventListener('change', updateGroups);
            updateGroups();
        })();
    </script>
@endpush
