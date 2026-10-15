<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchSession;
use App\Models\PracticeSubmission;
use App\Models\TrainingBatch;
use App\Services\Training\PracticeLogbook;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PracticeController extends Controller
{
    public function __construct(private PracticeLogbook $practice) {}

    public function show(Request $request, TrainingBatch $batch, BatchSession $session)
    {
        $this->practice->operator($request->user(), $batch, $session);
        $task = $session->practiceTask()->with(['submissions.enrollment.user', 'submissions.events'])->first();
        $enrollments = $batch->activePaidEnrollments()->with('user')->get();

        return response()->view('admin.operations.practice', compact('batch', 'session', 'task', 'enrollments'))->header('Cache-Control', 'private, no-store');
    }

    public function configure(Request $request, TrainingBatch $batch, BatchSession $session)
    {
        $this->practice->operator($request->user(), $batch, $session, 'practice.manage');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'], 'instructions' => ['required', 'string', 'max:4000'],
            'photo_required' => ['required', 'boolean'], 'effective_minutes' => ['nullable', 'integer', 'between:1,1440'],
        ]);
        $data['effective_minutes'] = $data['effective_minutes'] ?? null;
        $this->practice->configure($request->user(), $batch, $session, $data);

        return to_route('admin.operations.practice.show', [$batch, $session])->with('success', 'Aktivitas diterbitkan. Peserta cukup mengisi hasil dan bukti yang diminta.');
    }

    public function review(Request $request, TrainingBatch $batch, BatchSession $session, PracticeSubmission $record)
    {
        $this->practice->operator($request->user(), $batch, $session, 'practice.review');
        abort_unless($record->task->batch_session_id === $session->id, 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'revision'])], 'version' => ['required', 'integer', 'min:1'],
            'review_notes' => ['required_if:status,revision', 'nullable', 'string', 'max:1000'],
            'verified_minutes' => ['nullable', 'integer', 'between:1,1440'],
        ]);
        $this->practice->review($request->user(), $batch, $session, $record, $data);

        return to_route('admin.operations.practice.show', [$batch, $session])->with('success', 'Pemeriksaan hasil tersimpan.');
    }

    public function photo(Request $request, TrainingBatch $batch, BatchSession $session, PracticeSubmission $record)
    {
        $this->practice->operator($request->user(), $batch, $session);
        abort_unless($record->task->batch_session_id === $session->id && $record->status !== 'draft', 404);

        return $this->practice->photo($record);
    }
}
