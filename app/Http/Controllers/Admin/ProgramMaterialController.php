<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramTrainingMaterial;
use App\Models\TrainingMaterialRevision;
use App\Models\TrainingProgram;
use App\Services\Training\MaterialAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgramMaterialController extends Controller
{
    public function __construct(private MaterialAccess $access) {}

    public function index(Request $request)
    {
        $this->access->internal($request->user(), 'training-materials.view');

        return view('admin.training-materials.programs', ['programs' => TrainingProgram::withCount('materialPlan')->orderBy('title')->get()]);
    }

    public function show(Request $request, TrainingProgram $program)
    {
        $this->access->internal($request->user(), 'training-materials.view');
        $items = $program->materialPlan()->with('revision.material')->orderBy('position')->orderBy('id')->get();
        $available = TrainingMaterialRevision::with('material')->where('status', 'published')->orderBy('title')->get();

        return view('admin.training-materials.program', compact('program', 'items', 'available'));
    }

    public function edit(Request $request, TrainingProgram $program, ProgramTrainingMaterial $item)
    {
        $this->access->internal($request->user(), 'training-materials.manage');
        abort_unless($item->training_program_id === $program->id, 404);
        $available = TrainingMaterialRevision::with('material')->where('status', 'published')->orderBy('title')->get();

        return view('admin.training-materials.program-form', compact('program', 'item', 'available'));
    }

    public function save(Request $request, TrainingProgram $program, ?ProgramTrainingMaterial $item = null)
    {
        $this->access->internal($request->user(), 'training-materials.manage');
        if ($item) {
            abort_unless($item->training_program_id === $program->id, 404);
        }
        $data = $request->validate(['training_material_revision_id' => ['required', 'integer', 'exists:training_material_revisions,id'], 'position' => ['required', 'integer', 'min:1', 'max:9999']]);
        DB::transaction(function () use ($program, $item, $data) {
            TrainingProgram::whereKey($program->id)->lockForUpdate()->firstOrFail();
            $revision = TrainingMaterialRevision::lockForUpdate()->findOrFail($data['training_material_revision_id']);
            abort_unless($revision->status === 'published' || ($item && $item->training_material_revision_id === $revision->id && $revision->published_at), 422);
            $data['method'] = $revision->method;
            $data['duration_minutes'] = $revision->duration_minutes;
            if ($item) {
                $item->update($data);
            } else {
                $program->materialPlan()->create($data);
            }
        });

        return to_route('admin.training-materials.program', $program)->with('success', 'Susunan materi program disimpan. Batch yang sudah mengambil materi tetap memakai susunannya.');
    }

    public function destroy(Request $request, TrainingProgram $program, ProgramTrainingMaterial $item)
    {
        $this->access->internal($request->user(), 'training-materials.manage');
        abort_unless($item->training_program_id === $program->id, 404);
        DB::transaction(function () use ($program, $item) {
            TrainingProgram::whereKey($program->id)->lockForUpdate()->firstOrFail();
            $item->delete();
        });

        return back()->with('success', 'Materi dilepas dari susunan program.');
    }
}
