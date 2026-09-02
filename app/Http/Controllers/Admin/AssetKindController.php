<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetKind;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetKindController extends Controller
{
    public function index(Request $request): View
    {
        $assetKinds = AssetKind::query()
            ->with('numberSequence')
            ->withCount('assets')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.trim((string) $request->string('search')).'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search);
                });
            })
            ->when($request->filled('category'), fn ($query) => $query
                ->where('category_code', $request->string('category')->toString()))
            ->when($request->string('status')->toString() === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->string('status')->toString() === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('category_code')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.assets.kinds.index', [
            'assetKinds' => $assetKinds,
            'categories' => Asset::CATEGORIES,
        ]);
    }

    public function create(Request $request): View
    {
        $requestedCategory = strtoupper(trim((string) $request->input('category', 'WLD')));
        $selectedCategory = array_key_exists($requestedCategory, Asset::CATEGORIES) ? $requestedCategory : 'WLD';

        return view('admin.assets.kinds.form', [
            'assetKind' => new AssetKind,
            'categories' => Asset::CATEGORIES,
            'selectedCategory' => $selectedCategory,
            'identityLocked' => false,
        ]);
    }

    public function edit(AssetKind $assetKind): View
    {
        $assetKind->load('numberSequence')->loadCount('assets');

        return view('admin.assets.kinds.form', [
            'assetKind' => $assetKind,
            'categories' => Asset::CATEGORIES,
            'selectedCategory' => $assetKind->category_code,
            'identityLocked' => $assetKind->assets_count > 0
                || (int) ($assetKind->numberSequence?->last_number ?? 0) > 0,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->normalizeInput($request);

        $data = $request->validate([
            'category_code' => ['required', Rule::in(array_keys(Asset::CATEGORIES))],
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                Rule::unique('asset_kinds', 'code')->where(
                    fn ($query) => $query->where('category_code', $request->string('category_code')->toString()),
                ),
            ],
        ], [
            'code.size' => 'Kode jenis harus terdiri dari tepat 3 huruf.',
            'code.regex' => 'Kode jenis hanya boleh menggunakan huruf A–Z.',
            'code.unique' => 'Kode jenis tersebut sudah digunakan pada kategori ini.',
        ]);

        $assetKind = DB::transaction(function () use ($data, $request): AssetKind {
            $assetKind = AssetKind::query()->create([
                ...$data,
                'is_active' => true,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $assetKind->numberSequence()->create(['last_number' => 0]);

            return $assetKind;
        });

        $payload = [
            'message' => "Jenis aset {$assetKind->name} berhasil ditambahkan.",
            'kind' => [
                'id' => $assetKind->id,
                'categoryCode' => $assetKind->category_code,
                'code' => $assetKind->code,
                'name' => $assetKind->name,
                'lastNumber' => 0,
                'lastCode' => null,
                'nextCode' => $assetKind->codeFor(1),
                'assetCount' => 0,
            ],
        ];

        if ($request->expectsJson()) {
            return response()->json($payload, 201);
        }

        if ($request->input('return_to') === 'asset-create') {
            return redirect()->route('admin.assets.create', [
                'category_code' => $assetKind->category_code,
                'asset_kind_id' => $assetKind->id,
            ])->with('success', $payload['message'].' Jenis tersebut sudah dipilih pada form aset.');
        }

        return $this->redirectToIndex(
            $request,
            $payload['message'],
            fallbackCategory: $assetKind->category_code,
        );
    }

    public function update(Request $request, AssetKind $assetKind): RedirectResponse
    {
        $this->normalizeInput($request);
        $hasNumberHistory = (int) $assetKind->numberSequence()->value('last_number') > 0;
        $hasAssets = $assetKind->assets()->exists();
        $identityLocked = $hasNumberHistory || $hasAssets;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                Rule::unique('asset_kinds', 'code')
                    ->where(fn ($query) => $query->where('category_code', $assetKind->category_code))
                    ->ignore($assetKind->id),
            ],
        ], $this->validationMessages());

        if ($identityLocked && $data['code'] !== $assetKind->code) {
            throw ValidationException::withMessages([
                'code' => 'Kode jenis tidak dapat diubah karena sudah pernah digunakan untuk penomoran aset.',
            ]);
        }

        $assetKind->update([
            'name' => $data['name'],
            'code' => $identityLocked ? $assetKind->code : $data['code'],
            'updated_by' => $request->user()->id,
        ]);

        return $this->redirectToIndex($request, "Jenis aset {$assetKind->name} berhasil diperbarui.");
    }

    public function toggle(Request $request, AssetKind $assetKind): RedirectResponse
    {
        $assetKind->update([
            'is_active' => ! $assetKind->is_active,
            'updated_by' => $request->user()->id,
        ]);

        $status = $assetKind->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return $this->redirectToIndex($request, "Jenis aset {$assetKind->name} berhasil {$status}.");
    }

    public function destroy(Request $request, AssetKind $assetKind): RedirectResponse
    {
        $hasNumberHistory = (int) $assetKind->numberSequence()->value('last_number') > 0;

        if ($hasNumberHistory || $assetKind->assets()->exists()) {
            return $this->redirectToIndex(
                $request,
                'Jenis aset yang sudah pernah digunakan tidak dapat dihapus. Nonaktifkan jenis tersebut agar data dan riwayat nomor tetap aman.',
                'error',
            );
        }

        $name = $assetKind->name;
        $assetKind->delete();

        return $this->redirectToIndex($request, "Jenis aset {$name} berhasil dihapus permanen.");
    }

    private function normalizeInput(Request $request): void
    {
        $request->merge([
            'category_code' => strtoupper(trim((string) $request->input('category_code'))),
            'code' => strtoupper(trim((string) $request->input('code'))),
            'name' => trim((string) $request->input('name')),
        ]);
    }

    /** @return array<string, string> */
    private function validationMessages(): array
    {
        return [
            'code.size' => 'Kode jenis harus terdiri dari tepat 3 huruf.',
            'code.regex' => 'Kode jenis hanya boleh menggunakan huruf A–Z.',
            'code.unique' => 'Kode jenis tersebut sudah digunakan pada kategori ini.',
        ];
    }

    private function redirectToIndex(
        Request $request,
        string $message,
        string $flashKey = 'success',
        ?string $fallbackCategory = null,
    ): RedirectResponse {
        $parameters = [];

        if ($request->boolean('from_list')) {
            $search = trim((string) $request->input('redirect_search'));
            $category = strtoupper(trim((string) $request->input('redirect_category')));
            $status = trim((string) $request->input('redirect_status'));
            $page = max(1, (int) $request->input('redirect_page', 1));

            if ($search !== '') {
                $parameters['search'] = $search;
            }
            if (array_key_exists($category, Asset::CATEGORIES)) {
                $parameters['category'] = $category;
            }
            if (in_array($status, ['active', 'inactive'], true)) {
                $parameters['status'] = $status;
            }
            if ($page > 1) {
                $parameters['page'] = $page;
            }
        } else {
            $category = strtoupper(trim((string) $request->input('redirect_category', $fallbackCategory)));
            $parameters = array_key_exists($category, Asset::CATEGORIES) ? ['category' => $category] : [];
        }

        if ($request->input('return_to') === 'asset-create') {
            $parameters['return_to'] = 'asset-create';
        }

        return redirect()->route('admin.asset-kinds.index', $parameters)->with($flashKey, $message);
    }
}
