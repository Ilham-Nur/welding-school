<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetInspectionController extends Controller
{
    public function create(Asset $asset): View
    {
        $asset->load([
            'checklistItems',
            'inspections' => fn ($query) => $query
                ->with(['inspector', 'results'])
                ->limit(5),
        ]);

        return view('assets.inspect', compact('asset'));
    }

    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $items = $asset->checklistItems()->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'checklist' => 'Aset ini belum memiliki daftar pemeriksaan. Hubungi pengelola aset.',
            ]);
        }

        $rules = [
            'answers' => ['required', 'array', 'size:'.$items->count()],
            'condition' => ['required', Rule::in(array_keys(Asset::CONDITIONS))],
            'status' => ['required', Rule::in(array_keys(Asset::STATUSES))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        foreach ($items as $item) {
            $rules["answers.{$item->id}"] = ['required', Rule::in(['0', '1', 0, 1])];
        }

        $data = $request->validate($rules, [
            'answers.required' => 'Semua item pemeriksaan wajib dijawab.',
            'answers.size' => 'Semua item pemeriksaan wajib dijawab.',
            'answers.*.required' => 'Pilih Ya atau Tidak untuk setiap item pemeriksaan.',
        ]);

        if ($data['status'] === 'under_calibration' && ! $asset->requires_calibration) {
            throw ValidationException::withMessages([
                'status' => 'Status Dalam kalibrasi hanya dapat digunakan untuk aset yang wajib kalibrasi.',
            ]);
        }

        $nextInspection = today()->addMonthsNoOverflow((int) $asset->inspection_interval_months);

        DB::transaction(function () use ($asset, $data, $items, $nextInspection, $request): void {
            $inspection = $asset->inspections()->create([
                'inspector_id' => $request->user()->id,
                'inspected_at' => now(),
                'condition' => $data['condition'],
                'status' => $data['status'],
                'next_inspection_at' => $nextInspection,
                'notes' => $this->nullableTrim($data['notes'] ?? null),
            ]);

            $inspection->results()->createMany($items->map(fn ($item): array => [
                'checklist_item_id' => $item->id,
                'item_label' => $item->label,
                'is_ok' => (bool) ((int) $data['answers'][$item->id]),
            ])->all());

            $asset->update([
                'condition' => $data['condition'],
                'status' => $data['status'],
                'last_inspected_at' => today(),
                'next_inspection_at' => $nextInspection,
                'updated_by' => $request->user()->id,
            ]);
        });

        return to_route('assets.inspections.create', ['asset' => $asset->public_id])
            ->with('success', "Inspeksi {$asset->asset_code} berhasil disimpan. Kondisi dan status alat sudah diperbarui.");
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
