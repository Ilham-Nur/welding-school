<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\BatchSession;
use App\Models\SessionAttendance;
use App\Models\TrainingBatch;
use App\Services\Training\SessionAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function __construct(private SessionAttendanceService $attendance) {}

    public function index(Request $request, TrainingBatch $batch)
    {
        $this->attendance->participant($request->user(), $batch);
        $sessions = $batch->sessions()->where('status', 'published')->with(['trainer', 'location',
            'attendances' => fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('user_id', $request->user()->id)),
        ])->orderBy('starts_at')->get();
        $finalized = $sessions->filter(fn ($s) => $s->execution_status === 'completed' && $s->attendance_finalized_at && $s->attendances->isNotEmpty());
        $present = $finalized->filter(fn ($s) => $s->attendances->first()->status === 'present')->count();
        $summary = [
            'sessions' => $finalized->count(), 'present' => $present,
            'percentage' => $finalized->isEmpty() ? null : round($present / $finalized->count() * 100, 1),
            'practice_minutes' => $finalized->where('type', 'practice')->sum(fn ($s) => $s->attendances->first()->attended_minutes),
        ];

        return response()->view('participant.attendance.index', compact('batch', 'sessions', 'summary'))->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, TrainingBatch $batch, BatchSession $session)
    {
        $this->attendance->participant($request->user(), $batch);
        abort_unless($session->training_batch_id === $batch->id && $session->status === 'published', 404);
        $record = $session->attendances()->whereHas('enrollment', fn ($q) => $q->where('user_id', $request->user()->id))->first();

        return response()->view('participant.attendance.capture', compact('batch', 'session', 'record'))->header('Cache-Control', 'private, no-store');
    }

    public function challenge(Request $request, TrainingBatch $batch, BatchSession $session)
    {
        $data = $request->validate(['kind' => ['required', Rule::in(['in', 'out'])]]);

        return response()->json($this->attendance->challenge($batch, $session, $request->user(), $data['kind']))->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, TrainingBatch $batch, BatchSession $session)
    {
        $this->attendance->participant($request->user(), $batch);
        abort_unless($session->training_batch_id === $batch->id, 404);
        $data = $request->validate([
            'kind' => ['required', Rule::in(['in', 'out'])],
            'token' => ['required', 'string', 'size:64'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0', 'max:10000'],
            'location_timestamp' => ['required', 'numeric', 'min:0'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg', 'max:2048'],
        ]);
        $this->attendance->capture($batch, $session, $request->user(), $data, $request->file('photo'));

        return response()->json(['message' => 'Selfie dan lokasi tercatat. Absensi akan diperiksa trainer.'])->header('Cache-Control', 'no-store');
    }

    public function photo(Request $request, TrainingBatch $batch, BatchSession $session, SessionAttendance $record, string $kind)
    {
        $this->attendance->participant($request->user(), $batch);
        abort_unless($session->training_batch_id === $batch->id && $session->status === 'published'
            && $record->batch_session_id === $session->id && $record->enrollment->user_id === $request->user()->id, 404);

        return $this->attendance->photo($record, $kind);
    }
}
