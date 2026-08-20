<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetExternalLoan;
use App\Models\Location;
use App\Models\StorageItem;
use App\Models\StorageTransactionLine;
use App\Services\Storage\StorageReportExcelExporter;
use App\Services\Storage\StorageReportPdfExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageReportController extends Controller
{
    public function index(Request $request): View
    {
        $movements = $this->filteredQuery($request)->paginate(30)->withQueryString();

        return view('admin.storage.reports.index', [
            'movements' => $movements,
            'locations' => Location::query()->where('is_storage', true)->orderBy('name')->get(),
            'items' => StorageItem::query()->orderBy('name')->get(),
            'overdueLoans' => AssetExternalLoan::query()->with(['items.asset', 'borrower'])->where('status', 'active')->whereNotNull('due_at')->where('due_at', '<', now())->orderBy('due_at')->get(),
        ]);
    }

    public function excel(Request $request, StorageReportExcelExporter $exporter): BinaryFileResponse
    {
        $path = $exporter->create($this->filteredQuery($request)->get(), $request->only(['from', 'to', 'location_id', 'storage_item_id']));

        return response()->download($path, 'laporan-storage-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store, max-age=0',
        ])->deleteFileAfterSend(true);
    }

    public function pdf(Request $request, StorageReportPdfExporter $exporter): BinaryFileResponse
    {
        $path = $exporter->create($this->filteredQuery($request)->get(), $request->only(['from', 'to', 'location_id', 'storage_item_id']));

        return response()->download($path, 'laporan-storage-'.now()->format('Ymd-His').'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
        ])->deleteFileAfterSend(true);
    }

    private function filteredQuery(Request $request): Builder
    {
        return StorageTransactionLine::query()->with(['item', 'transaction.location', 'transaction.trainingBatch'])
            ->whereHas('transaction', function ($query) use ($request): void {
                $query->when($request->filled('from'), fn ($query) => $query->whereDate('transaction_date', '>=', $request->date('from')))
                    ->when($request->filled('to'), fn ($query) => $query->whereDate('transaction_date', '<=', $request->date('to')))
                    ->when($request->filled('location_id'), fn ($query) => $query->where('location_id', $request->integer('location_id')));
            })
            ->when($request->filled('storage_item_id'), fn ($query) => $query->where('storage_item_id', $request->integer('storage_item_id')))
            ->latest();
    }
}
