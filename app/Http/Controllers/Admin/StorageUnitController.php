<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorageUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StorageUnitController extends Controller
{
    public function index(Request $request): View
    {
        $units = StorageUnit::query()
            ->withCount('items')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.trim((string) $request->string('search')).'%';
                $query->where(fn ($query) => $query->where('symbol', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->orderBy('symbol')
            ->paginate(20)
            ->withQueryString();

        return view('admin.storage.units.index', compact('units'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = true;
        $unit = StorageUnit::query()->create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Satuan berhasil ditambahkan.',
                'unit' => [
                    'id' => $unit->id,
                    'symbol' => $unit->symbol,
                    'name' => $unit->name,
                    'label' => $unit->label(),
                ],
            ], 201);
        }

        return to_route('admin.storage.units.index')->with('success', "Satuan {$unit->symbol} berhasil ditambahkan.");
    }

    public function update(Request $request, StorageUnit $storageUnit): RedirectResponse
    {
        $data = $this->validated($request, $storageUnit);

        if ($storageUnit->items()->exists() && $data['symbol'] !== $storageUnit->symbol) {
            throw ValidationException::withMessages([
                'symbol' => 'Simbol satuan tidak dapat diubah karena sudah digunakan oleh consumable.',
            ]);
        }

        $storageUnit->update($data);

        return to_route('admin.storage.units.index')->with('success', "Satuan {$storageUnit->symbol} berhasil diperbarui.");
    }

    public function toggle(StorageUnit $storageUnit): RedirectResponse
    {
        $storageUnit->update(['is_active' => ! $storageUnit->is_active]);
        $status = $storageUnit->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Satuan {$storageUnit->symbol} berhasil {$status}.");
    }

    private function validated(Request $request, ?StorageUnit $storageUnit = null): array
    {
        $request->merge([
            'symbol' => trim((string) $request->input($request->has('unit_symbol') ? 'unit_symbol' : 'symbol')),
            'name' => filled($request->input($request->has('unit_name') ? 'unit_name' : 'name'))
                ? trim((string) $request->input($request->has('unit_name') ? 'unit_name' : 'name'))
                : null,
        ]);

        $data = $request->validate([
            'symbol' => ['required', 'string', 'max:30', Rule::unique('storage_units', 'symbol')->ignore($storageUnit?->id)],
            'name' => ['nullable', 'string', 'max:80'],
        ]);

        $duplicate = StorageUnit::query()
            ->whereRaw('LOWER(symbol) = ?', [mb_strtolower($data['symbol'])])
            ->when($storageUnit, fn ($query) => $query->whereKeyNot($storageUnit->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['symbol' => 'Simbol satuan sudah terdaftar.']);
        }

        return $data;
    }
}
