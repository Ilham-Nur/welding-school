<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\StorageItem;
use App\Models\TrainingBatch;
use App\Models\TrainingBudget;
use App\Models\TrainingProgram;
use App\Services\Training\BudgetCalculator;
use App\Services\Training\BudgetStockCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingBudgetController extends Controller
{
    public function index()
    {
        return view('admin.budgets.index', ['budgets' => TrainingBudget::with('trainingProgram')->withCount('batches')->latest()->paginate(20)]);
    }

    public function create(Request $request)
    {
        return $this->form(new TrainingBudget(['training_program_id' => $request->integer('program'), 'inputs' => [
            'planning' => true, 'batches' => [['id' => 1, 'name' => 'Batch 1', 'participants' => 15, 'minimum_participants' => 10, 'days' => 10, 'practice_days' => 8, 'instructors' => 2, 'assistants' => 1, 'machines' => 10, 'tools' => 10]], 'items' => array_map(fn ($item) => $item + ['stock_kind' => $item['category'] === 'Bahan Praktik' ? 'material' : 'none'], json_decode(file_get_contents(resource_path('data/rab-welder.json')), true)),
            'contingency_percent' => 5, 'markup_percent' => 30, 'sales_tax_percent' => 0,
            'gateway_percent' => 0, 'gateway_fixed' => 0, 'gateway_tax_percent' => 0,
            'payments_per_participant' => 1, 'gateway_notes' => '',
        ]]));
    }

    public function edit(TrainingBudget $budget)
    {
        abort_unless(in_array($budget->status, ['draft', 'revision']), 422, 'Ajukan revisi sebelum mengubah RAB.');

        return $this->form($budget);
    }

    private function form(TrainingBudget $budget)
    {
        return view('admin.budgets.form', [
            'budget' => $budget,
            'storageItems' => StorageItem::where('is_active', true)->orderBy('name')->get(),
            'storageLocations' => Location::where('is_active', true)->where('is_storage', true)->orderBy('name')->get(),
            'programs' => TrainingProgram::orderBy('title')->get(),
            'batches' => TrainingBatch::whereIn('operational_status', ['preparation', 'ready'])
                ->where(fn ($q) => $q->whereNull('training_budget_id')->when($budget->exists, fn ($q) => $q->orWhere('training_budget_id', $budget->id)))
                ->with('trainingProgram')->orderBy('start_date')->get(),
        ]);
    }

    public function store(Request $request, BudgetCalculator $calculator)
    {
        return $this->save($request, new TrainingBudget, $calculator);
    }

    public function preview(Request $request, BudgetCalculator $calculator)
    {
        $inputs = $this->validated($request, preview: true);

        return response()->json(['totals' => $calculator->calculate($inputs), 'stock' => app(BudgetStockCheck::class)->calculate($inputs)]);
    }

    public function update(Request $request, TrainingBudget $budget, BudgetCalculator $calculator)
    {
        return $this->save($request, $budget, $calculator);
    }

    private function save(Request $request, TrainingBudget $budget, BudgetCalculator $calculator)
    {
        $data = $this->validated($request);
        $budget = DB::transaction(function () use ($request, $budget, $data, $calculator) {
            if ($budget->exists) {
                $budget = TrainingBudget::lockForUpdate()->findOrFail($budget->id);
                if (! in_array($budget->status, ['draft', 'revision']) || $request->integer('revision') !== $budget->revision) {
                    throw ValidationException::withMessages(['budget' => 'RAB telah berubah atau terkunci. Muat ulang sebelum menyimpan.']);
                }
            }
            $inputs = collect($data)->except(['name', 'training_program_id', 'revision'])->all();
            $planning = (bool) ($inputs['planning'] ?? false);
            if ($budget->exists && $planning !== (bool) ($budget->inputs['planning'] ?? false)) {
                throw ValidationException::withMessages(['batches' => 'Jenis penyusunan RAB tidak dapat diganti.']);
            }
            $materialized = ! $planning || $budget->approved_price !== null;
            $ids = array_column($inputs['batches'], 'id');
            $existingIds = $budget->exists ? $budget->batches()->pluck('id')->all() : [];
            $allIds = array_unique(array_merge($ids, $existingIds));
            $batches = $materialized ? TrainingBatch::whereIn('id', $allIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id') : collect();
            if ($budget->approved_price !== null && ((int) $budget->training_program_id !== (int) $data['training_program_id'] || array_diff($existingIds, $ids) || array_diff($ids, $existingIds))) {
                throw ValidationException::withMessages(['batches' => 'Keanggotaan batch dan program RAB yang pernah disetujui tetap dipertahankan. Buat RAB baru untuk batch tambahan.']);
            }
            if ($planning && $budget->approved_price !== null) {
                $previousPlans = collect($budget->inputs['batches'])->keyBy('id');
                foreach ($inputs['batches'] as $row) {
                    foreach (['name', 'start_date', 'end_date'] as $key) {
                        if (($row[$key] ?? null) !== ($previousPlans[$row['id']][$key] ?? null)) {
                            throw ValidationException::withMessages(['batches' => 'Ubah nama dan jadwal batch yang sudah terbentuk melalui menu Batch Pelatihan.']);
                        }
                    }
                }
            }
            foreach ($materialized ? $inputs['batches'] : [] as $row) {
                $batch = $batches->get($row['id']);
                if (! $batch || (int) $batch->training_program_id !== (int) $data['training_program_id'] || ($batch->training_budget_id && $batch->training_budget_id !== $budget->id)) {
                    throw ValidationException::withMessages(['batches' => 'Pilih batch dari program yang sama dan belum terikat RAB lain.']);
                }
                if (! in_array($batch->operational_status, ['preparation', 'ready']) || $row['participants'] > $batch->capacity || $row['minimum_participants'] > $row['participants'] || $row['practice_days'] > $row['days']) {
                    throw ValidationException::withMessages(['batches' => 'Periksa status batch, kapasitas, minimum peserta, dan hari praktik.']);
                }
            }
            foreach ($inputs['items'] as $item) {
                if ((! empty($item['batch_id']) && ! in_array($item['batch_id'], $ids)) || ($item['scope'] === 'shared' && ($item['quantity_basis'] !== 'manual' || $item['frequency_basis'] !== 'manual' || ! empty($item['batch_id'])))) {
                    throw ValidationException::withMessages(['items' => 'Biaya bersama menggunakan Qty/frekuensi manual tanpa batch khusus. Batch khusus harus termasuk dalam RAB.']);
                }
            }
            $totals = $calculator->calculate($inputs);
            $budget->fill(['name' => $data['name'], 'training_program_id' => $data['training_program_id'], 'inputs' => $inputs]);
            $budget->revision = $budget->exists ? $budget->revision + 1 : 1;
            $budget->status ??= 'draft';
            $budget->save();
            foreach ($batches as $batch) {
                if (in_array($batch->id, $ids)) {
                    $batch->forceFill(['training_budget_id' => $budget->id, 'budget_required' => true])->save();
                } elseif ($batch->training_budget_id === $budget->id) {
                    $batch->forceFill(['training_budget_id' => null, 'budget_required' => true, 'status' => 'closed'])->save();
                }
            }
            // First approval is required before opening registration, including adopted legacy batches.
            if ($budget->approved_price === null) {
                $budget->batches()->where('status', 'open')->update(['status' => 'closed']);
            }
            $this->log($budget, $request, 'saved', $totals);

            return $budget;
        });

        return to_route('admin.budgets.show', $budget)->with('success', 'RAB disimpan. Periksa ringkasan sebelum diajukan.');
    }

    public function show(TrainingBudget $budget, BudgetCalculator $calculator)
    {
        $budget->load(['trainingProgram', 'batches', 'events.user']);
        $totals = $calculator->calculate($budget->inputs);
        $minimumInputs = $budget->inputs;
        foreach ($minimumInputs['batches'] as &$batch) {
            $batch['participants'] = $batch['minimum_participants'];
        }
        unset($batch);
        $minimum = $calculator->calculate($minimumInputs);
        // Compare demand at the proposed target price, not at a newly raised price.
        $revenue = $totals['price'] * $minimum['participants'];
        $feeTax = 1 + $minimumInputs['gateway_tax_percent'] / 100;
        $minimum['profit_at_target_price'] = (int) round($revenue / (1 + $minimumInputs['sales_tax_percent'] / 100))
            - (int) ceil(($revenue * $minimumInputs['gateway_percent'] / 100 + $minimum['participants'] * $minimumInputs['payments_per_participant'] * $minimumInputs['gateway_fixed']) * $feeTax) - $minimum['total'];

        $stock = app(BudgetStockCheck::class)->calculate($budget->inputs);

        return view('admin.budgets.show', compact('budget', 'totals', 'minimum', 'stock'));
    }

    public function transition(Request $request, TrainingBudget $budget, BudgetCalculator $calculator)
    {
        $data = $request->validate(['action' => ['required', Rule::in(['submit', 'revise', 'approve'])], 'notes' => ['nullable', 'string', 'max:2000'], 'revision' => ['required', 'integer']]);
        abort_unless($request->user()->can($data['action'] === 'approve' ? 'budgets.approve' : 'budgets.manage'), 403);
        DB::transaction(function () use ($request, $budget, $calculator, $data): void {
            $budget = TrainingBudget::lockForUpdate()->findOrFail($budget->id);
            $allowed = ['submit' => ['draft', 'revision'], 'revise' => ['submitted', 'approved'], 'approve' => ['submitted']];
            if (! in_array($budget->status, $allowed[$data['action']]) || $budget->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['budget' => 'Status atau versi RAB berubah. Muat ulang halaman.']);
            }
            if ($data['action'] === 'revise' && blank($data['notes'] ?? null)) {
                throw ValidationException::withMessages(['notes' => 'Tuliskan alasan revisi.']);
            }
            $totals = $calculator->calculate($budget->inputs);
            if (in_array($data['action'], ['submit', 'approve'])) {
                app(BudgetStockCheck::class)->validateForApproval($budget->inputs);
            }
            if ($data['action'] === 'approve') {
                if (($budget->inputs['planning'] ?? false) && $budget->approved_price === null) {
                    $inputs = $budget->inputs;
                    $mapping = [];
                    foreach ($inputs['batches'] as &$plan) {
                        $batch = TrainingBatch::create([
                            'training_program_id' => $budget->training_program_id,
                            'training_budget_id' => $budget->id, 'budget_required' => true,
                            'code' => 'RAB-'.$budget->id.'-'.$plan['id'], 'name' => $plan['name'],
                            'start_date' => $plan['start_date'], 'end_date' => $plan['end_date'] ?? null,
                            'capacity' => $plan['participants'], 'status' => 'draft', 'operational_status' => 'preparation',
                        ]);
                        $mapping[$plan['id']] = $batch->id;
                        $plan['id'] = $batch->id;
                    }
                    unset($plan);
                    foreach ($inputs['items'] as &$item) {
                        if (! empty($item['batch_id'])) {
                            $item['batch_id'] = $mapping[$item['batch_id']];
                        }
                    }
                    unset($item);
                    $budget->inputs = $inputs;
                    $totals = $calculator->calculate($inputs);
                }
                $batches = $budget->batches()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                foreach ($budget->inputs['batches'] as $row) {
                    $batch = $batches->get($row['id']);
                    if (! $batch || $batch->training_program_id !== $budget->training_program_id || $batch->capacity < $row['participants'] || ! in_array($batch->operational_status, ['preparation', 'ready'])) {
                        throw ValidationException::withMessages(['batches' => 'Data batch berubah. Revisi RAB sebelum persetujuan.']);
                    }
                }
                if ($totals['price'] < 1) {
                    throw ValidationException::withMessages(['items' => 'Harga final harus lebih dari nol.']);
                }
                $budget->approved_snapshot = ['inputs' => $budget->inputs, 'totals' => $totals, 'stock' => app(BudgetStockCheck::class)->calculate($budget->inputs)];
                $budget->approved_price = $totals['price'];
                $budget->approved_revision = $budget->revision;
            }
            $budget->status = ['submit' => 'submitted', 'revise' => 'revision', 'approve' => 'approved'][$data['action']];
            $budget->save();
            $this->log($budget, $request, $data['action'], $totals, $data['notes'] ?? null);
        });

        return back()->with('success', 'Status RAB diperbarui. Harga yang pernah ditawarkan dan invoice lama tetap dipertahankan.');
    }

    private function log(TrainingBudget $budget, Request $request, string $action, array $totals, ?string $notes = null): void
    {
        $budget->events()->create(['user_id' => $request->user()->id, 'action' => $action, 'notes' => $notes, 'snapshot' => ['name' => $budget->name, 'revision' => $budget->revision, 'status' => $budget->status, 'inputs' => $budget->inputs, 'totals' => $totals, 'stock' => app(BudgetStockCheck::class)->calculate($budget->inputs)]]);
    }

    private function validated(Request $request, bool $preview = false): array
    {
        $planning = $request->boolean('planning');
        $data = $request->validate([
            'planning' => ['sometimes', 'boolean'], 'procurement_notes' => ['nullable', 'string', 'max:2000'],
            'batches.*.name' => [$planning ? 'required' : 'nullable', 'string', 'max:255'],
            'batches.*.start_date' => [$planning && ! $preview ? 'required' : 'nullable', 'date'],
            'batches.*.end_date' => ['nullable', 'date', 'after_or_equal:batches.*.start_date'],
            'name' => [$preview ? 'nullable' : 'required', 'string', 'max:255'], 'training_program_id' => ['required', 'integer', 'exists:training_programs,id'], 'revision' => ['nullable', 'integer'],
            'contingency_percent' => ['required', 'numeric', 'between:0,100'], 'markup_percent' => ['required', 'numeric', 'between:0,1000'],
            'sales_tax_percent' => ['required', 'numeric', 'between:0,100'], 'gateway_percent' => ['required', 'numeric', 'between:0,50'],
            'gateway_fixed' => ['required', 'integer', 'between:0,10000000'], 'gateway_tax_percent' => ['required', 'numeric', 'between:0,100'],
            'payments_per_participant' => ['required', 'integer', 'between:1,12'], 'gateway_notes' => [$preview ? 'nullable' : 'required', 'string', 'max:1000'],
            'batches' => ['required', 'array', 'min:1', 'max:20'], 'batches.*.id' => ['required', 'integer', 'between:1,2147483647', 'distinct'],
            'batches.*.participants' => ['required', 'integer', 'between:1,10000'], 'batches.*.minimum_participants' => ['required', 'integer', 'between:1,10000'],
            'batches.*.days' => ['required', 'integer', 'between:1,1000'], 'batches.*.practice_days' => ['required', 'integer', 'between:0,1000'],
            'batches.*.instructors' => ['required', 'integer', 'between:0,100'], 'batches.*.assistants' => ['required', 'integer', 'between:0,100'],
            'batches.*.machines' => ['required', 'integer', 'between:0,1000'], 'batches.*.tools' => ['required', 'integer', 'between:0,1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.stock_kind' => ['nullable', Rule::in(['none', 'material'])],
            'items.*.storage_item_id' => ['nullable', 'integer', 'exists:storage_items,id'],
            'items.*.storage_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'items.*.stock_unit_factor' => ['nullable', 'numeric', 'between:0.001,100000'],
            'items.*.category' => ['required', 'string', 'max:100'], 'items.*.description' => ['required', 'string', 'max:255'], 'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.scope' => ['required', Rule::in(['shared', 'batch'])], 'items.*.batch_id' => ['nullable', 'integer'],
            'items.*.quantity_basis' => ['required', Rule::in(array_keys(BudgetCalculator::QUANTITIES))], 'items.*.frequency_basis' => ['required', Rule::in(array_keys(BudgetCalculator::FREQUENCIES))],
            'items.*.quantity' => ['required', 'numeric', 'between:0,10000'], 'items.*.frequency' => ['required', 'numeric', 'between:0,1000'],
            'items.*.unit_price' => ['required', 'integer', 'between:0,100000000'], 'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $ids = array_column($data['batches'], 'id');
        foreach ($data['batches'] as $index => $batch) {
            if ($batch['practice_days'] > $batch['days'] || $batch['minimum_participants'] > $batch['participants']) {
                throw ValidationException::withMessages(["batches.$index.days" => 'Periksa hari praktik dan minimum peserta pada batch.']);
            }
        }
        foreach ($data['items'] as $index => $item) {
            if ((! empty($item['batch_id']) && ! in_array($item['batch_id'], $ids)) || ($item['scope'] === 'shared' && ($item['quantity_basis'] !== 'manual' || $item['frequency_basis'] !== 'manual' || ! empty($item['batch_id'])))) {
                throw ValidationException::withMessages(["items.$index.scope" => 'Biaya bersama memakai dasar manual; batch khusus harus termasuk batch terpilih.']);
            }
        }

        return $data;
    }
}
