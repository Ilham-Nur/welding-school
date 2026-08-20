<?php

namespace App\Services\Storage;

use App\Models\StorageStock;
use App\Models\StorageTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoragePostingService
{
    /**
     * @param  array<int, array{storage_item_id:int, quantity:float|int|string, notes?:string|null}>  $lines
     */
    public function post(StorageTransaction $transaction, array $lines): void
    {
        DB::transaction(function () use ($transaction, $lines): void {
            foreach ($lines as $line) {
                $stock = StorageStock::query()->firstOrCreate(
                    ['storage_item_id' => $line['storage_item_id'], 'location_id' => $transaction->location_id],
                    ['quantity' => 0],
                );
                $stock = StorageStock::query()->lockForUpdate()->findOrFail($stock->id);
                $quantity = (float) $line['quantity'];
                $change = $transaction->type === 'issue' ? -$quantity : $quantity;

                if ((float) $stock->quantity + $change < 0) {
                    throw ValidationException::withMessages([
                        'lines' => 'Stok salah satu barang tidak mencukupi untuk pengeluaran ini.',
                    ]);
                }

                $stock->update(['quantity' => (float) $stock->quantity + $change]);
                $transaction->lines()->create([
                    'storage_item_id' => $line['storage_item_id'],
                    'quantity' => $line['quantity'],
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            $transaction->update(['status' => 'posted', 'posted_at' => now()]);
        });
    }
}
