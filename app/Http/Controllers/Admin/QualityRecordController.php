<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QualityRecord;
use App\Models\QualityRecordFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QualityRecordController extends Controller
{
    private const ACCEPTED_EXTENSIONS = 'pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,jpg,jpeg,png,webp,zip';

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $records = QualityRecord::query()
            ->with(['creator', 'updater'])
            ->withCount('files')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            }))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.quality-records.index', compact('records', 'search'));
    }

    public function create(): View
    {
        return view('admin.quality-records.form', ['record' => new QualityRecord]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRecord($request);
        $name = trim($validated['name']);
        $record = QualityRecord::query()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return to_route('admin.quality-records.show', $record)
            ->with('success', 'Quality Record berhasil ditambahkan.');
    }

    public function show(Request $request, QualityRecord $record): View
    {
        $search = trim((string) $request->query('search'));
        $record->load(['creator', 'updater'])->loadCount('files');
        $files = $record->files()
            ->with('updater')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('label', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('file_name', 'like', '%'.$search.'%');
            }))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.quality-records.show', compact('record', 'files', 'search'));
    }

    public function edit(QualityRecord $record): View
    {
        return view('admin.quality-records.form', compact('record'));
    }

    public function update(Request $request, QualityRecord $record): RedirectResponse
    {
        $validated = $this->validateRecord($request);
        $record->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return to_route('admin.quality-records.show', $record)
            ->with('success', 'Quality Record berhasil diperbarui.');
    }

    public function destroy(QualityRecord $record): RedirectResponse
    {
        $paths = $record->files()->pluck('file_path')->filter()->all();
        $name = $record->name;
        $record->delete();
        Storage::disk('local')->delete($paths);

        return to_route('admin.quality-records.index')
            ->with('success', $name.' berhasil dihapus beserta seluruh file di dalamnya.');
    }

    public function createFile(QualityRecord $record): View
    {
        return view('admin.quality-records.file-form', [
            'record' => $record,
            'file' => new QualityRecordFile,
        ]);
    }

    public function storeFile(Request $request, QualityRecord $record): RedirectResponse
    {
        $validated = $this->validateFile($request, true);
        $upload = $request->file('file');

        QualityRecordFile::query()->create([
            'quality_record_id' => $record->id,
            'label' => trim($validated['label']),
            'description' => $validated['description'] ?? null,
            'file_path' => $upload->store('quality-documents/quality-records', 'local'),
            'file_name' => $upload->getClientOriginalName(),
            'file_type' => strtolower($upload->getClientOriginalExtension()),
            'file_size' => (int) $upload->getSize(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $record->update(['updated_by' => $request->user()->id]);

        return to_route('admin.quality-records.show', $record)
            ->with('success', 'File berhasil diunggah ke Quality Record.');
    }

    public function editFile(QualityRecord $record, QualityRecordFile $file): View
    {
        $this->ensureFileBelongsTo($record, $file);

        return view('admin.quality-records.file-form', compact('record', 'file'));
    }

    public function updateFile(Request $request, QualityRecord $record, QualityRecordFile $file): RedirectResponse
    {
        $this->ensureFileBelongsTo($record, $file);
        $validated = $this->validateFile($request, false);
        $attributes = [
            'label' => trim($validated['label']),
            'description' => $validated['description'] ?? null,
            'updated_by' => $request->user()->id,
        ];
        $oldPath = null;

        if ($request->hasFile('file')) {
            $upload = $request->file('file');
            $oldPath = $file->file_path;
            $attributes += [
                'file_path' => $upload->store('quality-documents/quality-records', 'local'),
                'file_name' => $upload->getClientOriginalName(),
                'file_type' => strtolower($upload->getClientOriginalExtension()),
                'file_size' => (int) $upload->getSize(),
            ];
        }

        $file->update($attributes);
        $record->update(['updated_by' => $request->user()->id]);
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return to_route('admin.quality-records.show', $record)
            ->with('success', 'Informasi file berhasil diperbarui.');
    }

    public function destroyFile(Request $request, QualityRecord $record, QualityRecordFile $file): RedirectResponse
    {
        $this->ensureFileBelongsTo($record, $file);
        $path = $file->file_path;
        $file->delete();
        Storage::disk('local')->delete($path);
        $record->update(['updated_by' => $request->user()->id]);

        return back()->with('success', 'File berhasil dihapus dari Quality Record.');
    }

    public function previewFile(QualityRecord $record, QualityRecordFile $file): StreamedResponse
    {
        $this->ensureFileBelongsTo($record, $file);
        abort_unless($file->canPreview() && Storage::disk('local')->exists($file->file_path), 404);
        $contentTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'txt' => 'text/plain; charset=UTF-8',
            'csv' => 'text/csv; charset=UTF-8',
        ];

        return Storage::disk('local')->response($file->file_path, $file->file_name, [
            'Content-Type' => $contentTypes[strtolower((string) $file->file_type)],
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $file->file_name).'"',
        ]);
    }

    public function downloadFile(QualityRecord $record, QualityRecordFile $file): StreamedResponse
    {
        $this->ensureFileBelongsTo($record, $file);
        abort_unless(Storage::disk('local')->exists($file->file_path), 404);

        return Storage::disk('local')->download($file->file_path, $file->file_name);
    }

    /** @return array<string, mixed> */
    private function validateRecord(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateFile(Request $request, bool $required): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'file' => [$required ? 'required' : 'nullable', 'file', 'mimes:'.self::ACCEPTED_EXTENSIONS, 'max:20480'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'quality-record';
        $slug = $base;
        $counter = 2;

        while (QualityRecord::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function ensureFileBelongsTo(QualityRecord $record, QualityRecordFile $file): void
    {
        abort_unless((int) $file->quality_record_id === (int) $record->id, 404);
    }
}
