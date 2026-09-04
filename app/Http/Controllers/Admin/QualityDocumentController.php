<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditCollection;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentRevision;
use App\Models\DocumentSection;
use App\Models\DocumentStandard;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class QualityDocumentController extends Controller
{
    private const TAB_CODES = [
        'manual-mutu' => 'MM',
        'quality-procedure' => 'QP',
        'working-instruction' => 'IK',
        'form' => 'F',
    ];

    public function index(Request $request): View
    {
        return $this->renderStandard($request, null);
    }

    public function standard(Request $request, DocumentStandard $standard): View
    {
        return $this->renderStandard($request, $standard);
    }

    public function storeStandard(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $name = trim($validated['name']);
        $baseSlug = Str::slug(preg_replace('/^iso\s*/i', '', $name) ?: $name) ?: 'iso';
        $slug = $baseSlug;
        $counter = 2;

        while (DocumentStandard::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $standard = DocumentStandard::query()->create([
            'name' => $name,
            'slug' => $slug,
            'order_number' => ((int) DocumentStandard::query()->max('order_number')) + 1,
        ]);

        return to_route('admin.quality-documents.standards.show', $standard)
            ->with('success', $standard->name.' berhasil ditambahkan.');
    }

    public function createDocument(Request $request, DocumentStandard $standard): View
    {
        $categories = $this->categories();
        $selectedCategory = $categories->firstWhere(
            'code',
            self::TAB_CODES[(string) $request->query('tab')] ?? 'QP',
        ) ?? $categories->first();

        return view('admin.quality-documents.document-form', [
            'standard' => $standard,
            'document' => new Document,
            'categories' => $categories,
            'sections' => $this->allSections($standard),
            'selectedCategory' => $selectedCategory,
            'formMode' => 'create',
            'relatedDocuments' => $this->relatedDocuments($standard),
        ]);
    }

    public function reviseDocument(DocumentStandard $standard, Document $document): View
    {
        $this->ensureDocumentBelongsToStandard($document, $standard);
        abort_unless($document->status === 'active', 422, 'Hanya dokumen aktif yang dapat dibuatkan revisi.');
        $document->load(['category', 'sections', 'latestRevision']);

        return view('admin.quality-documents.document-form', [
            'standard' => $standard,
            'document' => $document,
            'categories' => $this->categories(),
            'sections' => $this->allSections($standard),
            'selectedCategory' => $document->category,
            'formMode' => 'revision',
            'relatedDocuments' => $this->relatedDocuments($standard, $document),
        ]);
    }

    public function editDocument(DocumentStandard $standard, Document $document): View
    {
        $this->ensureDocumentBelongsToStandard($document, $standard);
        $document->load(['category', 'sections', 'latestRevision']);

        return view('admin.quality-documents.document-form', [
            'standard' => $standard,
            'document' => $document,
            'categories' => $this->categories(),
            'sections' => $this->allSections($standard),
            'selectedCategory' => $document->category,
            'formMode' => 'edit',
            'relatedDocuments' => $this->relatedDocuments($standard, $document),
        ]);
    }

    public function storeDocument(Request $request, DocumentStandard $standard): RedirectResponse
    {
        $existingDocument = $request->filled('document_id')
            ? Document::query()->findOrFail($request->integer('document_id'))
            : null;

        if ($existingDocument) {
            $this->ensureDocumentBelongsToStandard($existingDocument, $standard);
        }

        $validated = $request->validate([
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'category_id' => ['required', Rule::exists('document_categories', 'id')],
            'section_ids' => ['nullable', 'array'],
            'section_ids.*' => [
                'integer',
                Rule::exists('document_sections', 'id')
                    ->where('standard_id', $standard->id)
                    ->whereNull('parent_id'),
            ],
            'related_document_ids' => ['nullable', 'array'],
            'related_document_ids.*' => [
                'integer',
                Rule::exists('documents', 'id')->where('standard_id', $standard->id),
            ],
            'document_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('documents', 'document_code')
                    ->where('standard_id', $standard->id)
                    ->where('category_id', $request->input('category_id'))
                    ->ignore($existingDocument?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'effective_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['draft', 'active'])],
            'original_file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:20480'],
            'preview_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $category = DocumentCategory::query()->findOrFail($validated['category_id']);
        abort_unless(! $existingDocument || (int) $existingDocument->category_id === (int) $category->id, 422);
        if ($existingDocument) {
            abort_unless($existingDocument->status === 'active', 422, 'Dokumen Draft diperbaiki melalui menu Edit, bukan Revisi.');
        }
        $sectionIds = $this->validatedSectionIds($standard, $category, $validated['section_ids'] ?? [], $existingDocument);
        $relatedDocumentIds = collect($validated['related_document_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $previousManualSectionId = $existingDocument && $category->code === 'MM' ? $existingDocument->section_id : null;
        $files = $this->storeFiles($request, $standard);

        try {
            $document = DB::transaction(function () use (
                $existingDocument,
                $validated,
                $sectionIds,
                $files,
                $standard,
                $request,
                $category,
                $relatedDocumentIds,
                $previousManualSectionId,
            ): Document {
                $revisionNumber = $existingDocument
                    ? ((int) $existingDocument->revisions()->max('revision_number')) + 1
                    : 0;
                $attributes = [
                    'standard_id' => $standard->id,
                    'category_id' => (int) $validated['category_id'],
                    'section_id' => $sectionIds[0] ?? null,
                    'document_code' => trim($validated['document_code']),
                    'title' => trim($validated['title']),
                    'description' => $validated['description'] ?? null,
                    'revision_number' => $revisionNumber,
                    'effective_date' => $validated['effective_date'] ?? null,
                    'status' => $existingDocument ? 'active' : 'draft',
                    ...$files,
                    'updated_by' => $request->user()->id,
                ];

                if ($existingDocument) {
                    $existingDocument->update($attributes);
                    $document = $existingDocument;
                } else {
                    $document = Document::query()->create([
                        ...$attributes,
                        'created_by' => $request->user()->id,
                    ]);
                }

                $document->sections()->sync($sectionIds);
                if ($category->code === 'MM') {
                    if ($previousManualSectionId && (int) $previousManualSectionId !== (int) $sectionIds[0]) {
                        $this->syncRelatedDocumentsForChapter($standard, (int) $previousManualSectionId, []);
                    }
                    $this->syncRelatedDocumentsForChapter($standard, $sectionIds[0], $relatedDocumentIds);
                }
                $document->revisions()->create([
                    'document_code' => $attributes['document_code'],
                    'title' => $attributes['title'],
                    'description' => $attributes['description'],
                    'status' => $attributes['status'],
                    'section_ids' => $sectionIds,
                    'revision_number' => $revisionNumber,
                    'effective_date' => $attributes['effective_date'],
                    'original_file_path' => $files['original_file_path'],
                    'original_file_name' => $files['original_file_name'],
                    'original_file_type' => $files['original_file_type'],
                    'original_file_size' => $files['original_file_size'],
                    'preview_file_path' => $files['preview_file_path'],
                    'conversion_status' => $files['conversion_status'],
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);
                $document->activityLogs()->create([
                    'action' => $existingDocument ? 'revised' : 'created',
                    'description' => $existingDocument
                        ? 'Revisi '.str_pad((string) $revisionNumber, 2, '0', STR_PAD_LEFT).' dibuat.'
                        : 'Dokumen dibuat sebagai Draft.',
                    'created_by' => $request->user()->id,
                ]);

                return $document;
            });
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($files);
            throw $exception;
        }

        return to_route('admin.quality-documents.documents.show', [$standard, $document])
            ->with('success', $existingDocument
                ? 'Revisi berhasil disimpan sebagai Rev. '.str_pad((string) $document->revision_number, 2, '0', STR_PAD_LEFT).'.'
                : 'Dokumen berhasil disimpan sebagai Draft Rev. 00. Periksa lalu terbitkan jika sudah benar.');
    }

    public function updateDocument(
        Request $request,
        DocumentStandard $standard,
        Document $document,
    ): RedirectResponse {
        $this->ensureDocumentBelongsToStandard($document, $standard);
        $document->load(['category', 'sections', 'latestRevision']);

        $validated = $request->validate([
            'document_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('documents', 'document_code')
                    ->where('standard_id', $standard->id)
                    ->where('category_id', $document->category_id)
                    ->ignore($document->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'effective_date' => ['nullable', 'date'],
            'section_ids' => ['nullable', 'array'],
            'section_ids.*' => [
                'integer',
                Rule::exists('document_sections', 'id')
                    ->where('standard_id', $standard->id)
                    ->whereNull('parent_id'),
            ],
            'related_document_ids' => ['nullable', 'array'],
            'related_document_ids.*' => ['integer', Rule::exists('documents', 'id')->where('standard_id', $standard->id)],
            'original_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:20480'],
            'preview_file' => ['nullable', 'required_with:original_file', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        if ($document->status !== 'draft' && ($request->hasFile('original_file') || $request->hasFile('preview_file'))) {
            throw ValidationException::withMessages([
                'original_file' => 'File dokumen aktif hanya dapat diganti melalui fitur Buat Revisi.',
            ]);
        }

        $sectionIds = $this->validatedSectionIds($standard, $document->category, $validated['section_ids'] ?? [], $document);
        $relatedDocumentIds = collect($validated['related_document_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $previousManualSectionId = $document->category->code === 'MM' ? $document->section_id : null;
        $oldPaths = [];
        $fileAttributes = [];

        if ($request->hasFile('original_file')) {
            $fileAttributes = $this->storeFiles($request, $standard);
            $oldPaths = [$document->original_file_path, $document->preview_file_path];
        } elseif ($request->hasFile('preview_file')) {
            $oldPaths = [$document->preview_file_path];
            $fileAttributes = [
                'preview_file_path' => $request->file('preview_file')->store('quality-documents/'.$standard->slug.'/preview', 'local'),
                'conversion_status' => 'uploaded',
            ];
        }

        try {
            DB::transaction(function () use ($request, $document, $standard, $validated, $sectionIds, $relatedDocumentIds, $fileAttributes, $previousManualSectionId): void {
                $attributes = [
                    'document_code' => trim($validated['document_code']),
                    'title' => trim($validated['title']),
                    'description' => $validated['description'] ?? null,
                    'effective_date' => $validated['effective_date'] ?? null,
                    'section_id' => $sectionIds[0] ?? null,
                    'updated_by' => $request->user()->id,
                    ...$fileAttributes,
                ];
                $document->update($attributes);
                $document->sections()->sync($sectionIds);

                if ($document->category->code === 'MM') {
                    if ($previousManualSectionId && (int) $previousManualSectionId !== (int) $sectionIds[0]) {
                        $this->syncRelatedDocumentsForChapter($standard, (int) $previousManualSectionId, []);
                    }
                    $this->syncRelatedDocumentsForChapter($standard, $sectionIds[0], $relatedDocumentIds);
                }

                $revisionAttributes = [
                    'document_code' => $attributes['document_code'],
                    'title' => $attributes['title'],
                    'description' => $attributes['description'],
                    'effective_date' => $attributes['effective_date'],
                    'section_ids' => $sectionIds,
                ];
                if ($document->status === 'draft') {
                    $revisionAttributes += $fileAttributes;
                }
                $document->latestRevision?->update($revisionAttributes);
                $document->activityLogs()->create([
                    'action' => 'metadata_updated',
                    'description' => $document->status === 'draft'
                        ? 'Data Draft diperbaiki sebelum diterbitkan.'
                        : 'Informasi dan relasi dokumen diperbarui tanpa mengubah isi file.',
                    'changes' => $document->getChanges(),
                    'created_by' => $request->user()->id,
                ]);
            });
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($fileAttributes);
            throw $exception;
        }

        Storage::disk('local')->delete(array_values(array_unique(array_filter($oldPaths))));

        return to_route('admin.quality-documents.documents.show', [$standard, $document])
            ->with('success', $document->status === 'draft' ? 'Draft berhasil diperbaiki.' : 'Informasi dokumen berhasil diperbarui.');
    }

    public function publishDocument(Request $request, DocumentStandard $standard, Document $document): RedirectResponse
    {
        $this->ensureDocumentBelongsToStandard($document, $standard);
        abort_unless($document->status === 'draft', 422, 'Dokumen ini sudah diterbitkan.');

        DB::transaction(function () use ($request, $document): void {
            $document->update(['status' => 'active', 'updated_by' => $request->user()->id]);
            $document->latestRevision()->update(['status' => 'active']);
            $document->activityLogs()->create([
                'action' => 'published',
                'description' => 'Draft diterbitkan dan mulai digunakan pada Review.',
                'created_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Dokumen berhasil diterbitkan.');
    }

    public function archiveDocument(Request $request, DocumentStandard $standard, Document $document): RedirectResponse
    {
        $this->ensureDocumentBelongsToStandard($document, $standard);
        abort_unless($document->status === 'active', 422, 'Hanya dokumen aktif yang dapat diarsipkan.');

        $document->update(['status' => 'archived', 'updated_by' => $request->user()->id]);
        $document->activityLogs()->create([
            'action' => 'archived',
            'description' => 'Dokumen diarsipkan dan tidak lagi ditampilkan pada Review.',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Dokumen berhasil diarsipkan.');
    }

    public function destroyDocument(DocumentStandard $standard, Document $document): RedirectResponse
    {
        $this->ensureDocumentBelongsToStandard($document, $standard);
        abort_unless($document->status === 'draft', 422, 'Hanya dokumen Draft yang dapat dihapus.');
        $paths = $document->revisions()->get()->flatMap(fn (DocumentRevision $revision) => [
            $revision->original_file_path,
            $revision->preview_file_path,
        ])->push($document->original_file_path)->push($document->preview_file_path)->filter()->unique()->values()->all();
        $tab = array_search($document->category->code, self::TAB_CODES, true) ?: 'review';
        $document->delete();
        Storage::disk('local')->delete($paths);

        return to_route('admin.quality-documents.standards.show', [$standard, 'tab' => $tab])
            ->with('success', 'Draft dan file terkait berhasil dihapus.');
    }

    public function showDocument(DocumentStandard $standard, Document $document): View
    {
        $this->ensureDocumentBelongsToStandard($document, $standard);
        $document->load(['category', 'sections.parent', 'latestRevision.creator', 'revisions.creator', 'activityLogs.creator']);
        $latestRevision = $document->latestRevision;
        $previousRevision = $document->revisions
            ->where('revision_number', '<', $document->currentRevisionNumber())
            ->sortByDesc('revision_number')
            ->first();

        return view('admin.quality-documents.show', compact(
            'standard',
            'document',
            'latestRevision',
            'previousRevision',
        ));
    }

    public function createSection(DocumentStandard $standard): View
    {
        return view('admin.quality-documents.section-form', [
            'standard' => $standard,
            'section' => new DocumentSection,
        ]);
    }

    public function storeSection(Request $request, DocumentStandard $standard): RedirectResponse
    {
        $validated = $this->validateSection($request, $standard);
        DocumentSection::query()->create([
            ...$validated,
            'standard_id' => $standard->id,
            'order_number' => $this->sectionOrderNumber($validated['chapter_number']),
        ]);

        return to_route('admin.quality-documents.standards.show', [$standard, 'tab' => 'manual-mutu'])
            ->with('success', 'Bab utama berhasil ditambahkan.');
    }

    public function editSection(DocumentStandard $standard, DocumentSection $section): View
    {
        $this->ensureSectionBelongsToStandard($section, $standard);

        return view('admin.quality-documents.section-form', [
            'standard' => $standard,
            'section' => $section,
        ]);
    }

    public function updateSection(Request $request, DocumentStandard $standard, DocumentSection $section): RedirectResponse
    {
        $this->ensureSectionBelongsToStandard($section, $standard);
        $validated = $this->validateSection($request, $standard, $section);
        $section->update([
            ...$validated,
            'parent_id' => null,
            'order_number' => $this->sectionOrderNumber($validated['chapter_number']),
        ]);

        return to_route('admin.quality-documents.standards.show', [$standard, 'tab' => 'manual-mutu'])
            ->with('success', 'Bab utama berhasil diperbarui.');
    }

    public function destroySection(DocumentStandard $standard, DocumentSection $section): RedirectResponse
    {
        $this->ensureSectionBelongsToStandard($section, $standard);

        if ($section->children()->exists() || $section->documents()->exists() || $section->linkedDocuments()->exists()) {
            return back()->with('error', 'Bab tidak dapat dihapus karena masih memiliki data atau dokumen terkait.');
        }

        $section->delete();

        return back()->with('success', 'Bab utama berhasil dihapus.');
    }

    public function previewDocument(DocumentStandard $standard, Document $document): StreamedResponse
    {
        $this->ensureDocumentBelongsToStandard($document, $standard);

        return $this->inlinePdf($document->currentPreviewPath(), $document->document_code.'.pdf');
    }

    public function downloadDocument(DocumentStandard $standard, Document $document): StreamedResponse
    {
        $this->ensureDocumentBelongsToStandard($document, $standard);

        return $this->downloadFile($document->currentOriginalPath(), $document->currentOriginalName());
    }

    public function previewRevision(DocumentRevision $revision): StreamedResponse
    {
        return $this->inlinePdf($revision->preview_file_path, $revision->document_code.'-rev-'.$revision->revision_number.'.pdf');
    }

    public function downloadRevision(DocumentRevision $revision): StreamedResponse
    {
        return $this->downloadFile($revision->original_file_path, $revision->original_file_name);
    }

    private function renderStandard(Request $request, ?DocumentStandard $standard): View
    {
        $requestedTab = (string) $request->query('tab', 'review');
        $activeTab = $requestedTab === 'review' || array_key_exists($requestedTab, self::TAB_CODES)
            ? $requestedTab
            : 'review';
        $categories = $this->categories();
        $standards = DocumentStandard::query()
            ->withCount([
                'documents',
                'sections as sections_count' => fn ($query) => $query->whereNull('parent_id'),
            ])
            ->orderBy('order_number')
            ->get();

        if (! $standard) {
            return view('admin.quality-documents.index', [
                'standard' => null,
                'standards' => $standards,
                'auditCollections' => AuditCollection::query()
                    ->withCount('documents')
                    ->orderBy('order_number')
                    ->orderBy('name')
                    ->get(),
                'categories' => $categories,
                'activeTab' => 'review',
                'activeCategory' => null,
                'activeDocuments' => null,
                'allDocuments' => collect(),
                'manualMutu' => null,
                'sections' => collect(),
                'reviewRows' => collect(),
            ]);
        }

        $activeCode = self::TAB_CODES[$activeTab] ?? null;
        $activeCategory = $activeCode ? $categories->firstWhere('code', $activeCode) : null;
        $sections = $this->allSections($standard);
        $allDocuments = Document::query()
            ->with(['category', 'sections.parent', 'latestRevision'])
            ->where('standard_id', $standard->id)
            ->orderBy('document_code')
            ->get();
        $activeDocuments = $activeCategory
            ? Document::query()
                ->with(['category', 'sections.parent', 'latestRevision'])
                ->where('standard_id', $standard->id)
                ->where('category_id', $activeCategory->id)
                ->orderBy('document_code')
                ->paginate(12)
                ->withQueryString()
            : null;
        $reviewableDocuments = $allDocuments->whereIn('status', ['draft', 'active']);
        $reviewRows = $sections->map(function (DocumentSection $section) use ($reviewableDocuments): array {
            return [
                'section' => $section,
                'manual' => $reviewableDocuments
                    ->first(fn (Document $document) => $document->category?->code === 'MM' && (int) $document->section_id === (int) $section->id),
                'documents' => $reviewableDocuments
                    ->filter(fn (Document $document) => in_array($document->category?->code, ['QP', 'IK', 'F'], true))
                    ->filter(fn (Document $document) => $document->sections->contains('id', $section->id))
                    ->groupBy('category.code'),
            ];
        });
        $manualMutu = $reviewRows->first(fn (array $row) => $row['manual'])['manual'] ?? null;

        return view('admin.quality-documents.index', compact(
            'standard',
            'standards',
            'categories',
            'activeTab',
            'activeCategory',
            'activeDocuments',
            'allDocuments',
            'manualMutu',
            'sections',
            'reviewRows',
        ));
    }

    private function categories(): EloquentCollection
    {
        return DocumentCategory::query()
            ->whereIn('code', array_values(self::TAB_CODES))
            ->orderBy('order_number')
            ->get();
    }

    private function allSections(DocumentStandard $standard): EloquentCollection
    {
        return DocumentSection::query()
            ->where('standard_id', $standard->id)
            ->whereNull('parent_id')
            ->orderBy('order_number')
            ->get();
    }

    private function relatedDocuments(DocumentStandard $standard, ?Document $except = null): EloquentCollection
    {
        return Document::query()
            ->with(['category', 'sections'])
            ->where('standard_id', $standard->id)
            ->whereHas('category', fn ($query) => $query->whereIn('code', ['QP', 'IK', 'F']))
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->orderBy('document_code')
            ->get();
    }

    /** @param array<int, int|string> $requestedIds
     * @return array<int, int>
     */
    private function validatedSectionIds(
        DocumentStandard $standard,
        DocumentCategory $category,
        array $requestedIds,
        ?Document $document = null,
    ): array {
        $sectionIds = collect($requestedIds)->map(fn ($id) => (int) $id)->unique()->values()->all();

        if ($category->code !== 'MM') {
            return $sectionIds;
        }

        if (count($sectionIds) !== 1) {
            throw ValidationException::withMessages(['section_ids' => 'Manual Mutu wajib dipasangkan ke tepat satu Bab utama.']);
        }

        $section = DocumentSection::query()
            ->where('standard_id', $standard->id)
            ->find($sectionIds[0]);

        if (! $section || $section->parent_id) {
            throw ValidationException::withMessages(['section_ids' => 'Manual Mutu hanya dapat dipasangkan ke Bab utama yang valid.']);
        }

        $manualCategoryId = DocumentCategory::query()->where('code', 'MM')->value('id');
        $alreadyUsed = Document::query()
            ->where('standard_id', $standard->id)
            ->where('category_id', $manualCategoryId)
            ->where('section_id', $section->id)
            ->where('status', '!=', 'archived')
            ->when($document, fn ($query) => $query->whereKeyNot($document->id))
            ->exists();

        if ($alreadyUsed) {
            throw ValidationException::withMessages(['section_ids' => 'Bab ini sudah memiliki Manual Mutu aktif atau Draft. Gunakan Edit atau Buat Revisi pada dokumen tersebut.']);
        }

        return $sectionIds;
    }

    /** @param array<int, int> $selectedDocumentIds */
    private function syncRelatedDocumentsForChapter(
        DocumentStandard $standard,
        int $chapterId,
        array $selectedDocumentIds,
    ): void {
        $documents = $this->relatedDocuments($standard);

        foreach ($documents as $relatedDocument) {
            if (in_array($relatedDocument->id, $selectedDocumentIds, true)) {
                $relatedDocument->sections()->syncWithoutDetaching([$chapterId]);
                if (! $relatedDocument->section_id) {
                    $relatedDocument->update(['section_id' => $chapterId]);
                }
            } else {
                $relatedDocument->sections()->detach($chapterId);
                if ((int) $relatedDocument->section_id === $chapterId) {
                    $relatedDocument->update(['section_id' => $relatedDocument->sections()->value('document_sections.id')]);
                }
            }
        }
    }

    /** @return array<string, mixed> */
    private function validateSection(Request $request, DocumentStandard $standard, ?DocumentSection $section = null): array
    {
        return $request->validate([
            'chapter_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('document_sections', 'chapter_number')
                    ->where('standard_id', $standard->id)
                    ->ignore($section?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
        ]);
    }

    /**
     * @return array{original_file_path: string, original_file_name: string, original_file_type: string, original_file_size: int, preview_file_path: string, conversion_status: string}
     */
    private function storeFiles(Request $request, DocumentStandard $standard): array
    {
        $original = $request->file('original_file');
        $directory = 'quality-documents/'.$standard->slug;
        $originalPath = $original->store($directory.'/original', 'local');
        $extension = strtolower($original->getClientOriginalExtension());
        $previewPath = $request->file('preview_file')->store($directory.'/preview', 'local');

        return [
            'original_file_path' => $originalPath,
            'original_file_name' => $original->getClientOriginalName(),
            'original_file_type' => $extension,
            'original_file_size' => (int) $original->getSize(),
            'preview_file_path' => $previewPath,
            'conversion_status' => 'uploaded',
        ];
    }

    /** @param array<string, mixed> $files */
    private function deleteStoredFiles(array $files): void
    {
        Storage::disk('local')->delete(array_values(array_unique(array_filter([
            $files['original_file_path'] ?? null,
            $files['preview_file_path'] ?? null,
        ]))));
    }

    private function sectionOrderNumber(string $chapterNumber): int
    {
        $segments = array_pad(array_slice(explode('.', $chapterNumber), 0, 3), 3, '0');

        return (int) collect($segments)
            ->map(fn (string $segment) => str_pad((string) ((int) $segment), 3, '0', STR_PAD_LEFT))
            ->implode('');
    }

    private function ensureDocumentBelongsToStandard(Document $document, DocumentStandard $standard): void
    {
        abort_unless((int) $document->standard_id === (int) $standard->id, 404);
    }

    private function ensureSectionBelongsToStandard(DocumentSection $section, DocumentStandard $standard): void
    {
        abort_unless((int) $section->standard_id === (int) $standard->id, 404);
    }

    private function inlinePdf(?string $path, string $filename): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $filename).'"',
        ]);
    }

    private function downloadFile(?string $path, string $filename): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $filename);
    }
}
