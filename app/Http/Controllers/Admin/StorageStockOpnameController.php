<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\StorageItem;
use App\Models\StorageStock;
use App\Models\StorageStockOpname;
use App\Models\StorageTransaction;
use App\Support\NormalizesIndonesianNumbers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StorageStockOpnameController extends Controller
{
    use NormalizesIndonesianNumbers;

    public function index(): View
    {
        $opnames = StorageStockOpname::query()->with('location')->withCount('lines')->latest('counted_at')->paginate(20);

        return view('admin.storage.opnames.index', compact('opnames'));
    }

    public function create(): View
    {
        $locations = Location::query()->where('is_active', true)->where('is_storage', true)->orderBy('name')->get();

        return view('admin.storage.opnames.create', compact('locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_storage', true)->where('is_active', true)],
            'counted_at' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $opname = DB::transaction(function () use ($data, $request): StorageStockOpname {
            $opname = StorageStockOpname::query()->create($data + [
                'number' => $this->nextNumber(), 'status' => 'counting', 'created_by' => $request->user()->id,
            ]);
            $stocks = StorageStock::query()->where('location_id', $data['location_id'])->pluck('quantity', 'storage_item_id');
            StorageItem::query()->where('is_active', true)->orderBy('name')->each(function (StorageItem $item) use ($opname, $stocks): void {
                $opname->lines()->create(['storage_item_id' => $item->id, 'system_quantity' => $stocks[$item->id] ?? 0]);
            });

            return $opname;
        });

        return to_route('admin.storage.opnames.show', $opname)->with('success', 'Sesi stock opname dimulai. Masukkan hasil hitung fisik.');
    }

    public function show(StorageStockOpname $opname): View
    {
        $opname->load(['location', 'lines.item']);

        return view('admin.storage.opnames.show', compact('opname'));
    }

    public function complete(Request $request, StorageStockOpname $opname): RedirectResponse
    {
        if ($opname->status !== 'counting') {
            throw ValidationException::withMessages(['opname' => 'Stock opname ini sudah diselesaikan.']);
        }
        $request->merge([
            'counts' => collect($request->input('counts', []))
                ->map(fn ($value) => $this->normalizeIndonesianNumber($value))->all(),
        ]);
        $data = $request->validate([
            'counts' => ['required', 'array'], 'counts.*' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'line_notes' => ['nullable', 'array'], 'line_notes.*' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $request, $opname): void {
            $adjustment = StorageTransaction::query()->create([
                'number' => 'ADJ-'.$opname->number, 'type' => 'adjustment', 'transaction_date' => $opname->counted_at,
                'location_id' => $opname->location_id, 'reference' => $opname->number, 'purpose' => 'Penyesuaian hasil stock opname',
                'status' => 'posted', 'handled_by' => $request->user()->id, 'created_by' => $request->user()->id, 'posted_at' => now(),
            ]);
            $opname->lines()->with('item')->get()->each(function ($line) use ($data, $opname, $adjustment): void {
                $counted = (float) ($data['counts'][$line->id] ?? $line->system_quantity);
                $difference = $counted - (float) $line->system_quantity;
                $line->update(['counted_quantity' => $counted, 'difference' => $difference, 'notes' => $data['line_notes'][$line->id] ?? null]);
                StorageStock::query()->updateOrCreate(
                    ['storage_item_id' => $line->storage_item_id, 'location_id' => $opname->location_id], ['quantity' => $counted],
                );
                if ($difference != 0.0) {
                    $adjustment->lines()->create(['storage_item_id' => $line->storage_item_id, 'quantity' => $difference, 'notes' => $line->notes]);
                }
            });
            $opname->update(['status' => 'completed', 'completed_by' => $request->user()->id, 'completed_at' => now()]);
        });

        return to_route('admin.storage.opnames.show', $opname)->with('success', 'Stock opname selesai. Selisih telah masuk ke kartu stok sebagai penyesuaian.');
    }

    private function nextNumber(): string
    {
        $base = 'SO-'.now()->format('Ym').'-';
        $sequence = StorageStockOpname::query()->where('number', 'like', $base.'%')->count() + 1;

        return $base.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
