<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditDocumentController extends Controller
{
    private const ACCEPTED_EXTENSIONS = 'pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,jpg,jpeg,png,webp,zip';

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $documents = AuditDocument::query()
            ->with('updater')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('file_name', 'like', '%'.$search.'%');
            }))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.quality-documents.audit.index', compact('documents', 'search'));
    }

    public function create(): View
    {
        return view('admin.quality-documents.audit.form', ['document' => new AuditDocument]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDocument($request, true);
        $file = $request->file('file');
        $path = $file->store('quality-documents/audit', 'local');

        AuditDocument::query()->create([
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'file_size' => (int) $file->getSize(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return to_route('admin.quality-documents.audit.index')->with('success', 'Dokumen audit berhasil ditambahkan.');
    }

    public function edit(AuditDocument $auditDocument): View
    {
        return view('admin.quality-documents.audit.form', ['document' => $auditDocument]);
    }

    public function update(Request $request, AuditDocument $auditDocument): RedirectResponse
    {
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

        return to_route('admin.quality-documents.audit.index')->with('success', 'Dokumen audit berhasil diperbarui.');
    }

    public function destroy(AuditDocument $auditDocument): RedirectResponse
    {
        $path = $auditDocument->file_path;
        $auditDocument->delete();
        Storage::disk('local')->delete($path);

        return back()->with('success', 'Dokumen audit berhasil dihapus.');
    }

    public function preview(AuditDocument $auditDocument): StreamedResponse
    {
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

    public function download(AuditDocument $auditDocument): StreamedResponse
    {
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
}
