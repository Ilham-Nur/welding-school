<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetExternalLoan;
use App\Models\StorageItem;
use App\Models\StorageTransaction;
use Illuminate\View\View;

class StorageDashboardController extends Controller
{
    public function __invoke(): View
    {
        $items = StorageItem::query()->withSum('stocks', 'quantity')->where('is_active', true)->get();

        return view('admin.storage.dashboard', [
            'itemCount' => $items->count(),
            'lowStockCount' => $items->filter(fn ($item) => (float) ($item->stocks_sum_quantity ?? 0) <= (float) $item->minimum_stock)->count(),
            'emptyStockCount' => $items->filter(fn ($item) => (float) ($item->stocks_sum_quantity ?? 0) <= 0)->count(),
            'activeLoanCount' => AssetExternalLoan::query()->where('status', 'active')->count(),
            'overdueLoanCount' => AssetExternalLoan::query()->where('status', 'active')->where('due_at', '<', now())->count(),
            'recentTransactions' => StorageTransaction::query()->with(['location', 'handler'])->withCount('lines')->latest('transaction_date')->latest()->limit(8)->get(),
            'lowStockItems' => $items->filter(fn ($item) => (float) ($item->stocks_sum_quantity ?? 0) <= (float) $item->minimum_stock)->sortBy('stocks_sum_quantity')->take(8),
        ]);
    }
}
