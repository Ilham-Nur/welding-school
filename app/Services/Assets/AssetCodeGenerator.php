<?php

namespace App\Services\Assets;

use App\Models\Asset;
use App\Models\AssetKind;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AssetCodeGenerator
{
    public function generate(string $categoryCode, ?AssetKind $assetKind = null): string
    {
        $categoryCode = strtoupper(trim($categoryCode));

        if (! array_key_exists($categoryCode, Asset::CATEGORIES)) {
            throw new InvalidArgumentException('Kategori aset tidak valid.');
        }

        if ($assetKind) {
            if ($assetKind->category_code !== $categoryCode) {
                throw new InvalidArgumentException('Jenis aset tidak sesuai dengan kategori yang dipilih.');
            }

            return $this->generateForKind($assetKind);
        }

        return $this->generateLegacyCode($categoryCode);
    }

    private function generateForKind(AssetKind $assetKind): string
    {
        return DB::transaction(function () use ($assetKind): string {
            $sequence = DB::table('asset_kind_number_sequences')
                ->where('asset_kind_id', $assetKind->id)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException("Pencatat nomor jenis {$assetKind->code} tidak tersedia.");
            }

            $nextNumber = (int) $sequence->last_number + 1;

            DB::table('asset_kind_number_sequences')
                ->where('asset_kind_id', $assetKind->id)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return $assetKind->codeFor($nextNumber);
        }, 5);
    }

    private function generateLegacyCode(string $categoryCode): string
    {
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
