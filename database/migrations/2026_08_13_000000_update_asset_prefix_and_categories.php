<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceAssetCodePrefix('AWA-', 'ATP-');
        $this->ensureDeviceSequenceExists();
    }

    public function down(): void
    {
        $this->replaceAssetCodePrefix('ATP-', 'AWA-');

        DB::table('asset_number_sequences')
            ->where('category_code', 'DEV')
            ->delete();
    }

    private function replaceAssetCodePrefix(string $from, string $to): void
    {
        DB::table('assets')
            ->where('asset_code', 'like', $from.'%')
            ->orderBy('id')
            ->get(['id', 'asset_code'])
            ->each(function (object $asset) use ($from, $to): void {
                $assetCode = (string) $asset->asset_code;

                DB::table('assets')
                    ->where('id', $asset->id)
                    ->update(['asset_code' => $to.substr($assetCode, strlen($from))]);
            });
    }

    private function ensureDeviceSequenceExists(): void
    {
        if (DB::table('asset_number_sequences')->where('category_code', 'DEV')->exists()) {
            return;
        }

        $highestNumber = DB::table('assets')
            ->where('category_code', 'DEV')
            ->pluck('asset_code')
            ->reduce(function (int $highest, string $assetCode): int {
                preg_match('/^(?:AWA|ATP)-DEV-(\d+)$/', strtoupper($assetCode), $matches);

                return max($highest, isset($matches[1]) ? (int) $matches[1] : 0);
            }, 0);

        DB::table('asset_number_sequences')->insert([
            'category_code' => 'DEV',
            'last_number' => $highestNumber,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
