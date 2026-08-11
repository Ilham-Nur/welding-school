<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    }

    public function test_asset_id_is_generated_automatically_per_category(): void
    {
        $this->get(route('admin.assets.create'))
            ->assertOk()
            ->assertSee('AWA-WLD-###')
            ->assertSee('Generate Asset ID &amp; simpan', false)
            ->assertSee('Daftar pemeriksaan')
            ->assertSee('Setiap 2 bulan')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('data-asset-photo-input', false)
            ->assertSee('capture="environment"', false)
            ->assertSee('data-asset-photo-cropper', false)
            ->assertSee('name="calibration_certificate"', false)
            ->assertSee('PDF, JPG, JPEG, atau PNG. Maksimal 10 MB.')
            ->assertDontSee('PIC / penanggung jawab')
            ->assertDontSee('id="asset_code"', false);

        $this->post(route('admin.assets.store'), $this->assetPayload())
            ->assertRedirect(route('admin.assets.index'));
        $this->post(route('admin.assets.store'), $this->assetPayload([
            'equipment_name' => 'GTAW Welding Machine',
            'serial_number' => 'GTAW-002',
        ]))->assertRedirect(route('admin.assets.index'));
        $this->post(route('admin.assets.store'), $this->assetPayload([
            'category_code' => 'MSR',
            'equipment_name' => 'Welding Gauge',
            'serial_number' => 'WG-001',
        ]))->assertRedirect(route('admin.assets.index'));

        $this->assertSame(
            ['AWA-MSR-001', 'AWA-WLD-001', 'AWA-WLD-002'],
            Asset::query()->orderBy('asset_code')->pluck('asset_code')->all(),
        );
        $this->assertSame(2, Asset::query()->where('asset_code', 'AWA-WLD-001')->firstOrFail()->checklistItems()->count());
    }

    public function test_asset_photo_can_be_uploaded_replaced_displayed_and_deleted(): void
    {
        Storage::fake('public');

        $this->post(route('admin.assets.store'), $this->assetPayload([
            'photo' => UploadedFile::fake()->image('mesin-las.jpg', 1200, 900),
        ]))->assertRedirect(route('admin.assets.index'));

        $asset = Asset::query()->firstOrFail();
        $firstPhoto = $asset->photo_path;

        $this->assertNotNull($firstPhoto);
        $this->assertStringStartsWith('storage/assets/', $firstPhoto);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $firstPhoto));

        $this->get(route('admin.assets.index'))
            ->assertOk()
            ->assertSee($asset->photoUrl(), false);
        Auth::logout();
        $this->get(route('assets.verify', ['asset' => $asset->public_id]))
            ->assertOk()
            ->assertSee($asset->photoUrl(), false)
            ->assertSee('Foto SMAW Welding Machine');
        $this->actingAs($this->admin);
        $this->get(route('assets.inspections.create', ['asset' => $asset->public_id]))
            ->assertOk()
            ->assertSee($asset->photoUrl(), false);

        $this->put(route('admin.assets.update', $asset), $this->assetPayload([
            'photo' => UploadedFile::fake()->image('mesin-las-baru.png', 1200, 900),
        ]))->assertRedirect(route('admin.assets.index'));

        $asset->refresh();
        $secondPhoto = $asset->photo_path;

        $this->assertNotSame($firstPhoto, $secondPhoto);
        Storage::disk('public')->assertMissing(str_replace('storage/', '', $firstPhoto));
        Storage::disk('public')->assertExists(str_replace('storage/', '', $secondPhoto));

        $this->delete(route('admin.assets.destroy', $asset))
            ->assertRedirect(route('admin.assets.index'));
        Storage::disk('public')->assertMissing(str_replace('storage/', '', $secondPhoto));
    }

    public function test_calibration_certificate_can_be_uploaded_opened_replaced_and_removed(): void
    {
        Storage::fake('local');

        $this->post(route('admin.assets.store'), $this->calibratedPayload([
            'calibration_certificate' => UploadedFile::fake()->create(
                'sertifikat-kalibrasi-awal.pdf',
                240,
                'application/pdf',
            ),
        ]))->assertRedirect(route('admin.assets.index'));

        $asset = Asset::query()->firstOrFail();
        $firstPath = $asset->calibration_certificate_path;

        $this->assertNotNull($firstPath);
        $this->assertStringStartsWith('asset-calibration-certificates/', $firstPath);
        $this->assertSame('sertifikat-kalibrasi-awal.pdf', $asset->calibration_certificate_name);
        Storage::disk('local')->assertExists($firstPath);

        $this->get(route('admin.assets.edit', $asset))
            ->assertOk()
            ->assertSee('sertifikat-kalibrasi-awal.pdf')
            ->assertSee('Hapus file saat disimpan')
            ->assertSee($asset->calibrationCertificateUrl(), false);

        Auth::logout();
        $this->get(route('assets.verify', ['asset' => $asset->public_id]))
            ->assertOk()
            ->assertSee('Buka sertifikat kalibrasi')
            ->assertSee($asset->calibrationCertificateUrl(), false);
        $this->get($asset->calibrationCertificateUrl())
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->admin);
        $this->put(route('admin.assets.update', $asset), $this->calibratedPayload([
            'calibration_certificate' => UploadedFile::fake()->image('sertifikat-kalibrasi-baru.jpg'),
        ]))->assertRedirect(route('admin.assets.index'));

        $asset->refresh();
        $secondPath = $asset->calibration_certificate_path;
        $this->assertNotSame($firstPath, $secondPath);
        $this->assertSame('sertifikat-kalibrasi-baru.jpg', $asset->calibration_certificate_name);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($secondPath);

        $this->put(route('admin.assets.update', $asset), $this->calibratedPayload([
            'remove_calibration_certificate' => '1',
        ]))->assertRedirect(route('admin.assets.index'));

        $asset->refresh();
        $this->assertNull($asset->calibration_certificate_path);
        $this->assertNull($asset->calibration_certificate_name);
        Storage::disk('local')->assertMissing($secondPath);
    }

    public function test_general_asset_cannot_receive_calibration_certificate_file(): void
    {
        Storage::fake('local');

        $this->from(route('admin.assets.create'))
            ->post(route('admin.assets.store'), $this->assetPayload([
                'calibration_certificate' => UploadedFile::fake()->create(
                    'sertifikat-yang-tidak-sesuai.pdf',
                    100,
                    'application/pdf',
                ),
            ]))
            ->assertRedirect(route('admin.assets.create'))
            ->assertSessionHasErrors('calibration_certificate');

        $this->assertDatabaseCount('assets', 0);
    }

    public function test_general_asset_label_uses_generated_id_and_application_logo(): void
    {
        $this->post(route('admin.assets.store'), $this->assetPayload())
            ->assertRedirect(route('admin.assets.index'));

        $asset = Asset::query()->firstOrFail();

        $this->assertSame('AWA-WLD-001', $asset->asset_code);
        $this->assertSame('WLD', $asset->category_code);
        $this->assertNotEmpty($asset->public_id);
        $this->assertSame($this->admin->id, $asset->created_by);

        $this->get(route('admin.assets.labels', ['assets' => [$asset->id]]))
            ->assertOk()
            ->assertSee('AWA-WLD-001')
            ->assertSee('SMAW Welding Machine')
            ->assertDontSee('Workshop Welding Bay 01')
            ->assertDontSee('SERVICEABLE')
            ->assertDontSee('<dt>LOCATION</dt>', false)
            ->assertDontSee('<dt>STATUS</dt>', false)
            ->assertSee('logo_alpha.png')
            ->assertSee('asset-sticker__header-background')
            ->assertSee('fill="#071b32"', false)
            ->assertSee(route('assets.verify', ['asset' => $asset->public_id]), false);
    }

    public function test_calibrated_asset_gets_category_id_and_complete_calibration_label(): void
    {
        $this->post(route('admin.assets.store'), $this->calibratedPayload())
            ->assertRedirect(route('admin.assets.index'));

        $asset = Asset::query()->firstOrFail();

        $this->assertSame('AWA-NDT-001', $asset->asset_code);
        $this->assertTrue($asset->requires_calibration);
        $this->assertSame('valid', $asset->calibrationStatus());

        $this->get(route('admin.assets.labels', ['assets' => [$asset->id]]))
            ->assertOk()
            ->assertSee('UT Flaw Detector')
            ->assertDontSee('CALIBRATED')
            ->assertDontSee('<dt>STATUS</dt>', false)
            ->assertSee('10-06-2026')
            ->assertSee('10-06-2027')
            ->assertSee('CAL-UT-2026-002');
    }

    public function test_active_calibration_asset_requires_complete_and_chronological_data(): void
    {
        $payload = $this->calibratedPayload([
            'serial_number' => '',
            'calibrated_at' => '2026-09-10',
            'calibration_due_at' => '2026-09-01',
            'certificate_number' => '',
        ]);

        $this->from(route('admin.assets.create'))
            ->post(route('admin.assets.store'), $payload)
            ->assertRedirect(route('admin.assets.create'))
            ->assertSessionHasErrors([
                'serial_number',
                'calibration_due_at',
                'certificate_number',
            ]);

        $this->assertDatabaseCount('assets', 0);
    }

    public function test_under_calibration_can_be_registered_before_certificate_is_available(): void
    {
        $payload = $this->calibratedPayload([
            'status' => 'under_calibration',
            'calibrated_at' => '',
            'calibration_due_at' => '',
            'certificate_number' => '',
        ]);

        $this->post(route('admin.assets.store'), $payload)
            ->assertRedirect(route('admin.assets.index'));

        $asset = Asset::query()->firstOrFail();
        $this->assertSame('under_calibration', $asset->status);

        $this->from(route('admin.assets.create'))
            ->post(route('admin.assets.store'), $this->assetPayload(['status' => 'under_calibration']))
            ->assertRedirect(route('admin.assets.create'))
            ->assertSessionHasErrors('status');
    }

    public function test_qr_page_shows_inventory_and_monitoring_data_without_internal_notes(): void
    {
        $this->post(route('admin.assets.store'), $this->assetPayload([
            'brand' => 'Lincoln Electric',
            'model' => 'Invertec V270-S',
            'purchase_year' => 2026,
            'notes' => 'Catatan internal yang tidak boleh tampil.',
        ]));

        $asset = Asset::query()->firstOrFail();

        Auth::logout();

        $this->get(route('assets.verify', ['asset' => $asset->public_id]))
            ->assertOk()
            ->assertSee('ASET TERVERIFIKASI')
            ->assertSee('AWA-WLD-001')
            ->assertSee('Lincoln Electric')
            ->assertSee('Invertec V270-S')
            ->assertSee('Workshop Welding Bay 01')
            ->assertSee('Setiap 2 bulan')
            ->assertSee(today()->addMonthsNoOverflow(2)->translatedFormat('d F Y'))
            ->assertDontSee('QR ini menampilkan identitas dan status alat.')
            ->assertDontSee('Informasi aset publik')
            ->assertDontSee('PIC')
            ->assertDontSee('Catatan internal yang tidak boleh tampil.');
    }

    public function test_expired_calibration_is_red_on_list_and_qr_page_but_hidden_from_label(): void
    {
        $asset = Asset::query()->create([
            ...$this->directAssetData(),
            'asset_code' => 'AWA-MSR-001',
            'category_code' => 'MSR',
            'equipment_name' => 'Welding Gauge',
            'requires_calibration' => true,
            'asset_type' => Asset::TYPE_MEASURING,
            'calibrated_at' => today()->subYear(),
            'calibration_due_at' => today()->subDay(),
            'certificate_number' => 'CAL-OLD-003',
        ]);

        $this->get(route('admin.assets.index'))
            ->assertOk()
            ->assertSee('Kedaluwarsa');

        $this->get(route('admin.assets.labels', ['assets' => [$asset->id]]))
            ->assertOk()
            ->assertDontSee('EXPIRED')
            ->assertDontSee('<dt>STATUS</dt>', false);

        Auth::logout();

        $this->get(route('assets.verify', ['asset' => $asset->public_id]))
            ->assertOk()
            ->assertSee('STATUS KALIBRASI')
            ->assertSee('Kedaluwarsa');
    }

    public function test_direct_qr_always_shows_information_and_inspection_endpoint_shows_checklist(): void
    {
        $this->post(route('admin.assets.store'), $this->assetPayload());
        $asset = Asset::query()->firstOrFail();

        $this->get(route('assets.verify', ['asset' => $asset->public_id]))
            ->assertOk()
            ->assertSee('AWA-WLD-001')
            ->assertDontSee('QR ini menampilkan identitas dan status alat.')
            ->assertDontSee('asset-inspection-form', false);

        $inspectorRole = Role::create(['name' => 'inspector', 'guard_name' => 'web']);
        $inspectorRole->givePermissionTo('assets.inspect');
        $inspector = User::factory()->create();
        $inspector->assignRole($inspectorRole);

        $this->actingAs($inspector);

        $this->get(route('assets.verify', ['asset' => $asset->public_id]))
            ->assertOk()
            ->assertDontSee('QR ini menampilkan identitas dan status alat.')
            ->assertDontSee('asset-inspection-form', false);

        $this->get(route('assets.inspections.create', ['asset' => $asset->public_id]))
            ->assertOk()
            ->assertSee('Checklist maintenance')
            ->assertSee('Periksa kondisi fisik alat')
            ->assertSee('Pastikan fungsi alat berjalan normal');
    }

    public function test_asset_sidebar_uses_dashboard_list_and_inspection_submenu(): void
    {
        $this->get(route('admin.assets.dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan aset dan inspeksi')
            ->assertSee('Dashboard Aset')
            ->assertSee('Daftar Aset')
            ->assertSee('Inspeksi Aset')
            ->assertSee('data-admin-nav-group', false)
            ->assertSee('data-open-asset-scanner', false)
            ->assertSee('data-asset-scan-dialog', false)
            ->assertSee(route('assets.inspections.resolve'), false);
    }

    public function test_scanner_modal_can_find_asset_and_asset_list_has_no_inspection_button(): void
    {
        $this->post(route('admin.assets.store'), $this->assetPayload());
        $asset = Asset::query()->firstOrFail();

        $this->get(route('admin.assets.dashboard'))
            ->assertOk()
            ->assertSee('Scan QR pada label aset')
            ->assertSee('data-asset-scan-dialog', false)
            ->assertSee('data-asset-scanner', false)
            ->assertSee('Aktifkan kamera')
            ->assertSee('Masukkan Asset ID');

        $this->getJson(route('assets.inspections.resolve', ['asset_code' => 'awa-wld-001']))
            ->assertOk()
            ->assertJson([
                'inspection_url' => route('assets.inspections.create', ['asset' => $asset->public_id]),
            ]);

        $this->get(route('admin.assets.index'))
            ->assertOk()
            ->assertDontSee(route('assets.inspections.create', ['asset' => $asset->public_id]), false);
    }

    public function test_scanner_modal_api_shows_error_for_unknown_asset_id(): void
    {
        $this->getJson(route('assets.inspections.resolve', ['asset_code' => 'AWA-WLD-999']))
            ->assertNotFound()
            ->assertJson([
                'message' => 'Asset ID tidak ditemukan. Periksa kembali nomor pada label aset.',
            ]);
    }

    public function test_asset_list_can_export_filtered_excel_workbook(): void
    {
        $this->post(route('admin.assets.store'), $this->assetPayload());
        $this->post(route('admin.assets.store'), $this->assetPayload([
            'category_code' => 'MSR',
            'equipment_name' => 'Digital Caliper',
            'serial_number' => 'DC-2026-001',
        ]));

        $exportUrl = route('admin.assets.export', ['category' => 'MSR']);
        $this->get(route('admin.assets.index', ['category' => 'MSR']))
            ->assertOk()
            ->assertSee('Export Excel')
            ->assertSee($exportUrl, false);

        $response = $this->get($exportUrl);
        $response->assertOk()->assertDownload();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $sourcePath = $response->baseResponse->getFile()->getPathname();
        $zipPath = tempnam(sys_get_temp_dir(), 'asset-export-test-').'.zip';
        copy($sourcePath, $zipPath);

        try {
            $archive = new \PharData($zipPath);
            $sheetXml = $archive['xl/worksheets/sheet1.xml']->getContent();
            $stylesXml = $archive['xl/styles.xml']->getContent();

            $this->assertStringContainsString('DAFTAR ASET ALPHA WELDING ACADEMY', $sheetXml);
            $this->assertStringContainsString('AWA-MSR-001', $sheetXml);
            $this->assertStringContainsString('Digital Caliper', $sheetXml);
            $this->assertStringContainsString('Kategori: MSR', $sheetXml);
            $this->assertStringNotContainsString('AWA-WLD-001', $sheetXml);
            $this->assertStringContainsString('autoFilter ref="A6:W7"', $sheetXml);
            $this->assertStringContainsString('formatCode="dd mmm yyyy"', $stylesXml);
        } finally {
            unset($archive);
            @unlink($zipPath);
            @unlink($sourcePath);
        }
    }

    public function test_inspection_saves_answers_and_updates_asset_condition_status_and_schedule(): void
    {
        $this->post(route('admin.assets.store'), $this->assetPayload());
        $asset = Asset::query()->firstOrFail();
        $items = $asset->checklistItems()->get();

        $this->post(route('assets.inspections.store', ['asset' => $asset->public_id]), [
            'answers' => [
                $items[0]->id => '1',
                $items[1]->id => '0',
            ],
            'condition' => 'damaged',
            'status' => 'maintenance',
            'notes' => 'Kabel perlu diganti sebelum alat digunakan kembali.',
        ])->assertRedirect(route('assets.inspections.create', ['asset' => $asset->public_id]));

        $asset->refresh();
        $this->assertSame('damaged', $asset->condition);
        $this->assertSame('maintenance', $asset->status);
        $this->assertTrue($asset->last_inspected_at->isToday());
        $this->assertTrue($asset->next_inspection_at->isSameDay(today()->addMonthsNoOverflow(2)));

        $this->assertDatabaseHas('asset_inspections', [
            'asset_id' => $asset->id,
            'inspector_id' => $this->admin->id,
            'condition' => 'damaged',
            'status' => 'maintenance',
        ]);
        $this->assertDatabaseHas('asset_inspection_results', [
            'item_label' => 'Pastikan fungsi alat berjalan normal',
            'is_ok' => false,
        ]);

        $this->get(route('assets.inspections.create', ['asset' => $asset->public_id]))
            ->assertOk()
            ->assertSee('Riwayat inspeksi')
            ->assertSee('Kabel perlu diganti sebelum alat digunakan kembali.');
    }

    public function test_user_without_inspection_permission_cannot_open_or_submit_checklist(): void
    {
        $this->post(route('admin.assets.store'), $this->assetPayload());
        $asset = Asset::query()->firstOrFail();
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->get(route('assets.inspections.create', ['asset' => $asset->public_id]))
            ->assertForbidden();

        $this->getJson(route('assets.inspections.resolve', ['asset_code' => 'AWA-WLD-001']))
            ->assertForbidden();

        $this->post(route('assets.inspections.store', ['asset' => $asset->public_id]), [])
            ->assertForbidden();
    }

    public function test_asset_id_and_category_are_immutable_and_deleted_number_is_not_reused(): void
    {
        $this->post(route('admin.assets.store'), $this->assetPayload());
        $asset = Asset::query()->firstOrFail();

        $this->put(route('admin.assets.update', $asset), $this->assetPayload([
            'asset_code' => 'AWA-PPE-999',
            'category_code' => 'PPE',
            'equipment_name' => 'SMAW Welding Machine Updated',
            'location' => 'Workshop Welding Bay 03',
            'status' => 'maintenance',
        ]))->assertRedirect(route('admin.assets.index'));

        $asset->refresh();
        $this->assertSame('AWA-WLD-001', $asset->asset_code);
        $this->assertSame('WLD', $asset->category_code);
        $this->assertSame('Workshop Welding Bay 03', $asset->location);

        $this->delete(route('admin.assets.destroy', $asset))
            ->assertRedirect(route('admin.assets.index'));
        $this->post(route('admin.assets.store'), $this->assetPayload([
            'equipment_name' => 'Replacement Welding Machine',
            'serial_number' => 'REPLACEMENT-002',
        ]));

        $this->assertDatabaseHas('assets', ['asset_code' => 'AWA-WLD-002']);
        $this->assertDatabaseMissing('assets', ['asset_code' => 'AWA-WLD-001']);
    }

    public function test_participant_cannot_access_asset_administration(): void
    {
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->get(route('admin.assets.index'))
            ->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function assetPayload(array $overrides = []): array
    {
        return array_replace([
            'category_code' => 'WLD',
            'equipment_name' => 'SMAW Welding Machine',
            'brand' => 'Alpha Test',
            'model' => 'SMAW-300',
            'serial_number' => 'SMAW-001',
            'quantity' => 1,
            'purchase_year' => 2026,
            'location' => 'Workshop Welding Bay 01',
            'condition' => 'good',
            'inspection_interval_months' => 2,
            'checklist_items' => [
                'Periksa kondisi fisik alat',
                'Pastikan fungsi alat berjalan normal',
            ],
            'status' => 'active',
            'requires_calibration' => '0',
            'notes' => 'Unit praktik utama.',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function calibratedPayload(array $overrides = []): array
    {
        return $this->assetPayload(array_replace([
            'category_code' => 'NDT',
            'equipment_name' => 'UT Flaw Detector',
            'brand' => 'Olympus',
            'model' => 'EPOCH 650',
            'serial_number' => 'UT-2026-002',
            'location' => 'NDT Room',
            'requires_calibration' => '1',
            'calibrated_at' => '2026-06-10',
            'calibration_due_at' => '2027-06-10',
            'certificate_number' => 'CAL-UT-2026-002',
        ], $overrides));
    }

    /** @return array<string, mixed> */
    private function directAssetData(): array
    {
        return [
            'asset_type' => Asset::TYPE_GENERAL,
            'brand' => 'Alpha Test',
            'model' => 'Test Model',
            'serial_number' => 'TEST-001',
            'quantity' => 1,
            'purchase_year' => 2026,
            'location' => 'QC Room',
            'condition' => 'good',
            'inspection_interval_months' => 2,
            'last_inspected_at' => today()->subWeek(),
            'next_inspection_at' => today()->addMonths(3),
            'status' => 'active',
            'requires_calibration' => false,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ];
    }
}
