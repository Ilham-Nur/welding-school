<?php

namespace Tests\Feature;

use App\Models\BatchChecklistItem;
use App\Models\Location;
use App\Models\StorageItem;
use App\Models\StorageStock;
use App\Models\StorageUnit;
use App\Models\TrainingApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingBudget;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\Training\BudgetCalculator;
use App\Services\Training\TrainingPricing;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrainingBudgetTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TrainingProgram $program;

    private TrainingBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
        $this->program = TrainingProgram::create(['code' => 'RAB-SMAW', 'title' => 'Welder RAB', 'category' => 'Welding', 'duration_hours' => 80, 'price' => 3500000, 'status' => 'active']);
        $this->batch = $this->batch();
        $this->actingAs($this->admin);
    }

    public function test_excel_reference_reconciles_and_all_pages_render(): void
    {
        $this->get(route('admin.budgets.create'))->assertOk()->assertSee('Susun RAB Welder');
        $budget = $this->saveBudget();
        $totals = app(BudgetCalculator::class)->calculate($budget->inputs);
        $this->assertSame(89100000, $totals['base']);
        $this->assertSame(4455000, $totals['reserve']);
        $this->assertSame(93555000, $totals['total']);
        $this->assertSame(8108100, $totals['price']);
        foreach (['index', 'show', 'edit'] as $page) {
            $response = $this->get(route('admin.budgets.'.$page, $page === 'index' ? [] : $budget))->assertOk();
            if (getenv('BUDGET_CAPTURE_DIR')) {
                file_put_contents(getenv('BUDGET_CAPTURE_DIR').'/'.$page.'.html', $response->getContent());
            }
        }
        $this->assertDatabaseCount('training_budget_events', 1);
    }

    public function test_three_batches_charge_shared_costs_once_and_support_specific_items(): void
    {
        $data = $this->payload();
        $second = $this->batch();
        $third = $this->batch();
        $data['batches'][] = [...$data['batches'][0], 'id' => $second->id];
        $data['batches'][] = [...$data['batches'][0], 'id' => $third->id];
        $totals = app(BudgetCalculator::class)->calculate($data);
        $this->assertSame(2700000, $totals['shared']);
        $this->assertSame(261900000, $totals['base']);
        $this->assertSame(45, $totals['participants']);
        $this->assertSame($totals['base'], array_sum(array_column($totals['allocation'], 'base')));
        $data['items'][14]['batch_id'] = $second->id; // Consumable discs only for the second batch.
        $specific = app(BudgetCalculator::class)->calculate($data);
        $this->assertSame($totals['base'] - 3000000, $specific['base']);
        $budget = $this->saveBudget($data);
        $this->assertSame(3, $budget->batches()->count());
    }

    public function test_live_preview_matches_saved_calculation_without_writing_records(): void
    {
        $data = $this->payload();
        unset($data['name'], $data['gateway_notes']);
        $this->postJson(route('admin.budgets.preview'), $data)->assertOk()
            ->assertJsonPath('totals.price', 8108100)
            ->assertJsonPath('totals.base', 89100000)
            ->assertJsonPath('totals.lines.0.index', 0);
        $this->assertDatabaseCount('training_budgets', 0);
        $this->assertDatabaseCount('training_budget_events', 0);
        $this->assertNull($this->batch->fresh()->training_budget_id);

        $data['gateway_percent'] = 2.9;
        $data['gateway_fixed'] = 2000;
        $data['items'][0]['unit_price'] = 750000;
        $data['batches'][] = [...$data['batches'][0], 'id' => $this->batch()->id, 'days' => 7, 'practice_days' => 5];
        // Sparse indices occur after deleting and adding table rows.
        $data['items'][45] = $data['items'][1];
        unset($data['items'][1]);
        $preview = $this->postJson(route('admin.budgets.preview'), $data)->assertOk()->json('totals');
        $budget = $this->saveBudget([...$data, 'name' => 'RAB tabel', 'gateway_notes' => 'Tarif uji']);
        $this->assertEquals(app(BudgetCalculator::class)->calculate($budget->inputs), $preview);
        $this->assertSame(45, $preview['lines'][28]['index']);
    }

    public function test_live_preview_rejects_incomplete_or_inconsistent_inputs_and_requires_permission(): void
    {
        $data = $this->payload();
        $data['items'][0]['quantity_basis'] = 'participants';
        $this->postJson(route('admin.budgets.preview'), $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.scope');
        $data = $this->payload();
        $data['batches'] = [];
        $this->postJson(route('admin.budgets.preview'), $data)->assertUnprocessable();
        $data = $this->payload();
        $data['items'][0]['unit_price'] = '';
        $this->postJson(route('admin.budgets.preview'), $data)->assertUnprocessable();
        $user = User::factory()->create(['status' => 'active']);
        $user->givePermissionTo(['admin.access', 'budgets.view']);
        $this->actingAs($user)->postJson(route('admin.budgets.preview'), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('training_budgets', 0);
    }

    public function test_gateway_and_sales_tax_are_funded_by_final_price(): void
    {
        $data = [...$this->payload(), 'gateway_percent' => 2.9, 'gateway_fixed' => 2000, 'gateway_tax_percent' => 11, 'sales_tax_percent' => 11, 'payments_per_participant' => 2];
        $result = app(BudgetCalculator::class)->calculate($data);
        $expected = (int) ceil((6237000 * 1.3 + 4000 * 1.11) / (1 / 1.11 - .029 * 1.11));
        $this->assertSame($expected, $result['price']);
        $this->assertGreaterThanOrEqual(28066500 - 1, $result['profit']);
        $this->assertLessThan(28066500 + 15, $result['profit']);
    }

    public function test_workflow_permissions_revision_history_and_existing_quotes_are_preserved(): void
    {
        $budget = $this->saveBudget();
        $route = route('admin.budgets.transition', $budget);
        $this->postJson($route, ['action' => 'approve', 'revision' => 1])->assertUnprocessable();
        $this->post($route, ['action' => 'submit', 'revision' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $editor = User::factory()->create(['status' => 'active']);
        $editor->givePermissionTo(['admin.access', 'budgets.view', 'budgets.manage']);
        $this->actingAs($editor)->postJson($route, ['action' => 'approve', 'revision' => 1])->assertForbidden();
        $this->actingAs($this->admin)->post($route, ['action' => 'approve', 'revision' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $quote = app(TrainingPricing::class)->quote($this->batch->fresh());
        $this->assertSame(8108100, $quote['quoted_price']);
        $this->assertSame(0, $quote['quoted_administration_fee']);
        $participant = User::factory()->create();
        $application = TrainingApplication::create([...$quote, 'registration_number' => 'REG-RAB', 'user_id' => $participant->id, 'training_program_id' => $this->program->id, 'training_batch_id' => $this->batch->id, 'status' => 'approved']);
        $this->postJson($route, ['action' => 'revise', 'revision' => 1])->assertUnprocessable();
        $this->post($route, ['action' => 'revise', 'revision' => 1, 'notes' => 'Tarif diperbarui'])->assertSessionHasNoErrors();
        $data = [...$this->payload(), 'revision' => 1, 'markup_percent' => 40];
        $this->put(route('admin.budgets.update', $budget), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(8108100, $budget->fresh()->approved_price);
        $this->putJson(route('admin.budgets.update', $budget), $data)->assertUnprocessable();
        $this->post($route, ['action' => 'submit', 'revision' => 2])->assertSessionHasNoErrors();
        $this->post($route, ['action' => 'approve', 'revision' => 2])->assertSessionHasNoErrors();
        $this->assertSame(8731800, $budget->fresh()->approved_price);
        $this->assertSame(7, $budget->events()->count());
        $this->actingAs($participant)->postJson(route('invoices.store'), ['agreement' => true, 'training_application_id' => $application->id])
            ->assertCreated()->assertJsonPath('invoice.subtotal', 8108100)->assertJsonPath('invoice.total_amount', 8108100)->assertJsonPath('invoice.administration_fee', 0);
        $this->program->update(['price' => 999999]);
        $this->postJson(route('invoices.store'), ['agreement' => true, 'training_application_id' => $application->id])->assertOk()->assertJsonPath('invoice.total_amount', 8108100);
        config()->set('services.midtrans.server_key', 'SB-Mid-server-test');
        Http::fake(['*' => Http::response(['token' => 'fake', 'redirect_url' => 'https://example.test/pay'])]);
        $this->postJson(route('payments.store'), ['invoice_id' => $application->invoice->id, 'payment_method' => 'qris'])->assertSuccessful();
        Http::assertSent(fn ($request) => $request['transaction_details']['gross_amount'] === 8108100 && count($request['item_details']) === 1);
    }

    public function test_batch_membership_and_input_validation_prevent_double_counting(): void
    {
        $data = $this->payload();
        $data['batches'][] = $data['batches'][0];
        $this->postJson(route('admin.budgets.store'), $data)->assertUnprocessable();
        $data = $this->payload();
        $data['batches'][0]['participants'] = 16;
        $this->postJson(route('admin.budgets.store'), $data)->assertUnprocessable();
        $data = $this->payload();
        $data['items'][0]['quantity_basis'] = 'participants';
        $this->postJson(route('admin.budgets.store'), $data)->assertUnprocessable();
        $data = $this->payload();
        $data['gateway_percent'] = 50;
        $data['gateway_tax_percent'] = 100;
        $this->postJson(route('admin.budgets.store'), $data)->assertUnprocessable();
        $otherProgram = $this->program->replicate();
        $otherProgram->code = 'OTHER';
        $otherProgram->save();
        $this->postJson(route('admin.budgets.store'), [...$this->payload(), 'training_program_id' => $otherProgram->id])->assertUnprocessable();
        $budget = $this->saveBudget();
        $this->postJson(route('admin.budgets.store'), $this->payload())->assertUnprocessable();
        $this->approve($budget);
        $this->post(route('admin.budgets.transition', $budget), ['action' => 'revise', 'revision' => 1, 'notes' => 'Ubah anggota'])->assertSessionHasNoErrors();
        $data = [...$this->payload(), 'revision' => 1];
        $data['batches'][0]['id'] = $this->batch()->id;
        $this->putJson(route('admin.budgets.update', $budget), $data)->assertUnprocessable();
        $this->delete(route('admin.batches.destroy', $this->batch))->assertSessionHasErrors('batch');
    }

    public function test_new_batches_require_approval_and_catalog_uses_batch_prices(): void
    {
        $data = $this->batch->only(['training_program_id', 'name', 'capacity']);
        $data += ['code' => 'NEW-BATCH', 'status' => 'open', 'start_date' => today()->addMonth()->toDateString()];
        $this->postJson(route('admin.batches.store'), $data)->assertUnprocessable();
        $data['status'] = 'draft';
        $this->post(route('admin.batches.store'), $data)->assertSessionHasErrors('batch');
        $this->get(route('admin.batches.create'))->assertRedirect(route('admin.budgets.create'));
        $new = $this->batch();
        $this->assertTrue($new->budget_required);
        $data['status'] = 'open';
        $this->putJson(route('admin.batches.update', $new), $data)->assertUnprocessable();
        $budget = $this->saveBudget();
        $this->batch->update(['status' => 'open']); // Even bypassing admin endpoint must not expose unpriced batches.
        $this->get('/')->assertOk()->assertViewHas('catalog', fn ($catalog) => $catalog->first()['batches']->isEmpty());
        $this->approve($budget);
        $this->get('/')->assertOk()->assertViewHas('catalog', fn ($catalog) => $catalog->first()['batches']->first()['price'] === 8108100 && $catalog->first()['batches']->first()['administration_fee'] === 0);
    }

    public function test_registration_requires_current_offer_and_saves_price_snapshot(): void
    {
        Storage::fake('local');
        $budget = $this->saveBudget($this->plannedPayload());
        $this->approve($budget);
        $this->batch = $budget->batches()->firstOrFail();
        $this->batch->update(['status' => 'open']);
        $user = User::factory()->create(['username' => 'budget.participant']);
        $user->participantProfile()->create(['phone' => '081234567890', 'identity_type' => 'ktp', 'identity_number' => '3671000000000002', 'birth_place' => 'Cilegon', 'birth_date' => '1998-03-10', 'gender' => 'male', 'address' => 'Jalan Industri 1', 'city' => 'Cilegon', 'province' => 'Banten', 'last_education' => 'SMA/SMK', 'emergency_contact_name' => 'Kontak', 'emergency_contact_phone' => '081299999999']);
        $payload = ['training_program_id' => $this->program->id, 'training_batch_id' => $this->batch->id, 'expected_price' => 1, 'full_name' => 'Peserta RAB', 'phone' => '081234567890', 'birth_place' => 'Cilegon', 'birth_date' => '1998-03-10', 'address' => 'Jalan Industri 1', 'city' => 'Cilegon', 'education' => 'SMA/SMK', 'emergency_name' => 'Kontak', 'emergency_phone' => '081299999999', 'documents' => ['id' => UploadedFile::fake()->image('ktp.jpg'), 'photo' => UploadedFile::fake()->image('photo.jpg'), 'education' => UploadedFile::fake()->create('ijazah.pdf', 10, 'application/pdf')]];
        $this->actingAs($user)->postJson(route('applications.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('expected_price');
        $this->assertDatabaseCount('training_applications', 0);
        $this->postJson(route('applications.store'), [...$payload, 'expected_price' => 8108100])->assertCreated()->assertJsonPath('application.quoted_price', 8108100);
        $application = TrainingApplication::firstOrFail();
        $this->assertSame(['source' => 'budget', 'budget_id' => $budget->id, 'revision' => 1], $application->pricing_snapshot);
        $this->assertSame(0, $application->quoted_administration_fee);
    }

    public function test_plans_create_batches_only_on_approval_and_revision_does_not_duplicate_them(): void
    {
        $data = $this->plannedPayload();
        $data['batches'][] = [...$data['batches'][0], 'id' => 92, 'name' => 'Rencana kedua'];
        $data['items'][14]['batch_id'] = 92;
        $before = TrainingBatch::count();
        $this->postJson(route('admin.budgets.preview'), $data)->assertOk();
        $budget = $this->saveBudget($data);
        $this->assertSame($before, TrainingBatch::count());
        $this->assertSame(0, $budget->batches()->count());
        $this->get(route('admin.budgets.show', $budget))->assertOk()->assertSee('belum terbentuk');
        $this->get(route('admin.budgets.edit', $budget))->assertOk()->assertSee('+ Rencana batch');
        if (getenv('BUDGET_CAPTURE_DIR')) {
            file_put_contents(getenv('BUDGET_CAPTURE_DIR').'/planned.html', $this->get(route('admin.budgets.edit', $budget))->getContent());
        }
        $this->approve($budget);
        $budget->refresh();
        $this->assertSame($before + 2, TrainingBatch::count());
        $this->assertSame(2, $budget->batches()->count());
        $second = $budget->batches()->where('name', 'Rencana kedua')->firstOrFail();
        $this->assertSame($second->id, $budget->inputs['items'][14]['batch_id']);
        $this->assertSame('draft', $second->status);
        $this->assertTrue($second->budget_required);
        $this->assertSame(15, $second->capacity);
        $this->assertSame(count(BatchChecklistItem::DEFAULTS), $second->checklistItems()->count());
        $this->postJson(route('admin.budgets.transition', $budget), ['action' => 'approve', 'revision' => 1])->assertUnprocessable();
        $this->post(route('admin.budgets.transition', $budget), ['action' => 'revise', 'revision' => 1, 'notes' => 'Ubah markup'])->assertSessionHasNoErrors();
        $updated = [...$budget->inputs, 'name' => $budget->name, 'training_program_id' => $budget->training_program_id, 'revision' => 1, 'markup_percent' => 35];
        $this->put(route('admin.budgets.update', $budget), $updated)->assertSessionHasNoErrors();
        $this->approve($budget->fresh());
        $this->assertSame($before + 2, TrainingBatch::count());
        $this->get(route('admin.budgets.show', $budget))->assertOk();
    }

    public function test_plans_validate_dates_identifiers_and_participant_drivers(): void
    {
        $data = $this->plannedPayload();
        $data['batches'][0]['end_date'] = today()->toDateString();
        $this->postJson(route('admin.budgets.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('batches.0.end_date');
        $data = $this->plannedPayload();
        $data['batches'][] = $data['batches'][0];
        $this->postJson(route('admin.budgets.store'), $data)->assertUnprocessable();
        $data = $this->plannedPayload();
        $data['batches'][0]['minimum_participants'] = 16;
        $this->postJson(route('admin.budgets.store'), $data)->assertUnprocessable();
        $this->assertDatabaseCount('training_budgets', 0);
    }

    public function test_stock_aggregates_material_needs_and_checks_again_at_approval(): void
    {
        $unit = StorageUnit::where('symbol', 'kg')->firstOrFail();
        $item = StorageItem::create(['code' => 'MAT-RAB', 'name' => 'Bahan uji', 'storage_unit_id' => $unit->id, 'is_active' => true]);
        $location = Location::create(['name' => 'Gudang uji', 'is_storage' => true, 'is_active' => true]);
        $stock = StorageStock::create(['storage_item_id' => $item->id, 'location_id' => $location->id, 'quantity' => 30]);
        $data = $this->plannedPayload();
        $data['items'] = [array_merge($data['items'][0], ['stock_kind' => 'material', 'storage_item_id' => $item->id, 'storage_location_id' => $location->id, 'quantity' => 2, 'frequency' => 3, 'stock_unit_factor' => 2])];
        $data['items'][] = $data['items'][0]; // 24 kg total; stock is counted once.
        $this->postJson(route('admin.budgets.preview'), $data)->assertOk()->assertJsonPath('stock.rows.0.required', 24)->assertJsonPath('stock.rows.0.available', 30)->assertJsonPath('stock.rows.0.shortage', 0);
        $budget = $this->saveBudget($data);
        $route = route('admin.budgets.transition', $budget);
        if (getenv('BUDGET_CAPTURE_DIR')) {
            file_put_contents(getenv('BUDGET_CAPTURE_DIR').'/stock.html', $this->get(route('admin.budgets.edit', $budget))->getContent());
            file_put_contents(getenv('BUDGET_CAPTURE_DIR').'/stock-preview.json', $this->postJson(route('admin.budgets.preview'), $data)->getContent());
        }
        $this->post($route, ['action' => 'submit', 'revision' => 1])->assertSessionHasNoErrors();
        $stock->update(['quantity' => 10]);
        $this->postJson($route, ['action' => 'approve', 'revision' => 1])->assertUnprocessable()->assertJsonValidationErrors('procurement_notes');
        $this->assertSame(0, $budget->batches()->count());
        $this->post($route, ['action' => 'revise', 'revision' => 1, 'notes' => 'Lengkapi pembelian'])->assertSessionHasNoErrors();
        $data['procurement_notes'] = 'Beli 14 kg sebelum pelatihan; anggaran material sudah mencakup seluruh kebutuhan.';
        $this->put(route('admin.budgets.update', $budget), [...$data, 'revision' => 1])->assertSessionHasNoErrors();
        $this->approve($budget->fresh());
        $this->assertEquals(10, $stock->fresh()->quantity); // Approval does not move warehouse stock.
        $this->assertEquals(14, $budget->fresh()->approved_snapshot['stock']['rows'][0]['shortage']);
    }

    public function test_unmapped_material_cannot_be_submitted(): void
    {
        $data = $this->plannedPayload();
        $data['items'][0]['stock_kind'] = 'material';
        $budget = $this->saveBudget($data);
        $this->postJson(route('admin.budgets.transition', $budget), ['action' => 'submit', 'revision' => 1])->assertUnprocessable()->assertJsonValidationErrors('stock');
        $this->assertSame('draft', $budget->fresh()->status);
    }

    private function plannedPayload(): array
    {
        $data = $this->payload();
        $data['planning'] = true;
        $data['batches'][0] = [...$data['batches'][0], 'id' => 91, 'name' => 'Rencana pertama', 'start_date' => today()->addMonth()->toDateString(), 'end_date' => today()->addMonth()->addDays(10)->toDateString()];

        return $data;
    }

    private function batch(): TrainingBatch
    {
        return TrainingBatch::create(['training_program_id' => $this->program->id, 'code' => Str::upper(Str::random(8)), 'name' => 'Batch RAB', 'start_date' => today()->addMonth(), 'end_date' => today()->addMonth()->addDays(10), 'capacity' => 15, 'status' => 'draft', 'budget_required' => true]);
    }

    private function payload(): array
    {
        return ['name' => 'RAB Welder contoh', 'training_program_id' => $this->program->id, 'contingency_percent' => 5, 'markup_percent' => 30, 'sales_tax_percent' => 0, 'gateway_percent' => 0, 'gateway_fixed' => 0, 'gateway_tax_percent' => 0, 'payments_per_participant' => 1, 'gateway_notes' => 'Rekonsiliasi Excel tanpa gateway', 'batches' => [['id' => $this->batch->id, 'participants' => 15, 'minimum_participants' => 10, 'days' => 10, 'practice_days' => 8, 'instructors' => 2, 'assistants' => 1, 'machines' => 10, 'tools' => 10]], 'items' => json_decode(file_get_contents(resource_path('data/rab-welder.json')), true)];
    }

    private function saveBudget(?array $data = null): TrainingBudget
    {
        $this->post(route('admin.budgets.store'), $data ?? $this->payload())->assertRedirect()->assertSessionHasNoErrors();

        return TrainingBudget::latest('id')->firstOrFail();
    }

    private function approve(TrainingBudget $budget): void
    {
        foreach (['submit', 'approve'] as $action) {
            $this->post(route('admin.budgets.transition', $budget), ['action' => $action, 'revision' => $budget->revision])->assertRedirect()->assertSessionHasNoErrors();
        }
    }
}
