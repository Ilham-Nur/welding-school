<?php

namespace Tests\Feature;

use App\Models\QualityRecord;
use App\Models\QualityRecordFile;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QualityRecordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_quality_records_and_their_files(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $this->get(route('admin.quality-records.index'))
            ->assertOk()
            ->assertSee('Daftar Quality Record')
            ->assertSee('Belum ada Quality Record');

        $this->post(route('admin.quality-records.store'), [
            'name' => 'Internal Audit 2026',
            'description' => 'Bukti pelaksanaan audit internal.',
        ])->assertRedirect();

        $record = QualityRecord::query()->firstOrFail();
        $this->assertSame($admin->id, $record->created_by);
        $this->get(route('admin.quality-records.index'))
            ->assertOk()
            ->assertSee('Internal Audit 2026')
            ->assertSee('Bukti pelaksanaan audit internal.')
            ->assertSee('Detail');

        $this->post(route('admin.quality-records.files.store', $record), [
            'label' => 'Laporan Audit Semester 1',
            'description' => 'Laporan final yang telah disetujui.',
            'file' => UploadedFile::fake()->create('audit-final.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('admin.quality-records.show', $record));

        $file = QualityRecordFile::query()->firstOrFail();
        Storage::disk('local')->assertExists($file->file_path);
        $this->get(route('admin.quality-records.show', $record))
            ->assertOk()
            ->assertSee('Laporan Audit Semester 1')
            ->assertSee('Laporan final yang telah disetujui.')
            ->assertSee('audit-final.pdf');
        $this->get(route('admin.quality-records.files.preview', [$record, $file]))->assertOk();
        $this->get(route('admin.quality-records.files.download', [$record, $file]))->assertOk();

        $this->put(route('admin.quality-records.files.update', [$record, $file]), [
            'label' => 'Laporan Audit Final',
            'description' => 'Dokumen final.',
        ])->assertRedirect(route('admin.quality-records.show', $record));
        $this->assertSame('Laporan Audit Final', $file->fresh()->label);

        $path = $file->file_path;
        $this->delete(route('admin.quality-records.destroy', $record))
            ->assertRedirect(route('admin.quality-records.index'));
        $this->assertDatabaseMissing('quality_records', ['id' => $record->id]);
        $this->assertDatabaseMissing('quality_record_files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_file_from_another_record_cannot_be_accessed(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $first = QualityRecord::query()->create(['name' => 'Record A', 'slug' => 'record-a']);
        $second = QualityRecord::query()->create(['name' => 'Record B', 'slug' => 'record-b']);
        $file = QualityRecordFile::query()->create([
            'quality_record_id' => $first->id,
            'label' => 'File A',
            'file_path' => 'quality-documents/quality-records/a.pdf',
            'file_name' => 'a.pdf',
            'file_type' => 'pdf',
        ]);

        $this->get(route('admin.quality-records.files.preview', [$second, $file]))->assertNotFound();
        $this->get(route('admin.quality-records.files.edit', [$second, $file]))->assertNotFound();
    }
}
