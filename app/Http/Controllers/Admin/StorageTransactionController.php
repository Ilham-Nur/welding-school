<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\StorageItem;
use App\Models\StorageTransaction;
use App\Models\TrainingBatch;
use App\Services\Storage\StoragePostingService;
use App\Support\NormalizesIndonesianNumbers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StorageTransactionController extends Controller
{
    use NormalizesIndonesianNumbers;

    public function __construct(private readonly StoragePostingService $posting) {}

    public function receipts(Request $request): View
    {
        return $this->index($request, 'receipt');
    }

    public function issues(Request $request): View
    {
        return $this->index($request, 'issue');
    }

    public function createReceipt(): View
    {
        return $this->form('receipt');
    }

    public function createIssue(): View
    {
        return $this->form('issue');
    }

    public function storeReceipt(Request $request): RedirectResponse
    {
        return $this->store($request, 'receipt');
    }

    public function storeIssue(Request $request): RedirectResponse
    {
        return $this->store($request, 'issue');
    }

    private function index(Request $request, string $type): View
    {
        $transactions = StorageTransaction::query()->where('type', $type)->with(['location', 'trainingBatch', 'handler'])->withCount('lines')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.trim((string) $request->string('search')).'%';
                $query->where(fn ($query) => $query->where('number', 'like', $term)->orWhere('reference', 'like', $term)->orWhere('supplier', 'like', $term)->orWhere('purpose', 'like', $term));
            })
            ->latest('transaction_date')->latest()->paginate(20)->withQueryString();

        return view('admin.storage.transactions.index', compact('transactions', 'type'));
    }

    private function form(string $type): View
    {
        return view('admin.storage.transactions.form', [
            'type' => $type,
            'locations' => Location::query()->where('is_active', true)->where('is_storage', true)->orderBy('name')->get(),
            'items' => StorageItem::query()->where('is_active', true)->orderBy('name')->get(),
            'batches' => TrainingBatch::query()->with('trainingProgram')->latest('start_date')->limit(100)->get(),
        ]);
    }

    private function store(Request $request, string $type): RedirectResponse
    {
        $request->merge([
            'lines' => collect($request->input('lines', []))->map(function (array $line): array {
                $line['quantity'] = $this->normalizeIndonesianNumber($line['quantity'] ?? null);

                return $line;
            })->all(),
        ]);

        $rules = [
            'transaction_date' => ['required', 'date'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_storage', true)->where('is_active', true)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'handled_by' => ['nullable', 'exists:users,id'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.storage_item_id' => ['required', 'integer', 'distinct', Rule::exists('storage_items', 'id')->where('is_active', true)],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ];
        if ($type === 'receipt') {
            $rules['supplier'] = ['nullable', 'string', 'max:255'];
        } else {
            $rules['purpose'] = ['required', 'string', 'max:255'];
            $rules['training_batch_id'] = ['nullable', 'exists:training_batches,id'];
        }
        $data = $request->validate($rules);

        $transaction = DB::transaction(function () use ($data, $request, $type): StorageTransaction {
            $transaction = StorageTransaction::query()->create([
                'number' => $this->nextNumber($type), 'type' => $type,
                'transaction_date' => $data['transaction_date'], 'location_id' => $data['location_id'],
                'training_batch_id' => $data['training_batch_id'] ?? null, 'supplier' => $data['supplier'] ?? null,
                'reference' => $data['reference'] ?? null, 'purpose' => $data['purpose'] ?? null,
                'status' => 'draft', 'handled_by' => $data['handled_by'] ?? $request->user()->id,
                'created_by' => $request->user()->id, 'notes' => $data['notes'] ?? null,
            ]);
            $this->posting->post($transaction, $data['lines']);

            return $transaction;
        });

        $route = $type === 'receipt' ? 'admin.storage.receipts.index' : 'admin.storage.issues.index';

        return to_route($route)->with('success', "Transaksi {$transaction->number} berhasil dicatat dan stok telah diperbarui.");
    }

    private function nextNumber(string $type): string
    {
        $prefix = $type === 'receipt' ? 'STG-IN' : 'STG-OUT';
        $base = $prefix.'-'.now()->format('Ym').'-';
        $sequence = StorageTransaction::query()->where('number', 'like', $base.'%')->count() + 1;
        do {
            $number = $base.str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT);
        } while (StorageTransaction::query()->where('number', $number)->exists());

        return $number;
    }
}
