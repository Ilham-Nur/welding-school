<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetExternalLoan;
use App\Models\Location;
use App\Models\StorageItem;
use App\Models\StorageStock;
use App\Models\StorageStockOpname;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
        $this->storage = Location::query()->create([
            'name' => 'Main Store',
            'is_storage' => true, 'is_active' => true,
        ]);
    }

    public function test_storage_pages_and_permissions_are_available(): void
    {
        $this->get(route('admin.storage.dashboard'))->assertOk()->assertSee('Kontrol Storage')->assertSee('Stok Consumable');
        $this->get(route('admin.storage.receipts.create'))
            ->assertOk()
            ->assertSee('Catat penerimaan barang')
            ->assertDontSee('Harga satuan')
            ->assertDontSee('unit_cost');
        $this->get(route('admin.storage.issues.create'))->assertOk()->assertSee('Catat pengeluaran barang');
        $this->get(route('admin.storage.loans.index'))->assertOk()->assertSee('Riwayat pinjaman');
        $this->get(route('admin.locations.index'))->assertOk()->assertSee('Master lokasi');
        $this->get(route('admin.roles.index'))->assertOk()->assertSee('Storage &amp; consumable', false)->assertSee('storage.transactions.manage');
        $this->assertTrue($this->admin->can('locations.manage'));
        $this->assertTrue($this->admin->can('storage.stocktakes.manage'));
    }

    public function test_location_can_be_main_area_or_child_without_code_and_type(): void
    {
        $this->post(route('admin.locations.store'), [
            'name' => 'Workshop', 'is_active' => '1',
        ])->assertRedirect(route('admin.locations.index'));

        $workshop = Location::query()->where('name', 'Workshop')->firstOrFail();

        $this->post(route('admin.locations.children.store', $workshop), [
            'children' => ['Booth 1', 'Booth 2', 'Booth 3'],
        ])->assertRedirect(route('admin.locations.index'));

        $booth = Location::query()->where('name', 'Booth 1')->firstOrFail();
        $this->assertSame('Workshop / Booth 1', $booth->fullName());
        $this->assertArrayNotHasKey('code', $booth->getAttributes());
        $this->assertArrayNotHasKey('type', $booth->getAttributes());

        $this->get(route('admin.locations.create'))
            ->assertOk()
            ->assertDontSee('Berada di dalam')
            ->assertDontSee('Kode lokasi')
            ->assertDontSee('Jenis');

        $this->get(route('admin.locations.index'))
            ->assertOk()
            ->assertSee('<th>Lokasi</th><th>Status</th><th>Aksi</th>', false)
            ->assertDontSee('<th>Lokasi induk</th>', false)
            ->assertDontSee('<th>Bagian</th>', false)
            ->assertDontSee('<th>Penggunaan</th>', false)
            ->assertSee('data-modal-open="location-detail-'.$workshop->id.'"', false)
            ->assertSee('Tambah bagian')
            ->assertSee('Simpan semua bagian')
            ->assertSee('Booth 1')
            ->assertSee('Booth 2')
            ->assertSee('Booth 3')
            ->assertDontSee('Tambah anak');
    }

    public function test_receipt_and_issue_update_stock_and_create_ledger(): void
    {
        $item = StorageItem::query()->create([
            'code' => 'ELC-E7018', 'name' => 'Elektroda E7018', 'category' => 'Welding',
            'unit' => 'box', 'minimum_stock' => 2, 'is_active' => true, 'notes' => 'Merek Kobelco LB-52U',
        ]);

        $this->get(route('admin.storage.receipts.create'))
            ->assertOk()
            ->assertSee('data-storage-item-picker', false)
            ->assertSee('data-storage-picker-style="select2"', false)
            ->assertSee('data-storage-line', false)
            ->assertSee('Cari berdasarkan kode, nama, kategori, merek, atau spesifikasi consumable.')
            ->assertDontSee('Ketik kode, nama, kategori, merek, atau spesifikasi.')
            ->assertSee('data-search="ELC-E7018 Elektroda E7018 Welding box Merek Kobelco LB-52U"', false);

        $this->post(route('admin.storage.receipts.store'), [
            'transaction_date' => '2026-08-13', 'location_id' => $this->storage->id,
            'supplier' => 'Supplier Test', 'reference' => 'PO-001',
            'lines' => [['storage_item_id' => $item->id, 'quantity' => 10]],
        ])->assertRedirect(route('admin.storage.receipts.index'));

        $this->assertSame(10.0, (float) StorageStock::query()->firstOrFail()->quantity);

        $this->get(route('admin.storage-items.show', $item))
            ->assertOk()
            ->assertSee('10 box')
            ->assertDontSee('10.000 box');

        $this->post(route('admin.storage.issues.store'), [
            'transaction_date' => '2026-08-13', 'location_id' => $this->storage->id,
            'purpose' => 'Praktik SMAW',
            'lines' => [['storage_item_id' => $item->id, 'quantity' => 3]],
        ])->assertRedirect(route('admin.storage.issues.index'));

        $this->assertSame(7.0, (float) StorageStock::query()->firstOrFail()->quantity);
        $this->assertDatabaseCount('storage_transactions', 2);
        $this->assertDatabaseCount('storage_transaction_lines', 2);

        $this->get(route('admin.storage-items.show', $item))
            ->assertOk()
            ->assertSee('7 box')
            ->assertDontSee('7.000 box');

        $this->get(route('admin.storage-items.index'))
            ->assertOk()
            ->assertSee('7 box')
            ->assertSee('2 box')
            ->assertDontSee('7.000 box')
            ->assertDontSee('2.000 box');
    }

    public function test_consumable_internal_code_is_generated_automatically_and_cannot_be_changed(): void
    {
        $this->get(route('admin.storage-items.create'))
            ->assertOk()
            ->assertSee('ATP-CNS-######')
            ->assertSee('Generate kode &amp; simpan', false)
            ->assertDontSee('name="code"', false);

        $this->post(route('admin.storage-items.store'), [
            'name' => 'Kawat Las ER70S-6',
            'category' => 'Welding',
            'unit' => 'roll',
            'minimum_stock' => 2,
            'is_active' => '1',
        ])->assertRedirect(route('admin.storage-items.index'));

        $item = StorageItem::query()->where('name', 'Kawat Las ER70S-6')->firstOrFail();
        $this->assertSame(StorageItem::internalCode($item->id), $item->code);
        $this->assertStringStartsWith('ATP-CNS-', $item->code);

        $this->put(route('admin.storage-items.update', $item), [
            'code' => 'KODE-DARI-USER',
            'name' => 'Kawat Las ER70S-6 Revisi',
            'category' => 'Welding',
            'unit' => 'roll',
            'minimum_stock' => 3,
            'is_active' => '1',
        ])->assertRedirect(route('admin.storage-items.index'));

        $this->assertSame(StorageItem::internalCode($item->id), $item->fresh()->code);
    }

    public function test_issue_is_rejected_when_stock_is_not_enough(): void
    {
        $item = StorageItem::query()->create([
            'code' => 'GRD-001', 'name' => 'Mata Gerinda', 'category' => 'Grinding',
            'unit' => 'pcs', 'minimum_stock' => 5, 'is_active' => true,
        ]);

        $this->from(route('admin.storage.issues.create'))->post(route('admin.storage.issues.store'), [
            'transaction_date' => '2026-08-13', 'location_id' => $this->storage->id,
            'purpose' => 'Workshop', 'lines' => [['storage_item_id' => $item->id, 'quantity' => 1]],
        ])->assertRedirect(route('admin.storage.issues.create'))->assertSessionHasErrors('lines');

        $this->assertDatabaseCount('storage_transactions', 0);
    }

    public function test_multiple_assets_can_be_loaned_to_an_internal_employee_and_returned_together(): void
    {
        $asset = Asset::query()->create([
            'asset_code' => 'ATP-TOL-001', 'category_code' => 'TOL', 'equipment_name' => 'Gerinda Tangan',
            'asset_type' => Asset::TYPE_GENERAL, 'quantity' => 1, 'location' => $this->storage->name,
            'location_id' => $this->storage->id, 'condition' => 'good', 'inspection_interval_months' => 2,
            'status' => 'active', 'requires_calibration' => false,
        ]);
        $secondAsset = Asset::query()->create([
            'asset_code' => 'ATP-TOL-002', 'category_code' => 'TOL', 'equipment_name' => 'Mesin Bor',
            'asset_type' => Asset::TYPE_GENERAL, 'quantity' => 1, 'location' => $this->storage->name,
            'location_id' => $this->storage->id, 'condition' => 'good', 'inspection_interval_months' => 2,
            'status' => 'active', 'requires_calibration' => false,
        ]);

        $this->get(route('admin.storage.loans.create'))
            ->assertOk()
            ->assertSee('data-loan-asset-picker', false)
            ->assertSee('data-loan-picker-style="select2-multiple"', false)
            ->assertSee('name="asset_ids[]" multiple required', false)
            ->assertSee('data-name="Gerinda Tangan"', false)
            ->assertSee('data-name="Mesin Bor"', false)
            ->assertSee('Nama karyawan')
            ->assertDontSee('Lokasi tujuan')
            ->assertDontSee('Kontak peminjam')
            ->assertDontSee('Organisasi');

        $this->post(route('admin.storage.loans.store'), [
            'asset_ids' => [$asset->id, $secondAsset->id], 'borrower_user_id' => $this->admin->id,
            'purpose' => 'Demo pengelasan', 'loaned_at' => '2026-08-13 08:00',
            'condition_out' => 'good',
        ])->assertRedirect(route('admin.storage.loans.index'));

        $loan = AssetExternalLoan::query()->with('items')->firstOrFail();
        $this->assertCount(2, $loan->items);
        $this->assertSame($this->admin->id, $loan->borrower_user_id);
        $this->assertNull($loan->due_at);
        $this->assertSame('on_loan', $asset->fresh()->status);
        $this->assertSame('on_loan', $secondAsset->fresh()->status);

        $this->get(route('admin.storage.loans.index'))
            ->assertOk()
            ->assertSee('2 aset')
            ->assertSee('Gerinda Tangan')
            ->assertSee('Mesin Bor')
            ->assertSee($this->admin->name)
            ->assertSee('Tidak dijadwalkan')
            ->assertSee('data-modal-open="loan-return-'.$loan->id.'"', false)
            ->assertSee('id="loan-return-'.$loan->id.'"', false)
            ->assertSee('Catat pengembalian aset')
            ->assertDontSee('<details class="storage-return">', false);

        $this->patch(route('admin.storage.loans.return', $loan), [
            'returned_at' => '2026-08-14 16:00', 'condition_in' => 'good',
        ])->assertRedirect();

        $this->assertSame('returned', $loan->fresh()->status);
        $this->assertSame('active', $asset->fresh()->status);
        $this->assertSame('active', $secondAsset->fresh()->status);
    }

    public function test_participant_cannot_be_selected_as_asset_borrower(): void
    {
        $participant = User::factory()->create();
        $participant->assignRole('participant');
        $asset = Asset::query()->create([
            'asset_code' => 'ATP-TOL-003', 'category_code' => 'TOL', 'equipment_name' => 'Tang Las',
            'asset_type' => Asset::TYPE_GENERAL, 'quantity' => 1, 'location' => $this->storage->name,
            'location_id' => $this->storage->id, 'condition' => 'good', 'inspection_interval_months' => 2,
            'status' => 'active', 'requires_calibration' => false,
        ]);

        $this->post(route('admin.storage.loans.store'), [
            'asset_ids' => [$asset->id], 'borrower_user_id' => $participant->id,
            'purpose' => 'Percobaan', 'loaned_at' => '2026-08-13 08:00', 'condition_out' => 'good',
        ])->assertSessionHasErrors('borrower_user_id');

        $this->assertDatabaseCount('asset_external_loans', 0);
    }

    public function test_storage_accepts_indonesian_thousand_separator_in_numeric_inputs(): void
    {
        $this->post(route('admin.storage-items.store'), [
            'name' => 'Kawat Uji', 'category' => 'Welding', 'unit' => 'pcs',
            'minimum_stock' => '1.000', 'is_active' => '1',
        ])->assertRedirect(route('admin.storage-items.index'));
        $item = StorageItem::query()->where('name', 'Kawat Uji')->firstOrFail();
        $this->assertSame(1000.0, (float) $item->minimum_stock);
        $this->get(route('admin.storage.receipts.create'))->assertOk()->assertSee('data-number-format', false);
        $decimalItem = StorageItem::query()->create([
            'code' => 'DEC-001', 'name' => 'Flux Uji', 'category' => 'Welding',
            'unit' => 'kg', 'minimum_stock' => 0, 'is_active' => true,
        ]);

        $this->post(route('admin.storage.receipts.store'), [
            'transaction_date' => '2026-08-13', 'location_id' => $this->storage->id,
            'lines' => [
                ['storage_item_id' => $item->id, 'quantity' => '1.000'],
                ['storage_item_id' => $decimalItem->id, 'quantity' => '1,5'],
            ],
        ])->assertRedirect(route('admin.storage.receipts.index'));
        $this->assertSame(1000.0, (float) StorageStock::query()->where('storage_item_id', $item->id)->value('quantity'));
        $this->assertSame(1.5, (float) StorageStock::query()->where('storage_item_id', $decimalItem->id)->value('quantity'));

        $this->get(route('admin.storage-items.index'))
            ->assertOk()
            ->assertSee('1.000 pcs')
            ->assertSee('1,5 kg');

        $this->get(route('admin.storage-items.show', $item))
            ->assertOk()
            ->assertSee('1.000 pcs');

        $this->get(route('admin.storage-items.show', $decimalItem))
            ->assertOk()
            ->assertSee('1,5 kg');

        $this->get(route('admin.storage-items.edit', $item))
            ->assertOk()
            ->assertSee('value="1.000"', false);
    }

    public function test_storage_report_can_export_excel_and_pdf_with_company_identity(): void
    {
        $item = StorageItem::query()->create([
            'code' => 'EXP-001', 'name' => 'Elektroda Export', 'category' => 'Welding',
            'unit' => 'box', 'minimum_stock' => 1, 'is_active' => true,
        ]);
        $this->post(route('admin.storage.receipts.store'), [
            'transaction_date' => '2026-08-13', 'location_id' => $this->storage->id,
            'supplier' => 'Supplier Uji', 'lines' => [['storage_item_id' => $item->id, 'quantity' => 12]],
        ]);

        $excel = $this->get(route('admin.storage.reports.excel'));
        $excel->assertOk()->assertDownload()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $sourcePath = $excel->baseResponse->getFile()->getPathname();
        $zipPath = tempnam(sys_get_temp_dir(), 'storage-report-test-').'.zip';
        copy($sourcePath, $zipPath);
        try {
            $archive = new \PharData($zipPath);
            $sheet = $archive['xl/worksheets/sheet1.xml']->getContent();
            $this->assertStringContainsString(config('branding.company'), $sheet);
            $this->assertStringContainsString('Elektroda Export', $sheet);
            $this->assertStringContainsString('<headerFooter>', $sheet);
            $this->assertTrue(isset($archive['xl/media/logo.png']));
            $this->assertTrue(isset($archive['xl/drawings/drawing1.xml']));
        } finally {
            unset($archive);
            @unlink($zipPath);
            @unlink($sourcePath);
        }

        $pdf = $this->get(route('admin.storage.reports.pdf'));
        $pdf->assertOk()->assertDownload()->assertHeader('content-type', 'application/pdf');
        $pdfPath = $pdf->baseResponse->getFile()->getPathname();
        $this->assertStringStartsWith('%PDF-1.4', file_get_contents($pdfPath));
        @unlink($pdfPath);
    }

    public function test_stock_opname_updates_balance_and_records_adjustment(): void
    {
        $item = StorageItem::query()->create([
            'code' => 'WIRE-ER70S', 'name' => 'Kawat Las ER70S', 'category' => 'Welding',
            'unit' => 'kg', 'minimum_stock' => 5, 'is_active' => true,
        ]);
        StorageStock::query()->create([
            'storage_item_id' => $item->id, 'location_id' => $this->storage->id, 'quantity' => 10,
        ]);

        $this->post(route('admin.storage.opnames.store'), [
            'location_id' => $this->storage->id, 'counted_at' => '2026-08-13',
        ])->assertRedirect();

        $opname = StorageStockOpname::query()->with('lines')->firstOrFail();
        $line = $opname->lines->firstOrFail();
        $this->patch(route('admin.storage.opnames.complete', $opname), [
            'counts' => [$line->id => 8.5],
            'line_notes' => [$line->id => 'Selisih hasil hitung fisik'],
        ])->assertRedirect(route('admin.storage.opnames.show', $opname));

        $this->assertSame(8.5, (float) StorageStock::query()->firstOrFail()->quantity);
        $this->assertSame(-1.5, (float) $line->fresh()->difference);
        $this->assertDatabaseHas('storage_transactions', ['type' => 'adjustment', 'reference' => $opname->number]);
    }

    public function test_storage_view_permission_does_not_allow_stock_mutation(): void
    {
        $role = Role::query()->create(['name' => 'storage-viewer', 'guard_name' => 'web']);
        $role->syncPermissions(['admin.access', 'storage.view', 'storage.reports.view', 'locations.view']);
        $viewer = User::factory()->create();
        $viewer->assignRole($role);

        $this->actingAs($viewer)
            ->get(route('admin.storage.dashboard'))
            ->assertOk();
        $this->post(route('admin.storage.receipts.store'), [])
            ->assertForbidden();
        $this->get(route('admin.locations.create'))
            ->assertForbidden();
        $this->post(route('admin.locations.children.store', $this->storage), [
            'children' => ['Rak A'],
        ])->assertForbidden();
    }
}
