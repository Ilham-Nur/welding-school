<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingMaterial;
use App\Models\TrainingMaterialFile;
use App\Models\TrainingMaterialRevision;
use App\Models\TrainingProgram;
use App\Services\Training\MaterialAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingMaterialController extends Controller
{
    public function __construct(private MaterialAccess $access) {}

    private function authorizeRequest(Request $request, string $permission = 'training-materials.view'): void
    {
        $this->access->internal($request->user(), $permission);
    }

    private function seeRevision(Request $request, TrainingMaterialRevision $revision): void
    {
        $this->authorizeRequest($request);
        abort_if($revision->status === 'draft' && ! $request->user()->canAny(['training-materials.manage', 'training-materials.publish']), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeRequest($request);
        $revisions = TrainingMaterialRevision::with(['material.programs'])
            ->when(! $request->user()->canAny(['training-materials.manage', 'training-materials.publish']), fn ($q) => $q->whereNot('status', 'draft'))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%')->orWhereHas('material', fn ($q) => $q->where('code', 'like', '%'.$request->string('search').'%'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->string('kind')))
            ->when($request->filled('program'), fn ($q) => $q->whereHas('material.programs', fn ($q) => $q->where('training_programs.id', $request->integer('program'))))
            ->latest('updated_at')->paginate(20)->withQueryString();
        $programs = TrainingProgram::orderBy('title')->get();

        return view('admin.training-materials.index', compact('revisions', 'programs'));
    }

    public function create(Request $request)
    {
        $this->authorizeRequest($request, 'training-materials.manage');

        return view('admin.training-materials.form', ['revision' => new TrainingMaterialRevision(['method' => 'theory', 'kind' => 'theory', 'duration_minutes' => 60]), 'programs' => TrainingProgram::orderBy('title')->get()]);
    }

    private function validated(Request $request, bool $new): array
    {
        return $request->validate([
            'code' => [$new ? 'required' : 'nullable', 'string', 'max:80', Rule::unique('training_materials', 'code')],
            'title' => ['required', 'string', 'max:255'], 'kind' => ['required', Rule::in(array_keys(TrainingMaterialRevision::KINDS))],
            'objectives' => ['required', 'string', 'max:10000'], 'topics' => ['nullable', 'string', 'max:10000'],
            'method' => ['required', Rule::in(array_keys(TrainingMaterialRevision::METHODS))], 'guidance' => ['nullable', 'string', 'max:20000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:10000'], 'change_notes' => ['nullable', 'string', 'max:2000'],
            'program_ids' => ['nullable', 'array', 'max:100'], 'program_ids.*' => ['integer', 'distinct', 'exists:training_programs,id'],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeRequest($request, 'training-materials.manage');
        $data = $this->validated($request, true);
        $revision = DB::transaction(function () use ($data, $request) {
            $material = TrainingMaterial::create(['code' => $data['code']]);
            $material->programs()->sync($data['program_ids'] ?? []);

            return $material->revisions()->create(collect($data)->except(['code', 'program_ids'])->all() + ['number' => 1, 'created_by' => $request->user()->id]);
        });

        return to_route('admin.training-materials.show', $revision)->with('success', 'Draft materi dibuat. Tambahkan file sebelum diterbitkan.');
    }

    public function show(Request $request, TrainingMaterialRevision $revision)
    {
        $this->seeRevision($request, $revision);
        $revision->load(['material.programs', 'files', 'author']);
        $history = $revision->material->revisions()->with('author')->when(! $request->user()->canAny(['training-materials.manage', 'training-materials.publish']), fn ($q) => $q->whereNot('status', 'draft'))->orderByDesc('number')->get();

        $usages = $request->user()->can('operations.view')
            ? $revision->batchItems()->whereHas('batch', fn ($query) => $query->visibleToOperator($request->user()))->with('batch.trainingProgram')->get()->unique('training_batch_id')
            : collect();

        return view('admin.training-materials.show', compact('revision', 'history', 'usages'));
    }

    public function edit(Request $request, TrainingMaterialRevision $revision)
    {
        $this->authorizeRequest($request, 'training-materials.manage');
        abort_unless($revision->status === 'draft', 403);

        return view('admin.training-materials.form', ['revision' => $revision->load('material.programs'), 'programs' => TrainingProgram::orderBy('title')->get()]);
    }

    private function draft(TrainingMaterialRevision $revision, callable $callback)
    {
        return DB::transaction(function () use ($revision, $callback) {
            TrainingMaterial::whereKey($revision->training_material_id)->lockForUpdate()->firstOrFail();
            $revision = TrainingMaterialRevision::lockForUpdate()->findOrFail($revision->id);
            abort_unless($revision->status === 'draft', 403);

            return $callback($revision);
        });
    }

    public function update(Request $request, TrainingMaterialRevision $revision)
    {
        $this->authorizeRequest($request, 'training-materials.manage');
        $data = $this->validated($request, false);
        $this->draft($revision, function ($revision) use ($data) {
            $revision->update(collect($data)->except(['code', 'program_ids'])->all());
            $revision->material->programs()->sync($data['program_ids'] ?? []);
        });

        return to_route('admin.training-materials.show', $revision)->with('success', 'Draft materi disimpan.');
    }

    public function revise(Request $request, TrainingMaterialRevision $revision)
    {
        $this->authorizeRequest($request, 'training-materials.manage');
        $new = DB::transaction(function () use ($revision, $request) {
            $material = TrainingMaterial::lockForUpdate()->findOrFail($revision->training_material_id);
            abort_unless($revision->published_at, 422);
            if ($material->revisions()->where('status', 'draft')->exists()) {
                throw ValidationException::withMessages(['revision' => 'Selesaikan draft revisi yang sudah ada terlebih dahulu.']);
            }
            $new = $revision->replicate(['number', 'status', 'published_by', 'published_at', 'created_by', 'change_notes']);
            $new->fill(['number' => $material->revisions()->max('number') + 1, 'status' => 'draft', 'created_by' => $request->user()->id]);
            $new->save();
            foreach ($revision->files as $file) {
                $copy = $file->replicate();
                $copy->training_material_revision_id = $new->id;
                $copy->save();
            }

            return $new;
        });

        return to_route('admin.training-materials.edit', $new)->with('success', 'Draft revisi dibuat. Revisi pada batch yang sudah ada tetap tersimpan.');
    }

    public function publish(Request $request, TrainingMaterialRevision $revision)
    {
        $this->authorizeRequest($request, 'training-materials.publish');
        $this->draft($revision, function ($revision) use ($request) {
            if (! $revision->files()->exists() || $revision->files->contains(fn ($file) => ! Storage::disk('local')->exists($file->path))) {
                throw ValidationException::withMessages(['files' => 'Materi harus memiliki file yang tersedia sebelum diterbitkan.']);
            }
            $revision->material->revisions()->where('status', 'published')->update(['status' => 'archived']);
            $revision->update(['status' => 'published', 'published_at' => now(), 'published_by' => $request->user()->id]);
        });

        return back()->with('success', 'Materi diterbitkan. Susunan program dan batch tetap memakai revisi yang sebelumnya dipilih.');
    }

    public function archive(Request $request, TrainingMaterialRevision $revision)
    {
        $this->authorizeRequest($request, 'training-materials.publish');
        DB::transaction(function () use ($revision) {
            TrainingMaterial::whereKey($revision->training_material_id)->lockForUpdate()->firstOrFail();
            $revision = TrainingMaterialRevision::lockForUpdate()->findOrFail($revision->id);
            abort_unless($revision->status === 'published', 422);
            $revision->update(['status' => 'archived']);
        });

        return back()->with('success', 'Materi diarsipkan. Batch yang sudah menggunakannya tetap dapat mengakses revisi ini.');
    }

    public function upload(Request $request, TrainingMaterialRevision $revision)
    {
        $this->authorizeRequest($request, 'training-materials.manage');
        $data = $request->validate(['label' => ['required', 'string', 'max:255'], 'audience' => ['required', Rule::in(['internal', 'participant'])],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,txt', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,txt', 'max:20480']]);
        $path = null;
        try {
            $this->draft($revision, function ($revision) use ($request, $data, &$path) {
                $upload = $request->file('file');
                $path = $upload->store('training-materials', 'local');
                if (! $path) {
                    throw new \RuntimeException('File tidak berhasil disimpan.');
                }
                $revision->files()->create(['label' => $data['label'], 'audience' => $data['audience'], 'path' => $path, 'original_name' => $upload->getClientOriginalName(), 'extension' => strtolower($upload->getClientOriginalExtension()), 'size' => $upload->getSize()]);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            } throw $e;
        }

        return back()->with('success', 'File ditambahkan ke draft.');
    }

    public function removeFile(Request $request, TrainingMaterialRevision $revision, TrainingMaterialFile $file)
    {
        $this->authorizeRequest($request, 'training-materials.manage');
        abort_unless($file->training_material_revision_id === $revision->id, 404);
        $this->draft($revision, fn () => $file->delete());

        // The blob may also belong to a published revision; retain it for historical access.
        return back()->with('success', 'File dilepas dari draft.');
    }

    public function file(Request $request, TrainingMaterialRevision $revision, TrainingMaterialFile $file)
    {
        $this->seeRevision($request, $revision);
        abort_unless($file->training_material_revision_id === $revision->id, 404);

        return $this->access->file($file, $request->boolean('preview'));
    }
}
