<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetExternalLoan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetExternalLoanController extends Controller
{
    public function index(Request $request): View
    {
        $loans = AssetExternalLoan::query()->with(['items.asset', 'borrower', 'creator'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.trim((string) $request->string('search')).'%';
                $query->where(fn ($query) => $query->where('number', 'like', $term)
                    ->orWhere('borrower_name', 'like', $term)
                    ->orWhereHas('items.asset', fn ($query) => $query->where('equipment_name', 'like', $term)));
            })->latest('loaned_at')->paginate(20)->withQueryString();

        return view('admin.storage.loans.index', compact('loans'));
    }

    public function create(Request $request): View
    {
        $assets = Asset::query()->where('status', 'active')
            ->whereDoesntHave('externalLoanItems.loan', fn ($query) => $query->where('status', 'active'))
            ->orderBy('equipment_name')->get();
        $employees = User::permission('admin.access')->where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('admin.storage.loans.form', [
            'assets' => $assets,
            'employees' => $employees,
            'selectedAssets' => array_filter([$request->integer('asset')]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_ids' => ['required', 'array', 'min:1', 'max:50'],
            'asset_ids.*' => ['required', 'integer', 'distinct', 'exists:assets,id'],
            'borrower_user_id' => ['required', 'integer', 'exists:users,id'],
            'purpose' => ['required', 'string', 'max:2000'],
            'loaned_at' => ['required', 'date'], 'due_at' => ['nullable', 'date', 'after:loaned_at'],
            'condition_out' => ['required', Rule::in(array_keys(Asset::CONDITIONS))], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $borrower = User::query()->findOrFail($data['borrower_user_id']);
        if ($borrower->status !== 'active' || ! $borrower->can('admin.access')) {
            throw ValidationException::withMessages(['borrower_user_id' => 'Peminjam harus merupakan karyawan internal yang aktif.']);
        }

        $loan = DB::transaction(function () use ($data, $request, $borrower): AssetExternalLoan {
            $assets = Asset::query()->whereKey($data['asset_ids'])->lockForUpdate()->get();
            if ($assets->count() !== count($data['asset_ids']) || $assets->contains(fn (Asset $asset) => $asset->status !== 'active'
                || $asset->externalLoanItems()->whereHas('loan', fn ($query) => $query->where('status', 'active'))->exists())) {
                throw ValidationException::withMessages(['asset_ids' => 'Salah satu aset tidak tersedia untuk dipinjam. Silakan perbarui pilihan.']);
            }
            $loan = AssetExternalLoan::query()->create([
                'asset_id' => $assets->first()->id,
                'borrower_user_id' => $borrower->id,
                'borrower_name' => $borrower->name,
                'borrower_contact' => $borrower->email,
                'organization' => config('branding.company'),
                'purpose' => $data['purpose'],
                'loaned_at' => $data['loaned_at'],
                'due_at' => $data['due_at'] ?? null,
                'condition_out' => $data['condition_out'],
                'notes' => $data['notes'] ?? null,
                'number' => $this->nextNumber(), 'status' => 'active', 'created_by' => $request->user()->id,
            ]);
            $loan->items()->createMany($assets->map(fn (Asset $asset) => ['asset_id' => $asset->id])->all());
            Asset::query()->whereKey($assets->modelKeys())->update(['status' => 'on_loan', 'updated_by' => $request->user()->id]);

            return $loan;
        });

        return to_route('admin.storage.loans.index')->with('success', "Pinjaman {$loan->number} berhasil dicatat untuk ".count($data['asset_ids']).' aset.');
    }

    public function returnLoan(Request $request, AssetExternalLoan $loan): RedirectResponse
    {
        if ($loan->status !== 'active') {
            throw ValidationException::withMessages(['loan' => 'Pinjaman ini sudah diselesaikan.']);
        }
        $data = $request->validate([
            'returned_at' => ['required', 'date', 'after_or_equal:'.$loan->loaned_at->format('Y-m-d H:i:s')],
            'condition_in' => ['required', Rule::in(array_keys(Asset::CONDITIONS))],
            'return_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($data, $request, $loan): void {
            $loan->update([
                'returned_at' => $data['returned_at'], 'condition_in' => $data['condition_in'], 'status' => 'returned',
                'returned_by' => $request->user()->id,
                'notes' => trim(collect([$loan->notes, $data['return_notes'] ?? null])->filter()->join("\nPengembalian: ")) ?: null,
            ]);
            $loan->loadMissing('items.asset');
            Asset::query()->whereKey($loan->items->pluck('asset_id'))->update([
                'condition' => $data['condition_in'],
                'status' => $data['condition_in'] === 'damaged' ? 'out_of_service' : 'active',
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Pengembalian aset berhasil dicatat dan status aset telah diperbarui.');
    }

    private function nextNumber(): string
    {
        $base = 'LOAN-'.now()->format('Ym').'-';
        $sequence = AssetExternalLoan::query()->where('number', 'like', $base.'%')->count() + 1;
        do {
            $number = $base.str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT);
        } while (AssetExternalLoan::query()->where('number', $number)->exists());

        return $number;
    }
}
