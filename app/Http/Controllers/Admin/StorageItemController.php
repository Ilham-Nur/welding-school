<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorageItem;
use App\Support\NormalizesIndonesianNumbers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StorageItemController extends Controller
{
    use NormalizesIndonesianNumbers;

    public function index(Request $request): View
    {
        $items = StorageItem::query()->withSum('stocks', 'quantity')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request): void {
                $term = '%'.trim((string) $request->string('search')).'%';
                $query->where('code', 'like', $term)->orWhere('name', 'like', $term)->orWhere('category', 'like', $term);
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.storage.items.index', compact('items'));
    }

    public function show(StorageItem $storageItem): View
    {
        $storageItem->load(['stocks.location']);
        $movements = $storageItem->transactionLines()->with(['transaction.location', 'transaction.trainingBatch'])->latest()->paginate(20);

        return view('admin.storage.items.show', compact('storageItem', 'movements'));
    }

    public function create(): View
    {
        return view('admin.storage.items.form', ['storageItem' => new StorageItem]);
    }

    public function edit(StorageItem $storageItem): View
    {
        return view('admin.storage.items.form', compact('storageItem'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $data['updated_by'] = $request->user()->id;

        DB::transaction(function () use ($data): void {
            $storageItem = StorageItem::query()->create([
                ...$data,
                'code' => 'ATP-TMP-'.Str::ulid(),
            ]);

            $storageItem->update([
                'code' => StorageItem::internalCode((int) $storageItem->getKey()),
            ]);
        });

        return to_route('admin.storage-items.index')->with('success', 'Consumable berhasil ditambahkan. Stok awal dicatat melalui Penerimaan Barang.');
    }

    public function update(Request $request, StorageItem $storageItem): RedirectResponse
    {
        $data = $this->validated($request);
        $data['updated_by'] = $request->user()->id;
        $storageItem->update($data);

        return to_route('admin.storage-items.index')->with('success', 'Data consumable berhasil diperbarui.');
    }

    private function validated(Request $request): array
    {
        $request->merge(['minimum_stock' => $this->normalizeIndonesianNumber($request->input('minimum_stock'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:80'],
            'unit' => ['required', 'string', 'max:30'],
            'minimum_stock' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
