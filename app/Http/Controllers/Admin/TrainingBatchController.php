<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Services\Training\BatchOperations;
use App\Services\Training\TrainingPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingBatchController extends Controller
{
    public function index(Request $request): View
    {
        $batches = TrainingBatch::query()
            ->with('trainingProgram')
            ->withCount(['applications', 'enrollments', 'assignments', 'sessions', 'activityLogs'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.trim((string) $request->string('search')).'%';
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('code', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhereHas('trainingProgram', fn ($program) => $program->where('title', 'like', $search));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('start_date')
            ->paginate(12)
            ->withQueryString();

        return view('admin.batches.index', compact('batches'));
    }

    public function create(): RedirectResponse
    {
        return to_route('admin.budgets.create')->with('success', 'Susun RAB dan rencana batch. Batch dibuat setelah persetujuan.');
    }

    public function store(Request $request): RedirectResponse
    {
        throw ValidationException::withMessages(['batch' => 'Batch baru dibuat otomatis setelah RAB disetujui. Susun rencana batch melalui Anggaran / RAB.']);
    }

    public function edit(TrainingBatch $batch): View
    {
        return view('admin.batches.form', [
            'batch' => $batch,
            'programs' => $this->programs(),
        ]);
    }

    public function update(Request $request, TrainingBatch $batch): RedirectResponse
    {
        $data = $this->validated($request, $batch);
        DB::transaction(function () use ($request, $batch, $data): void {
            $batch = TrainingBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($batch->training_budget_id) {
                if ((int) $data['training_program_id'] !== $batch->training_program_id) {
                    throw ValidationException::withMessages(['training_program_id' => 'Program batch yang terhubung RAB tidak dapat diganti.']);
                }
                $target = collect($batch->trainingBudget->inputs['batches'])->firstWhere('id', $batch->id);
                $approvedTarget = collect($batch->trainingBudget->approved_snapshot['inputs']['batches'] ?? [])->firstWhere('id', $batch->id);
                if ($data['capacity'] < max($target['participants'] ?? 0, $approvedTarget['participants'] ?? 0)) {
                    throw ValidationException::withMessages(['capacity' => 'Kapasitas tidak boleh di bawah target peserta dalam RAB.']);
                }
            }
            if ($data['status'] === 'open' && ! app(TrainingPricing::class)->available($batch)) {
                throw ValidationException::withMessages(['status' => 'RAB harus disetujui sebelum pendaftaran dibuka.']);
            }
            if ($batch->applications()->exists() && (int) $data['training_program_id'] !== $batch->training_program_id) {
                throw ValidationException::withMessages(['training_program_id' => 'Program tidak dapat diganti setelah ada pendaftaran.']);
            }
            if ($data['capacity'] < $batch->activePaidEnrollments()->count()) {
                throw ValidationException::withMessages(['capacity' => 'Kapasitas tidak boleh lebih kecil dari jumlah peserta aktif lunas.']);
            }
            if (in_array($batch->operational_status, ['running', 'postponed', 'cancelled', 'completed'], true) && $data['status'] === 'open') {
                throw ValidationException::withMessages(['status' => 'Status operasional batch ini tidak mengizinkan pembukaan pendaftaran.']);
            }
            if ($data['status'] === 'completed' && $batch->status !== 'completed') {
                throw ValidationException::withMessages(['status' => 'Penyelesaian batch akan tersedia bersama modul ujian dan evaluasi.']);
            }
            $from = Carbon::parse($data['start_date'])->startOfDay();
            $until = empty($data['end_date']) ? null : Carbon::parse($data['end_date'])->endOfDay();
            if ($batch->sessions()->whereNot('status', 'cancelled')->where(function ($q) use ($from, $until): void {
                $q->where('starts_at', '<', $from);
                if ($until) {
                    $q->orWhere('ends_at', '>', $until);
                }
            })->exists()) {
                throw ValidationException::withMessages(['start_date' => 'Periode baru tidak mencakup seluruh sesi aktif. Sesuaikan jadwal terlebih dahulu.']);
            }
            $before = $batch->only(array_keys($data));
            $batch->update($data);
            $operations = app(BatchOperations::class);
            $operations->log($batch, $request->user(), 'batch.updated', 'Informasi master batch diperbarui.', $before, $batch->only(array_keys($data)));
            $operations->invalidateReady($batch, $request->user());
        });

        return to_route('admin.batches.index')
            ->with('success', 'Batch pelatihan berhasil diperbarui.');
    }

    public function destroy(TrainingBatch $batch): RedirectResponse
    {
        if ($batch->training_budget_id) {
            throw ValidationException::withMessages(['batch' => 'Batch tercatat dalam RAB. Pertahankan data dan riwayatnya.']);
        }
        $batch->loadCount(['applications', 'enrollments']);

        if ($batch->applications_count > 0 || $batch->enrollments_count > 0 || $batch->assignments()->exists() || $batch->sessions()->exists() || $batch->activityLogs()->exists()) {
            throw ValidationException::withMessages([
                'batch' => 'Batch memiliki pendaftaran, peserta, atau riwayat operasional. Tutup batch untuk menjaga riwayat data.',
            ]);
        }

        $batch->delete();

        return to_route('admin.batches.index')
            ->with('success', 'Batch pelatihan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?TrainingBatch $batch = null): array
    {
        $validated = $request->validate([
            'training_program_id' => ['required', 'exists:training_programs,id'],
            'code' => ['required', 'string', 'max:30', Rule::unique('training_batches', 'code')->ignore($batch)],
            'name' => ['required', 'string', 'max:255'],
            'registration_deadline' => ['nullable', 'date', 'before_or_equal:start_date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'status' => ['required', Rule::in(['draft', 'open', 'closed', 'completed'])],
        ]);

        $validated['code'] = Str::upper(trim($validated['code']));

        return $validated;
    }

    private function programs()
    {
        return TrainingProgram::query()
            ->orderBy('title')
            ->get(['id', 'code', 'title']);
    }
}
