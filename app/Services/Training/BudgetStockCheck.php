<?php

namespace App\Services\Training;

use App\Models\Location;
use App\Models\StorageItem;
use App\Models\StorageStock;
use Illuminate\Validation\ValidationException;

class BudgetStockCheck
{
    public function calculate(array $inputs): array
    {
        $requirements = [];
        $unmapped = [];
        foreach ($inputs['items'] as $index => $line) {
            if (($line['stock_kind'] ?? 'none') !== 'material') {
                continue;
            }
            $item = empty($line['storage_item_id']) ? null : StorageItem::find($line['storage_item_id']);
            $location = empty($line['storage_location_id']) ? null : Location::find($line['storage_location_id']);
            if (! $item?->is_active || ! $item->unit?->is_active || ! $location?->is_active || ! $location->is_storage) {
                $unmapped[] = $line['description'];

                continue;
            }
            $quantity = 0;
            $calculator = app(BudgetCalculator::class);
            if ($line['scope'] === 'shared') {
                $quantity = $calculator->units($line, []);
            } else {
                foreach ($inputs['batches'] as $batch) {
                    if (empty($line['batch_id']) || (int) $line['batch_id'] === (int) $batch['id']) {
                        $quantity += $calculator->units($line, $batch);
                    }
                }
            }
            // Convert the RAB quantity to the warehouse's unit; round up to avoid understating needs.
            $quantity = ceil($quantity * ($line['stock_unit_factor'] ?? 1) * 1000) / 1000;
            if (! is_finite($quantity) || $quantity > 1_000_000_000) {
                throw ValidationException::withMessages(["items.$index.stock_unit_factor" => 'Kebutuhan material terlalu besar. Periksa konversi satuan.']);
            }
            $key = $item->id.':'.$location->id;
            $requirements[$key] ??= ['item' => $item->name, 'location' => $location->name, 'unit' => $item->unit->symbol, 'required' => 0,
                'available' => (float) (StorageStock::where('storage_item_id', $item->id)->where('location_id', $location->id)->value('quantity') ?? 0)];
            $requirements[$key]['required'] += $quantity;
        }
        foreach ($requirements as &$row) {
            $row['required'] = round($row['required'], 3);
            $row['shortage'] = round(max(0, $row['required'] - $row['available']), 3);
        }
        unset($row);

        return ['rows' => array_values($requirements), 'unmapped' => $unmapped,
            'has_shortage' => collect($requirements)->contains(fn ($row) => $row['shortage'] > 0)];
    }

    public function validateForApproval(array $inputs): array
    {
        $result = $this->calculate($inputs);
        if ($result['unmapped']) {
            throw ValidationException::withMessages(['stock' => 'Lengkapi barang dan lokasi gudang untuk material: '.implode(', ', $result['unmapped'])]);
        }
        if ($result['has_shortage'] && blank($inputs['procurement_notes'] ?? null)) {
            throw ValidationException::withMessages(['procurement_notes' => 'Ada kekurangan stok. Tuliskan rencana pengadaan sebelum pengajuan atau persetujuan.']);
        }

        return $result;
    }
}
