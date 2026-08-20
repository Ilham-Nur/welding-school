<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeePositionController extends Controller
{
    public function index(Request $request): View
    {
        $positions = EmployeePosition::query()
            ->withCount('employees')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.trim((string) $request->string('search')).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $status = $request->string('status')->toString();
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.employees.positions.index', [
            'positions' => $positions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('employee_positions', 'name')],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('employee_positions', 'code')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        EmployeePosition::query()->create($validated);

        return redirect()->route('admin.employee-positions.index')
            ->with('success', "Master data jabatan '{$validated['name']}' berhasil ditambahkan.");
    }

    public function update(Request $request, EmployeePosition $employeePosition): RedirectResponse
    {
        $oldName = $employeePosition->name;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('employee_positions', 'name')->ignore($employeePosition->id)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('employee_positions', 'code')->ignore($employeePosition->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        $employeePosition->update($validated);

        // Keep existing employee records in sync if name was updated
        if ($oldName !== $validated['name']) {
            Employee::query()->where('position', $oldName)->update(['position' => $validated['name']]);
        }

        return redirect()->route('admin.employee-positions.index')
            ->with('success', "Data jabatan '{$employeePosition->name}' berhasil diperbarui.");
    }

    public function destroy(EmployeePosition $employeePosition): RedirectResponse
    {
        $employeeCount = $employeePosition->employees()->count();

        if ($employeeCount > 0) {
            // Unlink position from employees before deleting
            Employee::query()->where('position_id', $employeePosition->id)->update(['position_id' => null]);
        }

        $name = $employeePosition->name;
        $employeePosition->delete();

        return redirect()->route('admin.employee-positions.index')
            ->with('success', "Master data jabatan '{$name}' berhasil dihapus.");
    }
}
