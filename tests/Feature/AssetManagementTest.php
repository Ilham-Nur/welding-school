<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
        $this->location = Location::query()->create([
            'name' => 'Workshop Welding Bay 01',
            'is_active' => true,
        ]);
    }

    public function test_asset_id_is_generated_automatically_per_category(): void
    {
        $this->get(route('admin.assets.create'))
            ->assertOk()
            ->assertSee('ATP-WLD-###')
            ->assertSee('WLD | Welding Equipment')
            ->assertSee('MSR | Measurement')
            ->assertSee('TOL | Tools')
            ->assertSee('FAC | Facility')
            ->assertSee('DEV | Device')
            ->assertDontSee('NDT | NDT Equipment')
            ->assertDontSee('PPE | Safety Equipment / APD')
            ->assertSee('Generate Asset ID &amp; simpan', false)
            ->assertSee('Daftar pemeriksaan')
            ->assertSee('Setiap 3 bulan')
            ->assertSee('Setiap 6 bulan')
            ->assertSee('Setiap 9 bulan')
            ->assertSee('Setiap 12 bulan')
            ->assertDontSee('Setiap 1 bulan')
            ->assertDontSee('Setiap 2 bulan')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('data-asset-photo-input', false)
            ->assertSee('capture="environment"', false)
            ->assertSee('data-asset-photo-cropper', false)
            ->assertSee('name="calibration_certificate"', false)
            ->assertSee('PDF, JPG, JPEG, atau PNG. Maksimal 10 MB.')
            ->assertDontSee('name="label_size"', false)
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
            ['ATP-MSR-001', 'ATP-WLD-001', 'ATP-WLD-002'],
            Asset::query()->orderBy('asset_code')->pluck('asset_code')->all(),
        );
        $this->assertSame(2, Asset::query()->where('asset_code', 'ATP-WLD-001')->firstOrFail()->checklistItems()->count());
    }

    public function test_only_new_inspection_intervals_are_accepted(): void
    {
        foreach ([3, 6, 9, 12] as $interval) {
            $this->post(route('admin.assets.store'), $this->assetPayload([
                'equipment_name' => "Aset interval {$interval} bulan",
                'serial_number' => "INTERVAL-{$interval}",
                'inspection_interval_months' => $interval,
            ]))->assertRedirect(route('admin.assets.index'));
        }

        $this->from(route('admin.assets.create'))
            ->post(route('admin.assets.store'), $this->assetPayload([
                'equipment_name' => 'Aset interval lama',
                'serial_number' => 'INTERVAL-OLD',
                'inspection_interval_months' => 2,
            ]))
            ->assertRedirect(route('admin.assets.create'))
            ->assertSessionHasErrors('inspection_interval_months');

        $this->assertSame([3, 6, 9, 12], Asset::query()->orderBy('inspection_interval_months')->pluck('inspection_interval_months')->all());
    }

    public function test_legacy_asset_schedule_is_preserved_until_a_new_interval_is_selected(): void
    {
        $legacyDueDate = today()->addMonthsNoOverflow(2);
        $asset = Asset::query()->create(array_merge($this->directAssetData(), [
            'asset_code' => 'ATP-WLD-001',
            'category_code' => 'WLD',
            'equipment_name' => 'Aset lama',
            'inspection_interval_months' => 2,
            'last_inspected_at' => null,
            'next_inspection_at' => $legacyDueDate,
        ]));

        $this->get(route('admin.assets.edit', $asset))
            ->assertOk()
            ->assertSee('Pilih interval inspeksi baru')
            ->assertSee('Aset ini masih memakai interval lama 2 bulan')
            ->assertSee('Setiap 3 bulan')
            ->assertSee('Setiap 6 bulan')
            ->assertSee('Setiap 9 bulan')
            ->assertSee('Setiap 12 bulan');

        $asset->refresh();
        $this->assertSame(2, $asset->inspection_interval_months);
        $this->assertTrue($asset->next_inspection_at->isSameDay($legacyDueDate));

        $this->put(route('admin.assets.update', $asset), $this->assetPayload([
            'inspection_interval_months' => 3,
        ]))->assertRedirect(route('admin.assets.index'));

        $asset->refresh();
        $this->assertSame(3, $asset->inspection_interval_months);
        $this->assertTrue($asset->next_inspection_at->isSameDay($asset->created_at->copy()->addMonthsNoOverflow(3)));
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

        $this->assertSame('ATP-WLD-001', $asset->asset_code);
        $this->assertSame('WLD', $asset->category_code);
        $this->assertNotEmpty($asset->public_id);
        $this->assertSame($this->admin->id, $asset->created_by);

        $this->get(route('admin.assets.labels', ['assets' => [$asset->id]]))
            ->assertOk()
            ->assertSee('ATP-WLD-001')
            ->assertSee('SMAW Welding Machine')
            ->assertSee('Workshop Welding Bay 01')
            ->assertDontSee('SERVICEABLE')
            ->assertSee('<dt>LOCATION</dt>', false)
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

        $this->assertSame('ATP-DEV-001', $asset->asset_code);
        $this->assertTrue($asset->requires_calibration);
        $this->assertSame('valid', $asset->calibrationStatus());

        $this->get(route('admin.assets.labels', ['assets' => [$asset->id]]))
            ->assertOk()
            ->assertSee('UT Flaw Detector')
            ->assertDontSee('CALIBRATED')
            ->assertDontSee('<dt>STATUS</dt>', false)
            ->assertDontSee('<dt>LOCATION</dt>', false)
            ->assertSee('10-06-2026')
            ->assertSee('10-06-2027')
            ->assertSee('CAL-UT-2026-002');
    }

    public function test_label_preview_offers_standard_and_compact_print_sizes(): void
    {
        $this->post(route('admin.assets.store'), $this->calibratedPayload([
            'equipment_name' => 'Digital Caliper',
        ]))->assertRedirect(route('admin.assets.index'));

        $asset = Asset::query()->firstOrFail();

        $this->get(route('admin.assets.labels', ['assets' => [$asset->id]]))
            ->assertOk()
            ->assertSee('data-label-size-select', false)
            ->assertSee('data-label-size-summary', false)
            ->assertSee('data-label-sheet', false)
            ->assertSee('Standar 90 x 42 mm')
            ->assertSee('Ringkas 60 x 31 mm')
            ->assertSee('Digital Caliper')
            ->assertSee('SERIAL NO')
            ->assertSee('DUE DATE')
            ->assertSee('CAL. DATE')
            ->assertSee('CERT. NO')
            ->assertSee(route('assets.verify', ['asset' => $asset->public_id]), false);

        $styles = file_get_contents(public_path('templates/welding-school/assets.css'));
        $this->assertMatchesRegularExpression(
            '/\.asset-label-sheet\s*\{[^}]*align-content:\s*start;/s',
            $styles,
        );
        $this->assertMatchesRegularExpression(
            '/\.asset-sticker--measuring:not\(\.asset-sticker--compact\) \.asset-sticker__details\s*\{[^}]*align-content:\s*stretch;[^}]*grid-template-rows:\s*repeat\(6, minmax\(0, 1fr\)\);/s',
            $styles,
        );
        $this->assertMatchesRegularExpression(
            '/\.asset-sticker--compact:not\(\.asset-sticker--measuring\) \.asset-sticker__details\s*\{[^}]*align-content:\s*stretch;[^}]*grid-template-rows:\s*repeat\(3, minmax\(0, 1fr\)\);/s',
            $styles,
        );
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
            ->assertSee('ATP-WLD-001')
            ->assertSee('Lincoln Electric')
            ->assertSee('Invertec V270-S')
            ->assertSee('Workshop Welding Bay 01')
            ->assertSee('Setiap 6 bulan')
            ->assertSee(today()->addMonthsNoOverflow(6)->translatedFormat('d F Y'))
            ->assertDontSee('QR ini menampilkan identitas dan status alat.')
            ->assertDontSee('Informasi aset publik')
            ->assertDontSee('PIC')
            ->assertDontSee('Catatan internal yang tidak boleh tampil.');
    }

    public function test_expired_calibration_is_red_on_list_and_qr_page_but_hidden_from_label(): void
    {
        $asset = Asset::query()->create([
            ...$this->directAssetData(),
            'asset_code' => 'ATP-MSR-001',
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
            ->assertSee('ATP-WLD-001')
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

        $this->getJson(route('assets.inspections.resolve', ['asset_code' => 'atp-wld-001']))
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
        $this->getJson(route('assets.inspections.resolve', ['asset_code' => 'ATP-WLD-999']))
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
        $workbook = null;

        try {
            $workbook = IOFactory::load($sourcePath);
            $this->assertSame(2, $workbook->getSheetCount());

            $sheet = $workbook->getSheetByName('Daftar Aset');
            $this->assertNotNull($sheet);
            $pageSetup = $sheet->getPageSetup();

            $this->assertSame('Daftar Aset', $sheet->getTitle());
            $this->assertSame('ALPHA ACADEMY', $sheet->getCell('B1')->getValue());
            $this->assertSame('WELDING SCHOOL', $sheet->getCell('B2')->getValue());
            $this->assertStringNotContainsString('Kompeten', (string) $sheet->getCell('B2')->getValue());
            $this->assertSame('DAFTAR ASET ALPHA WELDING ACADEMY', $sheet->getCell('A4')->getValue());
            $this->assertStringContainsString('Kategori: MSR', (string) $sheet->getCell('A5')->getValue());
            $this->assertSame('ATP-MSR-001', $sheet->getCell('A8')->getValue());
            $this->assertSame('Digital Caliper', $sheet->getCell('C8')->getValue());
            $this->assertNull($sheet->getCell('A9')->getValue());
            $this->assertSame('A7:K8', $sheet->getAutoFilter()->getRange());
            $this->assertSame('dd mmm yyyy', $sheet->getStyle('I8')->getNumberFormat()->getFormatCode());
            $this->assertCount(1, $sheet->getDrawingCollection());
            $this->assertSame('Logo Alpha Academy', $sheet->getDrawingCollection()[0]->getName());
            $this->assertSame('A1', $sheet->getDrawingCollection()[0]->getCoordinates());
            $this->assertArrayHasKey('A1:A3', $sheet->getMergeCells());
            $this->assertArrayNotHasKey('A1:B3', $sheet->getMergeCells());
            $this->assertEquals(13, $sheet->getColumnDimension('A')->getWidth());
            $this->assertSame(PageSetup::PAPERSIZE_A4, $pageSetup->getPaperSize());
            $this->assertSame(PageSetup::ORIENTATION_LANDSCAPE, $pageSetup->getOrientation());
            $this->assertSame(1, $pageSetup->getFitToWidth());
            $this->assertSame(0, $pageSetup->getFitToHeight());
            $this->assertSame([7, 7], $pageSetup->getRowsToRepeatAtTop());
            $this->assertSame('A1:K8', str_replace('$', '', $pageSetup->getPrintArea()));
            $this->assertFalse($sheet->getShowGridlines());

            $detailSheet = $workbook->getSheetByName('Data Lengkap');
            $this->assertNotNull($detailSheet);
            $this->assertSame('ATP-MSR-001', $detailSheet->getCell('A8')->getValue());
            $this->assertSame('Digital Caliper', $detailSheet->getCell('C8')->getValue());
            $this->assertSame('A7:W8', $detailSheet->getAutoFilter()->getRange());
            $this->assertSame('dd mmm yyyy', $detailSheet->getStyle('M8')->getNumberFormat()->getFormatCode());
            $this->assertSame(3, $detailSheet->getPageSetup()->getFitToWidth());
            $this->assertSame('A1:W8', str_replace('$', '', $detailSheet->getPageSetup()->getPrintArea()));
            $this->assertStringContainsString('/assets/', $detailSheet->getCell('W8')->getHyperlink()->getUrl());
        } finally {
            $workbook?->disconnectWorksheets();
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
        $this->assertTrue($asset->next_inspection_at->isSameDay(today()->addMonthsNoOverflow(6)));

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
            ->assertSee('data-flash-toast', false)
            ->assertDontSee('asset-inspection-message--success', false)
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

        $this->getJson(route('assets.inspections.resolve', ['asset_code' => 'ATP-WLD-001']))
            ->assertForbidden();

        $this->post(route('assets.inspections.store', ['asset' => $asset->public_id]), [])
            ->assertForbidden();
    }

    public function test_asset_id_and_category_are_immutable_and_deleted_number_is_not_reused(): void
    {
        $this->post(route('admin.assets.store'), $this->assetPayload());
        $asset = Asset::query()->firstOrFail();
        $updatedLocation = Location::query()->create([
            'name' => 'Workshop Welding Bay 03',
            'is_active' => true,
        ]);

        $this->put(route('admin.assets.update', $asset), $this->assetPayload([
            'asset_code' => 'ATP-DEV-999',
            'category_code' => 'DEV',
            'equipment_name' => 'SMAW Welding Machine Updated',
            'location_id' => $updatedLocation->id,
            'status' => 'maintenance',
        ]))->assertRedirect(route('admin.assets.index'));

        $asset->refresh();
        $this->assertSame('ATP-WLD-001', $asset->asset_code);
        $this->assertSame('WLD', $asset->category_code);
        $this->assertSame('Workshop Welding Bay 03', $asset->location);

        $this->delete(route('admin.assets.destroy', $asset))
            ->assertRedirect(route('admin.assets.index'));
        $this->post(route('admin.assets.store'), $this->assetPayload([
            'equipment_name' => 'Replacement Welding Machine',
            'serial_number' => 'REPLACEMENT-002',
        ]));

        $this->assertDatabaseHas('assets', ['asset_code' => 'ATP-WLD-002']);
        $this->assertDatabaseMissing('assets', ['asset_code' => 'ATP-WLD-001']);
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
            'location_id' => $this->location->id,
            'location' => 'Workshop Welding Bay 01',
            'condition' => 'good',
            'inspection_interval_months' => 6,
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
            'category_code' => 'DEV',
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
            'inspection_interval_months' => 6,
            'last_inspected_at' => today()->subWeek(),
            'next_inspection_at' => today()->addMonths(3),
            'status' => 'active',
            'requires_calibration' => false,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ];
    }
}
