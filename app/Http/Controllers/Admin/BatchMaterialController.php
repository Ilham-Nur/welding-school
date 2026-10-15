<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchTrainingMaterial;
use App\Models\TrainingBatch;
use App\Models\TrainingMaterialFile;
use App\Models\TrainingMaterialRevision;
use App\Models\TrainingProgram;
use App\Services\Training\BatchOperations;
use App\Services\Training\MaterialAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BatchMaterialController extends Controller
{
    public function __construct(private BatchOperations $operations, private MaterialAccess $access) {}

    private function backTo(TrainingBatch $batch)
    {
        return to_route('admin.operations.show', ['batch' => $batch, 'tab' => 'materials'])->with('success', 'Materi batch diperbarui.');
    }

    public function import(Request $request, TrainingBatch $batch)
    {
        $this->operations->mutate($batch, $request->user(), 'operations.materials', function ($batch) use ($request) {
            if ($batch->materials()->exists()) {
                throw ValidationException::withMessages(['materials' => 'Susunan program hanya dapat diambil saat daftar materi batch kosong. Tambahkan materi berikutnya secara manual dari library.']);
            }
            $program = TrainingProgram::lockForUpdate()->findOrFail($batch->training_program_id);
            $plan = $program->materialPlan()->with('revision')->orderBy('position')->orderBy('id')->get();
            if ($plan->isEmpty()) {
                throw ValidationException::withMessages(['materials' => 'Susunan materi program masih kosong.']);
            }
            $existing = $batch->materials()->pluck('training_material_revision_id')->all();
            $count = 0;
            foreach ($plan as $row) {
                if (in_array($row->training_material_revision_id, $existing, true)) {
                    continue;
                }
                $revision = TrainingMaterialRevision::lockForUpdate()->findOrFail($row->training_material_revision_id);
                if ($revision->status !== 'published') {
                    throw ValidationException::withMessages(['materials' => 'Susunan program memuat revisi arsip. Pilih revisi terbit pada susunan program terlebih dahulu.']);
                }
                $batch->materials()->create(['training_material_revision_id' => $revision->id, 'position' => $row->position, 'method' => $revision->method, 'duration_minutes' => $revision->duration_minutes]);
                $count++;
            }
            $batch->update(['materials_managed' => true]);
            if ($count) {
                $this->operations->invalidateReady($batch, $request->user());
            }
            $this->operations->log($batch, $request->user(), 'materials.imported', "$count materi diambil dari susunan program.");
        });

        return $this->backTo($batch);
    }

    public function store(Request $request, TrainingBatch $batch)
    {
        $this->operations->authorize($request->user(), $batch, 'operations.materials');
        $data = $request->validate(['training_material_revision_id' => ['required', 'integer', 'exists:training_material_revisions,id']]);
        $this->operations->mutate($batch, $request->user(), 'operations.materials', function ($batch) use ($request, $data) {
            $revision = TrainingMaterialRevision::lockForUpdate()->findOrFail($data['training_material_revision_id']);
            if ($revision->status !== 'published') {
                throw ValidationException::withMessages(['materials' => 'Pilih revisi materi yang berstatus Terbit.']);
            }
            $item = $batch->materials()->create(['training_material_revision_id' => $revision->id, 'position' => ($batch->materials()->max('position') ?? 0) + 1, 'method' => $revision->method, 'duration_minutes' => $revision->duration_minutes]);
            $batch->update(['materials_managed' => true]);
            $this->operations->log($batch, $request->user(), 'materials.added', 'Materi '.$revision->title.' ditambahkan.', null, $item->toArray());
            $this->operations->invalidateReady($batch, $request->user());
        });

        return $this->backTo($batch);
    }

    public function edit(Request $request, TrainingBatch $batch, BatchTrainingMaterial $item)
    {
        $this->operations->authorize($request->user(), $batch, 'operations.materials');
        abort_unless($item->training_batch_id === $batch->id, 404);
        abort_if(in_array($batch->operational_status, ['cancelled', 'completed'], true), 403);
        $sessions = $batch->sessions()->whereNot('status', 'cancelled')->orderBy('starts_at')->get();

        return view('admin.training-materials.batch-form', compact('batch', 'item', 'sessions'));
    }

    public function update(Request $request, TrainingBatch $batch, BatchTrainingMaterial $item)
    {
        $this->operations->authorize($request->user(), $batch, 'operations.materials');
        abort_unless($item->training_batch_id === $batch->id, 404);
        $data = $request->validate(['batch_session_id' => ['nullable', 'integer', Rule::exists('batch_sessions', 'id')->where('training_batch_id', $batch->id)->whereNot('status', 'cancelled')],
            'position' => ['required', 'integer', 'min:1', 'max:9999'],
            'participant_visible' => ['required', 'boolean']]);
        $this->operations->mutate($batch, $request->user(), 'operations.materials', function ($batch) use ($request, $item, $data) {
            $item->refresh();
            $session = ! empty($data['batch_session_id']) ? $batch->sessions()->whereKey($data['batch_session_id'])->whereNot('status', 'cancelled')->first() : null;
            if (! empty($data['batch_session_id']) && ! $session) {
                throw ValidationException::withMessages(['batch_session_id' => 'Sesi tidak tersedia.']);
            }
            $missing = collect($this->access->publicationChecks($item, $session))->where('done', false);
            if ($data['participant_visible'] && $missing->isNotEmpty()) {
                throw ValidationException::withMessages(['participant_visible' => $missing->pluck('message')->all()]);
            }
            $data['method'] = $item->revision->method;
            $data['duration_minutes'] = $item->revision->duration_minutes;
            $before = $item->toArray();
            $item->update($data);
            $this->operations->log($batch, $request->user(), 'materials.updated', 'Pengaturan materi '.$item->revision->title.' diperbarui.', $before, $item->toArray());
            $this->operations->invalidateReady($batch, $request->user());
        });

        return $this->backTo($batch);
    }

    public function destroy(Request $request, TrainingBatch $batch, BatchTrainingMaterial $item)
    {
        abort_unless($item->training_batch_id === $batch->id, 404);
        $this->operations->mutate($batch, $request->user(), 'operations.materials', function ($batch) use ($request, $item) {
            $item->refresh();
            $before = $item->toArray();
            $item->delete();
            $this->operations->log($batch, $request->user(), 'materials.removed', 'Materi dilepas dari batch.', $before);
            $this->operations->invalidateReady($batch, $request->user());
        });

        return $this->backTo($batch);
    }

    public function show(Request $request, TrainingBatch $batch, BatchTrainingMaterial $item)
    {
        $this->operations->authorize($request->user(), $batch);
        abort_unless($item->training_batch_id === $batch->id, 404);
        $item->load(['revision.material', 'revision.files', 'session']);

        return view('admin.training-materials.batch-detail', compact('batch', 'item'));
    }

    public function file(Request $request, TrainingBatch $batch, BatchTrainingMaterial $item, TrainingMaterialFile $file)
    {
        $this->operations->authorize($request->user(), $batch);
        abort_unless($item->training_batch_id === $batch->id && $file->training_material_revision_id === $item->training_material_revision_id, 404);

        return $this->access->file($file, $request->boolean('preview'));
    }
}
