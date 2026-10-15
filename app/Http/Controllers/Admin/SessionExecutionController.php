<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchSession;
use App\Models\SessionAttendance;
use App\Models\TrainingBatch;
use App\Services\Training\SessionAttendanceService;
use App\Services\Training\GoogleMapsLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SessionExecutionController extends Controller
{
    public function __construct(private SessionAttendanceService $attendance) {}

    public function show(Request $request, TrainingBatch $batch, BatchSession $session)
    {
        $this->attendance->operator($request->user(), $batch, $session);
        $session->load(['trainer', 'location', 'attendances.enrollment.user']);

        return response()->view('admin.operations.execution', compact('batch', 'session'))->header('Cache-Control', 'private, no-store');
    }

    public function area(Request $request, TrainingBatch $batch, BatchSession $session)
    {
        $this->attendance->operator($request->user(), $batch, $session, 'attendance.area');
        $data = $request->validate([
            'attendance_latitude' => ['required', 'numeric', 'between:-90,90'],
            'attendance_longitude' => ['required', 'numeric', 'between:-180,180'],
            'attendance_radius_meters' => ['required', 'integer', 'between:25,1000'],
            'late_grace_minutes' => ['required', 'integer', 'between:0,120'],
            'attendance_area_confirmed' => ['accepted'],
        ]);
        unset($data['attendance_area_confirmed']);
        $this->attendance->area($batch, $session, $request->user(), $data);

        return $this->back($batch, $session, 'Area absensi disimpan.');
    }

    public function resolveArea(Request $request, TrainingBatch $batch, BatchSession $session, GoogleMapsLocation $maps)
    {
        $this->attendance->operator($request->user(), $batch, $session, 'attendance.area');
        abort_if($session->execution_status !== 'scheduled' || $session->status === 'cancelled'
            || in_array($batch->operational_status, ['cancelled', 'completed', 'postponed'], true), 422, 'Area sudah terkunci atau tidak dapat diatur.');
        $data = $request->validate(['maps_location' => ['required', 'string', 'max:2048']]);

        return response()->json($maps->resolve($data['maps_location']))->header('Cache-Control', 'private, no-store');
    }

    public function start(Request $request, TrainingBatch $batch, BatchSession $session)
    {
        $this->attendance->start($batch, $session, $request->user());

        return $this->back($batch, $session, 'Sesi dimulai. Peserta dapat absen masuk menggunakan selfie.');
    }

    public function complete(Request $request, TrainingBatch $batch, BatchSession $session)
    {
        $this->attendance->operator($request->user(), $batch, $session, 'operations.execution');
        $data = $request->validate(['execution_notes' => ['required', 'string', 'max:4000']]);
        $this->attendance->complete($batch, $session, $request->user(), $data['execution_notes']);

        return $this->back($batch, $session, 'Sesi selesai. Periksa seluruh absensi lalu finalisasi.');
    }

    public function review(Request $request, TrainingBatch $batch, BatchSession $session, SessionAttendance $record)
    {
        $this->attendance->operator($request->user(), $batch, $session, $session->attendance_finalized_at ? 'attendance.correct' : 'attendance.manage');
        abort_unless($record->batch_session_id === $session->id, 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(['present', 'permission', 'sick', 'absent'])],
            'review_notes' => ['required', 'string', 'max:1000'],
        ]);
        $this->attendance->review($batch, $session, $record, $request->user(), $data);

        return $this->back($batch, $session, 'Hasil pemeriksaan absensi disimpan.');
    }

    public function finalize(Request $request, TrainingBatch $batch, BatchSession $session)
    {
        $this->attendance->finalize($batch, $session, $request->user());

        return $this->back($batch, $session, 'Absensi difinalisasi dan masuk rekap peserta.');
    }

    public function photo(Request $request, TrainingBatch $batch, BatchSession $session, SessionAttendance $record, string $kind)
    {
        $this->attendance->operator($request->user(), $batch, $session);
        abort_unless($record->batch_session_id === $session->id, 404);

        return $this->attendance->photo($record, $kind);
    }

    private function back(TrainingBatch $batch, BatchSession $session, string $message)
    {
        return to_route('admin.operations.execution.show', [$batch, $session])->with('success', $message);
    }
}
