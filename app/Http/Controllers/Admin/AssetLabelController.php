<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetLabelController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'assets' => ['required', 'array', 'min:1', 'max:24'],
            'assets.*' => ['required', 'integer', 'distinct', 'exists:assets,id'],
        ], [
            'assets.required' => 'Pilih minimal satu aset untuk dicetak.',
            'assets.max' => 'Maksimal 24 label dapat dicetak sekaligus.',
        ]);

        $assets = Asset::query()
            ->whereIn('id', $validated['assets'])
            ->orderBy('asset_code')
            ->get();

        return view('admin.assets.labels', compact('assets'));
    }
}
