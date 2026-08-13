<?php

namespace App\Services\Assets;

use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AssetCodeGenerator
{
    public function generate(string $categoryCode): string
    {
        $categoryCode = strtoupper(trim($categoryCode));

        if (! array_key_exists($categoryCode, Asset::CATEGORIES)) {
            throw new InvalidArgumentException('Kategori aset tidak valid.');
        }

        return DB::transaction(function () use ($categoryCode): string {
            $sequence = DB::table('asset_number_sequences')
                ->where('category_code', $categoryCode)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException("Pencatat nomor kategori {$categoryCode} tidak tersedia.");
            }

            $nextNumber = (int) $sequence->last_number + 1;

            DB::table('asset_number_sequences')
                ->where('category_code', $categoryCode)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return sprintf('ATP-%s-%03d', $categoryCode, $nextNumber);
        }, 5);
    }
}
