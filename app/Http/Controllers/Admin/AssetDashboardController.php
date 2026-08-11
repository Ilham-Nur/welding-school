<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetInspection;
use Illuminate\View\View;

class AssetDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.assets.dashboard', [
            'stats' => [
                'total' => Asset::query()->count(),
                'active' => Asset::query()->where('status', 'active')->count(),
                'inspectionDue' => Asset::query()
                    ->where(function ($query): void {
                        $query
                            ->whereNull('next_inspection_at')
                            ->orWhereDate('next_inspection_at', '<=', today());
                    })
                    ->count(),
                'calibrationAlerts' => Asset::query()
                    ->where('requires_calibration', true)
                    ->where(function ($query): void {
                        $query
                            ->whereNull('calibration_due_at')
                            ->orWhereDate('calibration_due_at', '<=', today()->addDays(30));
                    })
                    ->count(),
            ],
            'categoryCounts' => Asset::query()
                ->selectRaw('category_code, COUNT(*) as total')
                ->groupBy('category_code')
                ->pluck('total', 'category_code'),
            'dueAssets' => Asset::query()
                ->where(function ($query): void {
                    $query
                        ->whereNull('next_inspection_at')
                        ->orWhereDate('next_inspection_at', '<=', today()->addDays(7));
                })
                ->orderByRaw('next_inspection_at IS NULL DESC')
                ->orderBy('next_inspection_at')
                ->limit(8)
                ->get(),
            'recentInspections' => AssetInspection::query()
                ->with(['asset', 'inspector'])
                ->withCount([
                    'results as failed_items_count' => fn ($query) => $query->where('is_ok', false),
                ])
                ->latest('inspected_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
