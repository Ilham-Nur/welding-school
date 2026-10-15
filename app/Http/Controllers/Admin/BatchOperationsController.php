<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchAssignment;
use App\Models\BatchChecklistItem;
use App\Models\BatchSession;
use App\Models\Location;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Services\Training\BatchOperations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BatchOperationsController extends Controller
{
    public function __construct(private readonly BatchOperations $operations) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->status === 'active', 403);
        $batches = TrainingBatch::query()->visibleToOperator($request->user())->with('trainingProgram')
            ->withCount('activePaidEnrollments')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')->orWhere('code', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('operational_status', $request->string('status')))
            ->orderByDesc('start_date')->paginate(12)->withQueryString();

        return view('admin.operations.index', compact('batches'));
    }

    public function show(Request $request, TrainingBatch $batch): View
    {
        $this->operations->authorize($request->user(), $batch);
        $tab = in_array($request->query('tab'), ['participants', 'team', 'schedule', 'materials', 'checklist', 'history'], true) ? $request->query('tab') : 'overview';
        $batch->load(['trainingProgram', 'assignments.user', 'checklistItems.assignee', 'checklistItems.completer']);
        $batch->loadCount('activePaidEnrollments');
        $participants = $batch->enrollments()->with(['user', 'trainingApplication.invoice'])->latest('enrolled_at')->paginate(20, ['*'], 'participants_page')->withQueryString();
        $sessions = $batch->sessions()->with(['trainer', 'location'])->orderBy('starts_at')->paginate(15, ['*'], 'sessions_page')->withQueryString();
        $logs = $batch->activityLogs()->with('actor')->latest('id')->paginate(20, ['*'], 'history_page')->withQueryString();
        $historyUsers = collect();
        $historyLocations = collect();
        foreach ($logs as $log) {
            foreach ([$log->before, $log->after] as $snapshot) {
                $historyUsers = $historyUsers->merge(collect($snapshot)->only(['user_id', 'trainer_id', 'assigned_to'])->values());
                $historyLocations->push($snapshot['location_id'] ?? null);
            }
        }
        $historyUsers = User::whereIn('id', $historyUsers->filter()->unique())->pluck('name', 'id')->all();
        $historyLocations = Location::whereIn('id', $historyLocations->filter()->unique())->pluck('name', 'id')->all();
        $readiness = $this->operations->readiness($batch);
        $materialReadiness = app(\App\Services\Training\MaterialAccess::class)->readiness($batch);
        $materials = $batch->materials()->with(['revision.material', 'session'])->orderBy('position')->orderBy('id')->get();
        $materialSessions = $batch->sessions()->whereNot('status', 'cancelled')->orderBy('starts_at')->get();
        $availableMaterials = $tab === 'materials' && $request->user()->can('operations.materials') ? \App\Models\TrainingMaterialRevision::with('material')->where('status', 'published')->orderBy('title')->get() : collect();
        $staff = $request->user()->can('operations.team')
            ? User::query()->where('status', 'active')->orderBy('name')->get()->filter(fn (User $user) => $user->isInternal() && $user->can('operations.view'))
            : collect();
        $editable = ! in_array($batch->operational_status, ['cancelled', 'completed'], true);

        return view('admin.operations.show', compact('batch', 'tab', 'participants', 'sessions', 'logs', 'readiness', 'staff', 'editable', 'historyUsers', 'historyLocations', 'materials', 'materialSessions', 'availableMaterials', 'materialReadiness'));
    }

    public function assign(Request $request, TrainingBatch $batch): RedirectResponse
    {
        $this->operations->authorize($request->user(), $batch, 'operations.team');
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id'], 'role' => ['required', Rule::in(array_keys(BatchAssignment::ROLES))], 'reason' => ['nullable', 'string', 'max:1000']]);
        $this->operations->mutate($batch, $request->user(), 'operations.team', fn ($batch) => $this->operations->assign($batch, $request->user(), $data));

        return $this->backTo($batch, 'team');
    }

    public function unassign(Request $request, TrainingBatch $batch, BatchAssignment $assignment): RedirectResponse
    {
        $this->operations->authorize($request->user(), $batch, 'operations.team');
        abort_unless($assignment->training_batch_id === $batch->id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->operations->mutate($batch, $request->user(), 'operations.team', function ($batch) use ($request, $assignment, $data): void {
            if ($batch->sessions()->where('trainer_id', $assignment->user_id)->whereNot('status', 'cancelled')->exists()) {
                $this->operations->invalid('assignment', 'Pindahkan atau batalkan sesi Trainer sebelum mengakhiri penugasannya.');
            }
            $this->operations->log($batch, $request->user(), 'team.removed', 'Penugasan '.$assignment->user->name.' diakhiri.', $assignment->toArray(), null, $data['reason']);
            $batch->checklistItems()->where('assigned_to', $assignment->user_id)->update(['assigned_to' => null]);
            $assignment->delete();
            $this->operations->invalidateReady($batch, $request->user());
        });

        return $this->backTo($batch, 'team');
    }

    public function createSession(Request $request, TrainingBatch $batch): View
    {
        return $this->sessionForm($request, $batch, new BatchSession(['status' => 'draft']));
    }

    public function editSession(Request $request, TrainingBatch $batch, BatchSession $session): View
    {
        abort_unless($session->training_batch_id === $batch->id, 404);

        return $this->sessionForm($request, $batch, $session);
    }

    private function sessionForm(Request $request, TrainingBatch $batch, BatchSession $session): View
    {
        $this->operations->authorize($request->user(), $batch, 'operations.schedule');
        abort_if(in_array($batch->operational_status, ['cancelled', 'completed'], true), 403);
        $trainers = $batch->assignments()->whereIn('role', ['lead_trainer', 'assistant_trainer'])->with('user')->get()
            ->filter(fn ($a) => $a->user?->status === 'active' && $a->user->isInternal() && $a->user->can('operations.view'));
        $locations = Location::where('is_active', true)->with('parent')->orderBy('name')->get();

        return view('admin.operations.session-form', compact('batch', 'session', 'trainers', 'locations'));
    }

    public function storeSession(Request $request, TrainingBatch $batch): RedirectResponse
    {
        return $this->saveSession($request, $batch);
    }

    public function updateSession(Request $request, TrainingBatch $batch, BatchSession $session): RedirectResponse
    {
        abort_unless($session->training_batch_id === $batch->id, 404);

        return $this->saveSession($request, $batch, $session);
    }

    public function cancelSession(Request $request, TrainingBatch $batch, BatchSession $session): RedirectResponse
    {
        $this->operations->authorize($request->user(), $batch, 'operations.schedule');
        abort_unless($session->training_batch_id === $batch->id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->operations->mutate($batch, $request->user(), 'operations.schedule', function ($batch) use ($request, $session, $data): void {
            $session->refresh();
            if ($session->execution_status !== 'scheduled') {
                $this->operations->invalid('session', 'Sesi yang sudah dimulai tidak dapat dibatalkan melalui jadwal. Selesaikan pelaksanaannya.');
            }
            $before = $session->toArray();
            $session->update(['status' => 'cancelled']);
            $this->operations->log($batch, $request->user(), 'session.cancelled', 'Sesi '.$session->title.' dibatalkan.', $before, $session->toArray(), $data['reason']);
            $this->operations->invalidateReady($batch, $request->user());
        });

        return $this->backTo($batch, 'schedule');
    }

    private function saveSession(Request $request, TrainingBatch $batch, ?BatchSession $session = null): RedirectResponse
    {
        $this->operations->authorize($request->user(), $batch, 'operations.schedule');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'], 'type' => ['required', Rule::in(array_keys(BatchSession::TYPES))],
            'trainer_id' => ['required', 'integer', 'exists:users,id'], 'location_id' => ['required', 'integer', 'exists:locations,id'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'], 'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'status' => ['required', Rule::in(array_keys(BatchSession::STATUSES))], 'notes' => ['nullable', 'string', 'max:4000'],
            'reason' => [$session ? 'required' : 'nullable', 'string', 'max:1000'],
        ]);
        $this->operations->mutate($batch, $request->user(), 'operations.schedule', fn ($batch) => $this->operations->saveSession($batch, $request->user(), $data, $session));

        return $this->backTo($batch, 'schedule');
    }

    public function checklist(Request $request, TrainingBatch $batch, BatchChecklistItem $item): RedirectResponse
    {
        $this->operations->authorize($request->user(), $batch, 'operations.checklist');
        abort_unless($item->training_batch_id === $batch->id, 404);
        if ($item->key === 'materials' && $batch->materials_managed) {
            throw \Illuminate\Validation\ValidationException::withMessages(['materials' => 'Kesiapan materi dihitung otomatis dari tab Materi.']);
        }
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(BatchChecklistItem::STATUSES))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => [Rule::requiredIf(in_array($request->input('status'), ['completed', 'blocked'], true)), 'nullable', 'string', 'max:2000'],
        ]);
        $this->operations->mutate($batch, $request->user(), 'operations.checklist', function ($batch) use ($request, $item, $data): void {
            if (! empty($data['assigned_to'])) {
                $this->operations->staff((int) $data['assigned_to']);
                if (! $batch->assignments()->where('user_id', $data['assigned_to'])->exists()) {
                    $this->operations->invalid('assigned_to', 'PIC checklist harus menjadi anggota tim batch.');
                }
            }
            $item->refresh();
            $before = $item->toArray();
            $item->update($data + [
                'completed_by' => $data['status'] === 'completed' ? $request->user()->id : null,
                'completed_at' => $data['status'] === 'completed' ? now() : null,
            ]);
            $this->operations->log($batch, $request->user(), 'checklist.updated', $item->title.': '.BatchChecklistItem::STATUSES[$item->status].'.', $before, $item->toArray());
            $this->operations->invalidateReady($batch, $request->user());
        });

        return $this->backTo($batch, 'checklist');
    }

    public function status(Request $request, TrainingBatch $batch): RedirectResponse
    {
        $this->operations->authorize($request->user(), $batch, 'operations.status');
        $data = $request->validate([
            'operational_status' => ['required', Rule::in(['preparation', 'ready', 'running', 'postponed', 'cancelled'])],
            'reason' => ['required_if:operational_status,postponed,cancelled', 'nullable', 'string', 'max:1000'],
        ]);
        $this->operations->mutate($batch, $request->user(), 'operations.status', fn ($batch) => $this->operations->changeStatus($batch, $request->user(), $data['operational_status'], $data['reason'] ?? null));

        return $this->backTo($batch, 'overview');
    }

    private function backTo(TrainingBatch $batch, string $tab): RedirectResponse
    {
        return to_route('admin.operations.show', ['batch' => $batch, 'tab' => $tab])->with('success', 'Operasional batch berhasil diperbarui.');
    }
}
