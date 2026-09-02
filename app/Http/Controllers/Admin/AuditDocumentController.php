<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditCollection;
use App\Models\AuditDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditDocumentController extends Controller
{
    private const ACCEPTED_EXTENSIONS = 'pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,jpg,jpeg,png,webp,zip';

    public function legacyIndex(): RedirectResponse
    {
        $collection = AuditCollection::query()->orderBy('order_number')->first();

        if (! $collection) {
            return to_route('admin.quality-documents.index');
        }

        return to_route('admin.quality-documents.audit.index', $collection);
    }

    public function storeCollection(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $name = trim($validated['name']);
        $baseSlug = Str::slug($name) ?: 'data-audit';
        $slug = $baseSlug;
        $counter = 2;

        while (AuditCollection::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $collection = AuditCollection::query()->create([
            'name' => $name,
            'slug' => $slug,
            'order_number' => ((int) AuditCollection::query()->max('order_number')) + 1,
        ]);

        return to_route('admin.quality-documents.audit.index', $collection)
            ->with('success', $collection->name.' berhasil ditambahkan.');
    }

    public function index(Request $request, AuditCollection $auditCollection): View
    {
        $search = trim((string) $request->query('search'));
        $documents = $auditCollection->documents()
            ->with('updater')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('file_name', 'like', '%'.$search.'%');
            }))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.quality-documents.audit.index', compact('auditCollection', 'documents', 'search'));
    }

    public function create(AuditCollection $auditCollection): View
    {
        return view('admin.quality-documents.audit.form', [
            'auditCollection' => $auditCollection,
            'document' => new AuditDocument,
        ]);
    }

    public function store(Request $request, AuditCollection $auditCollection): RedirectResponse
    {
        $validated = $this->validateDocument($request, true);
        $file = $request->file('file');
        $path = $file->store('quality-documents/audit', 'local');

        AuditDocument::query()->create([
            'audit_collection_id' => $auditCollection->id,
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'file_size' => (int) $file->getSize(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return to_route('admin.quality-documents.audit.index', $auditCollection)->with('success', 'Dokumen audit berhasil ditambahkan.');
    }

    public function edit(AuditCollection $auditCollection, AuditDocument $auditDocument): View
    {
        $this->ensureDocumentBelongsTo($auditCollection, $auditDocument);

        return view('admin.quality-documents.audit.form', [
            'auditCollection' => $auditCollection,
            'document' => $auditDocument,
        ]);
    }

    public function update(Request $request, AuditCollection $auditCollection, AuditDocument $auditDocument): RedirectResponse
    {
        $this->ensureDocumentBelongsTo($auditCollection, $auditDocument);
        $validated = $this->validateDocument($request, false);
        $attributes = [
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'updated_by' => $request->user()->id,
        ];
        $oldPath = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $oldPath = $auditDocument->file_path;
            $attributes += [
                'file_path' => $file->store('quality-documents/audit', 'local'),
                'file_name' => $file->getClientOriginalName(),
                'file_type' => strtolower($file->getClientOriginalExtension()),
                'file_size' => (int) $file->getSize(),
            ];
        }

        $auditDocument->update($attributes);
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return to_route('admin.quality-documents.audit.index', $auditCollection)->with('success', 'Dokumen audit berhasil diperbarui.');
    }

    public function destroy(AuditCollection $auditCollection, AuditDocument $auditDocument): RedirectResponse
    {
        $this->ensureDocumentBelongsTo($auditCollection, $auditDocument);
        $path = $auditDocument->file_path;
        $auditDocument->delete();
        Storage::disk('local')->delete($path);

        return back()->with('success', 'Dokumen audit berhasil dihapus.');
    }

    public function preview(AuditCollection $auditCollection, AuditDocument $auditDocument): StreamedResponse
    {
        $this->ensureDocumentBelongsTo($auditCollection, $auditDocument);
        abort_unless($auditDocument->canPreview() && Storage::disk('local')->exists($auditDocument->file_path), 404);
        $contentTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'txt' => 'text/plain; charset=UTF-8',
            'csv' => 'text/csv; charset=UTF-8',
        ];

        return Storage::disk('local')->response($auditDocument->file_path, $auditDocument->file_name, [
            'Content-Type' => $contentTypes[strtolower((string) $auditDocument->file_type)],
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $auditDocument->file_name).'"',
        ]);
    }

    public function download(AuditCollection $auditCollection, AuditDocument $auditDocument): StreamedResponse
    {
        $this->ensureDocumentBelongsTo($auditCollection, $auditDocument);
        abort_unless(Storage::disk('local')->exists($auditDocument->file_path), 404);

        return Storage::disk('local')->download($auditDocument->file_path, $auditDocument->file_name);
    }

    /** @return array<string, mixed> */
    private function validateDocument(Request $request, bool $fileRequired): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'file' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:'.self::ACCEPTED_EXTENSIONS, 'max:20480'],
        ]);
    }

    private function ensureDocumentBelongsTo(AuditCollection $auditCollection, AuditDocument $auditDocument): void
    {
        abort_unless((int) $auditDocument->audit_collection_id === (int) $auditCollection->id, 404);
    }
}
