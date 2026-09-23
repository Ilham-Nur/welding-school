<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeePosition;
use App\Models\EmployeePositionRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeRequirementController extends Controller
{
    public function index(EmployeePosition $employeePosition): View
    {
        return view('admin.employees.positions.requirements', ['position' => $employeePosition->load('requirements')]);
    }

    public function store(Request $request, EmployeePosition $employeePosition): RedirectResponse
    {
        $employeePosition->requirements()->create($this->validated($request));

        return back()->with('success', 'Persyaratan jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, EmployeePosition $employeePosition, EmployeePositionRequirement $requirement): RedirectResponse
    {
        abort_unless($requirement->employee_position_id === $employeePosition->id, 404);
        $requirement->fill($this->validated($request));
        if ($requirement->isDirty()) {
            $requirement->version++;
            $requirement->save();
        }

        return back()->with('success', 'Persyaratan diperbarui. Dokumen yang memerlukan verifikasi perlu diperiksa kembali jika persyaratan berubah.');
    }

    public function destroy(EmployeePosition $employeePosition, EmployeePositionRequirement $requirement): RedirectResponse
    {
        abort_unless($requirement->employee_position_id === $employeePosition->id, 404);
        $requirement->delete();

        return back()->with('success', 'Persyaratan dihapus. Arsip dokumen karyawan tetap tersimpan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(EmployeePositionRequirement::CATEGORIES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_required' => ['required', 'boolean'],
            'has_expiry' => ['required', 'boolean'],
            'requires_verification' => ['required', 'boolean'],
        ]);
    }
}
