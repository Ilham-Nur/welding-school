<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->withCount(['applications', 'enrollments'])
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

    public function create(): View
    {
        return view('admin.batches.form', [
            'batch' => new TrainingBatch,
            'programs' => $this->programs(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TrainingBatch::query()->create($this->validated($request));

        return to_route('admin.batches.index')
            ->with('success', 'Batch pelatihan berhasil ditambahkan.');
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
        $batch->update($this->validated($request, $batch));

        return to_route('admin.batches.index')
            ->with('success', 'Batch pelatihan berhasil diperbarui.');
    }

    public function destroy(TrainingBatch $batch): RedirectResponse
    {
        $batch->loadCount(['applications', 'enrollments']);

        if ($batch->applications_count > 0 || $batch->enrollments_count > 0) {
            throw ValidationException::withMessages([
                'batch' => 'Batch memiliki pendaftaran atau peserta. Tutup batch untuk menjaga riwayat data.',
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
