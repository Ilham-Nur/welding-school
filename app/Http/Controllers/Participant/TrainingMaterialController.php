<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\BatchTrainingMaterial;
use App\Models\TrainingBatch;
use App\Models\TrainingMaterialFile;
use App\Services\Training\MaterialAccess;
use Illuminate\Http\Request;

class TrainingMaterialController extends Controller
{
    public function __construct(private MaterialAccess $access) {}

    private function visible(TrainingBatch $batch)
    {
        return $batch->materials()->where('participant_visible', true)->whereHas('session', fn ($q) => $q->where('status', 'published'))
            ->whereHas('revision', fn ($q) => $q->whereNotNull('published_at'));
    }

    public function index(Request $request, TrainingBatch $batch)
    {
        $this->access->participant($request->user(), $batch);
        $items = $this->visible($batch)->with(['revision.material', 'revision.files' => fn ($q) => $q->where('audience', 'participant'), 'session'])
            ->orderBy('position')->orderBy('id')->get();

        return response()->view('participant.materials', compact('batch', 'items'))->header('Cache-Control', 'private, no-store');
    }

    public function file(Request $request, TrainingBatch $batch, BatchTrainingMaterial $item, TrainingMaterialFile $file)
    {
        $this->access->participant($request->user(), $batch);
        abort_unless($this->visible($batch)->whereKey($item->id)->exists() && $file->training_material_revision_id === $item->training_material_revision_id && $file->audience === 'participant', 404);

        return $this->access->file($file, $request->boolean('preview'));
    }
}
