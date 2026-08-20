<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $locations = Location::query()->with(['parent', 'children'])
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request): void {
                $term = '%'.trim((string) $request->string('search')).'%';
                $query->where('name', 'like', $term)
                    ->orWhereHas('parent', fn ($parent) => $parent->where('name', 'like', $term));
            }))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.locations.index', compact('locations'));
    }

    public function create(): View
    {
        return view('admin.locations.form', ['location' => new Location]);
    }

    public function store(Request $request): RedirectResponse
    {
        Location::query()->create($this->validated($request));

        return to_route('admin.locations.index')->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function edit(Location $location): View
    {
        $location->load('parent');

        return view('admin.locations.form', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $location->update($this->validated($request, $location));

        return to_route('admin.locations.index')->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function storeChildren(Request $request, Location $location): RedirectResponse
    {
        $data = $request->validate([
            'children' => ['required', 'array', 'min:1', 'max:30'],
            'children.*' => ['required', 'string', 'max:120', 'distinct:ignore_case'],
        ], [
            'children.required' => 'Tambahkan minimal satu nama bagian.',
            'children.*.distinct' => 'Nama bagian tidak boleh sama dalam daftar.',
        ]);

        $names = collect($data['children'])
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->values();

        $existingNames = $location->children()
            ->whereIn('name', $names)
            ->pluck('name');

        if ($existingNames->isNotEmpty()) {
            throw ValidationException::withMessages([
                'children' => 'Bagian berikut sudah ada: '.$existingNames->join(', ').'.',
            ]);
        }

        DB::transaction(function () use ($location, $names): void {
            $location->children()->createMany($names->map(fn (string $name): array => [
                'name' => $name,
                'is_storage' => false,
                'is_active' => true,
            ])->all());
        });

        return to_route('admin.locations.index')
            ->with('success', $names->count().' bagian berhasil ditambahkan ke '.$location->name.'.');
    }

    private function validated(Request $request, ?Location $location = null): array
    {
        $parentId = $request->filled('parent_id') ? $request->integer('parent_id') : $location?->parent_id;
        $uniqueName = Rule::unique('locations', 'name')
            ->where(fn ($query) => $parentId ? $query->where('parent_id', $parentId) : $query->whereNull('parent_id'))
            ->ignore($location);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', $uniqueName],
            'parent_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where(fn ($query) => $location ? $query->where('id', '!=', $location->id) : $query)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_storage' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_storage'] = $request->boolean('is_storage');
        $data['is_active'] = $request->boolean('is_active');

        if ($location && ! empty($data['parent_id'])) {
            $parent = Location::query()->find($data['parent_id']);
            $guard = 0;
            while ($parent && $guard++ < 20) {
                if ($parent->id === $location->id) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Lokasi induk tidak boleh menghasilkan hubungan lokasi yang berputar.',
                    ]);
                }
                $parent = $parent->parent()->first();
            }
        }

        return $data;
    }
}
