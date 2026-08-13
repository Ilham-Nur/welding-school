<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetInspectionScanController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_code' => ['required', 'string', 'max:40', 'regex:/^ATP-[A-Z]{3}-\d{3,}$/i'],
        ], [
            'asset_code.regex' => 'Format Asset ID tidak sesuai. Contoh yang benar: ATP-WLD-001.',
        ]);

        $asset = Asset::query()
            ->where('asset_code', Str::upper(trim($data['asset_code'])))
            ->first();

        if (! $asset) {
            return response()->json([
                'message' => 'Asset ID tidak ditemukan. Periksa kembali nomor pada label aset.',
            ], 404);
        }

        return response()->json([
            'inspection_url' => route('assets.inspections.create', ['asset' => $asset->public_id]),
        ]);
    }
}
