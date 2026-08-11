<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\Assets\AssetCodeGenerator;
use App\Services\Assets\AssetExcelExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AssetController extends Controller
{
    public function __construct(private readonly AssetCodeGenerator $codeGenerator) {}

    public function index(Request $request): View
    {
        $assets = $this->filteredQuery($request)
            ->orderBy('asset_code')
            ->paginate(15)
            ->withQueryString();

        return view('admin.assets.index', [
            'assets' => $assets,
        ]);
    }

    public function export(Request $request, AssetExcelExporter $exporter): BinaryFileResponse
    {
        $assets = $this->filteredQuery($request)
            ->orderBy('asset_code')
            ->get();
        $path = $exporter->create($assets, $request->only(['search', 'category', 'status']));
        $filename = 'daftar-aset-'.now()->format('Ymd-His').'.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store, max-age=0',
        ])->deleteFileAfterSend(true);
    }

    public function create(): View
    {
        return view('admin.assets.form', ['asset' => new Asset]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $checklistItems = $data['checklist_items'];
        unset($data['checklist_items']);
        $photoPath = null;
        $certificatePath = null;

        if ($request->hasFile('photo')) {
            $photoPath = 'storage/'.$request->file('photo')->store('assets', 'public');
            $data['photo_path'] = $photoPath;
        }

        if ($request->hasFile('calibration_certificate')) {
            $certificate = $this->storeCalibrationCertificate($request->file('calibration_certificate'));
            $certificatePath = $certificate['calibration_certificate_path'];
            $data = array_merge($data, $certificate);
        }

        try {
            $asset = DB::transaction(function () use ($data, $checklistItems, $request): Asset {
                $data['asset_code'] = $this->codeGenerator->generate($data['category_code']);
                $data['last_inspected_at'] = null;
                $data['next_inspection_at'] = today()->addMonthsNoOverflow((int) $data['inspection_interval_months']);
                $data['created_by'] = $request->user()->id;
                $data['updated_by'] = $request->user()->id;

                $asset = Asset::query()->create($data);
                $this->replaceChecklist($asset, $checklistItems);

                return $asset;
            });
        } catch (Throwable $exception) {
            if ($photoPath) {
                $this->deleteUploadedPhoto($photoPath);
            }

            if ($certificatePath) {
                $this->deleteUploadedCalibrationCertificate($certificatePath);
            }

            throw $exception;
        }

        return to_route('admin.assets.index')
            ->with('success', "Aset {$asset->asset_code} berhasil dibuat otomatis dan siap diberi label.");
    }

    public function edit(Asset $asset): View
    {
        $asset->load('checklistItems');

        return view('admin.assets.form', compact('asset'));
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $data = $this->validated($request, $asset);
        $checklistItems = $data['checklist_items'];
        unset($data['checklist_items']);
        $oldPhotoPath = $asset->photo_path;
        $oldCertificatePath = $asset->calibration_certificate_path;
        $newPhotoPath = null;
        $newCertificatePath = null;
        $removeCertificate = $request->boolean('remove_calibration_certificate') || ! $data['requires_calibration'];

        if ($request->hasFile('photo')) {
            $newPhotoPath = 'storage/'.$request->file('photo')->store('assets', 'public');
            $data['photo_path'] = $newPhotoPath;
        }

        if ($request->hasFile('calibration_certificate')) {
            $certificate = $this->storeCalibrationCertificate($request->file('calibration_certificate'));
            $newCertificatePath = $certificate['calibration_certificate_path'];
            $data = array_merge($data, $certificate);
        } elseif ($removeCertificate) {
            $data = array_merge($data, [
                'calibration_certificate_path' => null,
                'calibration_certificate_name' => null,
                'calibration_certificate_mime' => null,
                'calibration_certificate_size' => null,
            ]);
        }

        try {
            DB::transaction(function () use ($data, $checklistItems, $request, $asset): void {
                $anchor = $asset->last_inspected_at ?? $asset->created_at ?? today();
                $data['next_inspection_at'] = $anchor->copy()
                    ->startOfDay()
                    ->addMonthsNoOverflow((int) $data['inspection_interval_months']);
                $data['updated_by'] = $request->user()->id;

                $asset->update($data);
                $this->replaceChecklist($asset, $checklistItems);
            });
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                $this->deleteUploadedPhoto($newPhotoPath);
            }

            if ($newCertificatePath) {
                $this->deleteUploadedCalibrationCertificate($newCertificatePath);
            }

            throw $exception;
        }

        if ($newPhotoPath && $oldPhotoPath) {
            $this->deleteUploadedPhoto($oldPhotoPath);
        }

        if (($newCertificatePath || $removeCertificate) && $oldCertificatePath) {
            $this->deleteUploadedCalibrationCertificate($oldCertificatePath);
        }

        return to_route('admin.assets.index')
            ->with('success', "Aset {$asset->asset_code} berhasil diperbarui. Asset ID tetap tidak berubah.");
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $code = $asset->asset_code;
        $photoPath = $asset->photo_path;
        $certificatePath = $asset->calibration_certificate_path;
        $asset->delete();
        if ($photoPath) {
            $this->deleteUploadedPhoto($photoPath);
        }
        if ($certificatePath) {
            $this->deleteUploadedCalibrationCertificate($certificatePath);
        }

        return to_route('admin.assets.index')
            ->with('success', "Aset {$code} berhasil dihapus. Nomor tersebut tidak akan digunakan ulang.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Asset $asset = null): array
    {
        $calibrationDetailsRequired = fn (): bool => $request->boolean('requires_calibration')
            && $request->string('status')->toString() === 'active';

        $rules = [
            'equipment_name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(fn (): bool => $request->boolean('requires_calibration')),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'purchase_year' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'location' => ['required', 'string', 'max:255'],
            'condition' => ['required', Rule::in(array_keys(Asset::CONDITIONS))],
            'inspection_interval_months' => ['required', 'integer', Rule::in(array_keys(Asset::INSPECTION_INTERVALS))],
            'checklist_items' => ['required', 'array', 'min:1', 'max:30'],
            'checklist_items.*' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'status' => ['required', Rule::in(array_keys(Asset::STATUSES))],
            'requires_calibration' => ['required', 'boolean'],
            'calibrated_at' => ['nullable', 'date', Rule::requiredIf($calibrationDetailsRequired)],
            'calibration_due_at' => [
                'nullable',
                'date',
                Rule::requiredIf($calibrationDetailsRequired),
                'after_or_equal:calibrated_at',
            ],
            'certificate_number' => ['nullable', 'string', 'max:100', Rule::requiredIf($calibrationDetailsRequired)],
            'calibration_certificate' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
                Rule::prohibitedIf(fn (): bool => ! $request->boolean('requires_calibration')),
            ],
            'remove_calibration_certificate' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if (! $asset) {
            $rules['category_code'] = ['required', Rule::in(array_keys(Asset::CATEGORIES))];
        }

        $data = $request->validate($rules);
        unset($data['photo'], $data['calibration_certificate'], $data['remove_calibration_certificate']);
        $data['requires_calibration'] = $request->boolean('requires_calibration');
        $data['asset_type'] = $data['requires_calibration'] ? Asset::TYPE_MEASURING : Asset::TYPE_GENERAL;

        if ($data['status'] === 'under_calibration' && ! $data['requires_calibration']) {
            throw ValidationException::withMessages([
                'status' => 'Status Dalam kalibrasi hanya dapat digunakan untuk aset yang wajib kalibrasi.',
            ]);
        }

        foreach (['brand', 'model', 'serial_number', 'certificate_number', 'notes'] as $field) {
            $data[$field] = $this->nullableTrim($data[$field] ?? null);
        }

        $data['checklist_items'] = collect($data['checklist_items'])
            ->map(fn (string $item): string => trim($item))
            ->values()
            ->all();

        if (! $data['requires_calibration']) {
            $data['calibrated_at'] = null;
            $data['calibration_due_at'] = null;
            $data['certificate_number'] = null;
        }

        return $data;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function filteredQuery(Request $request): Builder
    {
        return Asset::query()
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.trim((string) $request->string('search')).'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('asset_code', 'like', $search)
                        ->orWhere('equipment_name', 'like', $search)
                        ->orWhere('brand', 'like', $search)
                        ->orWhere('model', 'like', $search)
                        ->orWhere('serial_number', 'like', $search)
                        ->orWhere('location', 'like', $search);
                });
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category_code', $request->string('category')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')));
    }

    private function deleteUploadedPhoto(string $path): void
    {
        if (Str::startsWith($path, 'storage/assets/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }

    /** @return array<string, int|string> */
    private function storeCalibrationCertificate(UploadedFile $file): array
    {
        return [
            'calibration_certificate_path' => $file->store('asset-calibration-certificates', 'local'),
            'calibration_certificate_name' => $file->getClientOriginalName(),
            'calibration_certificate_mime' => $file->getMimeType() ?: 'application/octet-stream',
            'calibration_certificate_size' => (int) $file->getSize(),
        ];
    }

    private function deleteUploadedCalibrationCertificate(string $path): void
    {
        if (Str::startsWith($path, 'asset-calibration-certificates/')) {
            Storage::disk('local')->delete($path);
        }
    }

    /** @param array<int, string> $items */
    private function replaceChecklist(Asset $asset, array $items): void
    {
        $asset->checklistItems()->delete();

        $asset->checklistItems()->createMany(array_map(
            fn (string $label, int $index): array => [
                'label' => $label,
                'sort_order' => $index,
            ],
            $items,
            array_keys($items),
        ));
    }
}
