<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingProgramController extends Controller
{
    public function index(Request $request): View
    {
        $programs = TrainingProgram::query()
            ->withCount(['batches', 'applications', 'enrollments'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.trim((string) $request->string('search')).'%';
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('code', 'like', $search)
                        ->orWhere('title', 'like', $search)
                        ->orWhere('category', 'like', $search);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.programs.index', compact('programs'));
    }

    public function create(): View
    {
        return view('admin.programs.form', ['program' => new TrainingProgram]);
    }

    public function store(Request $request): RedirectResponse
    {
        TrainingProgram::query()->create($this->validated($request));

        return to_route('admin.programs.index')
            ->with('success', 'Program pelatihan berhasil ditambahkan.');
    }

    public function edit(TrainingProgram $program): View
    {
        return view('admin.programs.form', compact('program'));
    }

    public function update(Request $request, TrainingProgram $program): RedirectResponse
    {
        $program->update($this->validated($request, $program));

        return to_route('admin.programs.index')
            ->with('success', 'Program pelatihan berhasil diperbarui.');
    }

    public function destroy(TrainingProgram $program): RedirectResponse
    {
        $program->loadCount(['batches', 'applications', 'enrollments']);

        if (
            $program->batches_count > 0
            || $program->applications_count > 0
            || $program->enrollments_count > 0
        ) {
            throw ValidationException::withMessages([
                'program' => 'Program memiliki batch, pendaftaran, atau peserta. Tutup program untuk menjaga riwayat data.',
            ]);
        }

        $program->delete();

        return to_route('admin.programs.index')
            ->with('success', 'Program pelatihan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?TrainingProgram $program = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('training_programs', 'code')->ignore($program)],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:80'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:10000'],
            'price' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'active', 'closed'])],
            'start_date' => ['nullable', 'date'],
        ]);

        $validated['code'] = Str::upper(trim($validated['code']));

        return $validated;
    }
}
