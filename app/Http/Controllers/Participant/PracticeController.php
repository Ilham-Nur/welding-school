<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\PracticeSubmission;
use App\Models\SessionPracticeTask;
use App\Models\TrainingBatch;
use App\Services\Training\PracticeLogbook;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PracticeController extends Controller
{
    public function __construct(private PracticeLogbook $practice) {}

    public function index(Request $request, TrainingBatch $batch)
    {
        $enrollment = $this->practice->participant($request->user(), $batch);
        $tasks = SessionPracticeTask::whereHas('session', fn ($q) => $q->where('training_batch_id', $batch->id)->where('status', 'published'))
            ->whereNotNull('published_at')->with(['session.trainer', 'session.location',
                'submissions' => fn ($q) => $q->where('enrollment_id', $enrollment->id),
            ])->get()->sortBy(fn ($task) => $task->session->starts_at);
        $records = $tasks->flatMap->submissions;
        $summary = ['submitted' => $records->where('status', 'submitted')->count(), 'revision' => $records->where('status', 'revision')->count(),
            'accepted' => $records->where('status', 'accepted')->count(), 'minutes' => $records->where('status', 'accepted')->sum('verified_minutes')];

        return response()->view('participant.practice.index', compact('batch', 'tasks', 'summary'))->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, TrainingBatch $batch, SessionPracticeTask $task)
    {
        $enrollment = $this->practice->participant($request->user(), $batch);
        $this->practice->task($batch, $task);
        $task->load(['session.trainer', 'session.location']);
        $record = $task->submissions()->where('enrollment_id', $enrollment->id)->with('events')->first();
        $editable = $batch->operational_status === 'running' && in_array($task->session->execution_status, ['running', 'completed'], true)
            && (! $record || in_array($record->status, ['draft', 'revision'], true));

        return response()->view('participant.practice.show', compact('batch', 'task', 'record', 'editable'))->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, TrainingBatch $batch, SessionPracticeTask $task)
    {
        $this->practice->participant($request->user(), $batch);
        $this->practice->task($batch, $task);
        $data = $request->validate([
            'action' => ['required', Rule::in(['draft', 'submit'])], 'version' => ['required', 'integer', 'min:0'], 'request_id' => ['required', 'uuid'],
            'outcome' => ['nullable', Rule::in(array_keys(PracticeSubmission::OUTCOMES))],
            'obstacle' => ['nullable', Rule::in(array_keys(PracticeSubmission::OBSTACLES))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ]);
        $record = $this->practice->save($request->user(), $batch, $task, $data, $request->file('photo'));
        if (! $request->expectsJson()) {
            return to_route('participant.practice.show', [$batch, $task])->with('success', $data['action'] === 'submit' ? 'Hasil dikirim untuk diperiksa.' : 'Draf tersimpan.');
        }

        return response()->json(['message' => $data['action'] === 'submit' ? 'Hasil dikirim untuk diperiksa.' : 'Draf tersimpan.',
            'record' => ['version' => $record->version, 'status' => $record->status,
                'photo_url' => $record->photo_path ? route('participant.practice.photo', [$batch, $task]) : null],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function photo(Request $request, TrainingBatch $batch, SessionPracticeTask $task)
    {
        $enrollment = $this->practice->participant($request->user(), $batch);
        $this->practice->task($batch, $task);

        return $this->practice->photo($task->submissions()->where('enrollment_id', $enrollment->id)->firstOrFail());
    }
}
