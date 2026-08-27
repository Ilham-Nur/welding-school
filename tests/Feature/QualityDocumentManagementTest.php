<?php

namespace Tests\Feature;

use App\Models\AuditDocument;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentSection;
use App\Models\DocumentStandard;
use App\Models\User;
use Database\Seeders\QualityDocumentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QualityDocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_landing_shows_audit_data_and_iso_standard_cards(): void
    {
        $this->seedQualityModule();
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.quality-documents.index'))
            ->assertOk()
            ->assertSee('Quality Documents')
            ->assertSee('Data Audit')
            ->assertSee('ISO 9001')
            ->assertSee('ISO 17025')
            ->assertSee('Quality standard')
            ->assertDontSee('class="qd-tabs"', false)
            ->assertSee('Training')
            ->assertDontSee('Coming Soon');

        $standard = DocumentStandard::query()->where('slug', '9001')->firstOrFail();
        $this->get(route('admin.quality-documents.standards.show', $standard))
            ->assertOk()
            ->assertSee('Review')
            ->assertSee('Manual Mutu')
            ->assertSee('Quality Procedure')
            ->assertSee('Working Instruction')
            ->assertSee('Form')
            ->assertSee('Main Document')
            ->assertSee('Second Document');

        $this->get(route('admin.quality-documents.documents.create', $standard))
            ->assertOk()
            ->assertSee('data-file-drop', false)
            ->assertSee('data-max-size-mb="20"', false)
            ->assertSee('Pilih file atau tarik ke area ini')
            ->assertSee('Simpan sebagai Draft');

        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)->get(route('admin.quality-documents.index'))->assertForbidden();
    }

    public function test_view_only_role_can_view_but_cannot_change_quality_or_audit_documents(): void
    {
        $this->seedQualityModule();
        $role = Role::findOrCreate('quality-viewer', 'web');
        $role->syncPermissions(['admin.access', 'quality-documents.view']);
        $viewer = User::factory()->create();
        $viewer->assignRole($role);
        $standard = DocumentStandard::query()->where('slug', '9001')->firstOrFail();
        $manualCategory = DocumentCategory::query()->where('code', 'MM')->firstOrFail();
        $auditDocument = AuditDocument::query()->create([
            'title' => 'Dokumen Audit Viewer',
            'file_path' => 'quality-documents/audit/viewer.pdf',
            'file_name' => 'viewer.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'created_by' => $viewer->id,
            'updated_by' => $viewer->id,
        ]);
        $document = Document::query()->create([
            'standard_id' => $standard->id,
            'category_id' => $manualCategory->id,
            'document_code' => 'MM-VIEW-001',
            'title' => 'Manual Mutu Viewer',
            'status' => 'active',
            'original_file_path' => 'quality-documents/9001/original/viewer.pdf',
            'original_file_name' => 'viewer.pdf',
            'original_file_type' => 'pdf',
            'original_file_size' => 1024,
            'created_by' => $viewer->id,
            'updated_by' => $viewer->id,
        ]);
        $revision = $document->revisions()->create([
            'document_code' => $document->document_code,
            'title' => $document->title,
            'status' => 'active',
            'revision_number' => 0,
            'original_file_path' => $document->original_file_path,
            'original_file_name' => $document->original_file_name,
            'original_file_type' => 'pdf',
            'original_file_size' => 1024,
            'conversion_status' => 'not_required',
            'created_by' => $viewer->id,
        ]);

        $this->actingAs($viewer)->get(route('admin.quality-documents.index'))->assertOk();
        $this->get(route('admin.quality-documents.audit.index'))
            ->assertOk()
            ->assertDontSee(route('admin.quality-documents.audit.download', $auditDocument), false);
        $this->get(route('admin.quality-documents.documents.show', [$standard, $document]))
            ->assertOk()
            ->assertDontSee('Download asli')
            ->assertDontSee(route('admin.quality-documents.documents.download', [$standard, $document]), false)
            ->assertDontSee(route('admin.quality-documents.revisions.download', $revision), false);
        $this->get(route('admin.quality-documents.audit.download', $auditDocument))->assertForbidden();
        $this->get(route('admin.quality-documents.documents.download', [$standard, $document]))->assertForbidden();
        $this->get(route('admin.quality-documents.revisions.download', $revision))->assertForbidden();
        $this->post(route('admin.quality-documents.standards.store'), ['name' => 'ISO 3834'])->assertForbidden();
        $this->get(route('admin.quality-documents.documents.create', $standard))->assertForbidden();
        $this->get(route('admin.quality-documents.audit.create'))->assertForbidden();
    }

    public function test_admin_can_manage_flexible_audit_documents(): void
    {
        Storage::fake('local');
        $this->seedQualityModule();
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $this->post(route('admin.quality-documents.audit.store'), [
            'title' => 'NIB Perusahaan',
            'description' => 'Dokumen legal untuk auditor.',
            'file' => UploadedFile::fake()->create('nib.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('admin.quality-documents.audit.index'));

        $auditDocument = AuditDocument::query()->firstOrFail();
        Storage::disk('local')->assertExists($auditDocument->file_path);
        $this->get(route('admin.quality-documents.audit.index'))
            ->assertOk()
            ->assertSee('NIB Perusahaan')
            ->assertSee('nib.pdf')
            ->assertSee('Dokumen legal untuk auditor.')
            ->assertSee('data-confirm-dialog="delete-audit-document-'.$auditDocument->id.'"', false)
            ->assertSee('id="delete-audit-document-'.$auditDocument->id.'"', false)
            ->assertSee('Hapus dokumen audit?')
            ->assertDontSee('return confirm(', false);
        $this->get(route('admin.quality-documents.audit.preview', $auditDocument))->assertOk();
        $this->get(route('admin.quality-documents.audit.download', $auditDocument))->assertOk();

        $oldPath = $auditDocument->file_path;
        $this->put(route('admin.quality-documents.audit.update', $auditDocument), [
            'title' => 'NIB Perusahaan Terbaru',
            'description' => 'Dokumen legal terbaru.',
            'file' => UploadedFile::fake()->create('nib-terbaru.pdf', 140, 'application/pdf'),
        ])->assertRedirect(route('admin.quality-documents.audit.index'));

        $auditDocument->refresh();
        $this->assertSame('NIB Perusahaan Terbaru', $auditDocument->title);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($auditDocument->file_path);

        $newPath = $auditDocument->file_path;
        $this->delete(route('admin.quality-documents.audit.destroy', $auditDocument))->assertRedirect();
        $this->assertDatabaseMissing('audit_documents', ['id' => $auditDocument->id]);
        Storage::disk('local')->assertMissing($newPath);
    }

    public function test_manual_is_managed_per_chapter_with_draft_publish_relationship_and_revision_flow(): void
    {
        Storage::fake('local');
        $this->seedQualityModule();
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
        $standard = DocumentStandard::query()->where('slug', '9001')->firstOrFail();
        $qpCategory = DocumentCategory::query()->where('code', 'QP')->firstOrFail();
        $manualCategory = DocumentCategory::query()->where('code', 'MM')->firstOrFail();

        $this->post(route('admin.quality-documents.sections.store', $standard), [
            'chapter_number' => '7',
            'title' => 'Support',
        ])->assertRedirect();
        $chapter = DocumentSection::query()->where('standard_id', $standard->id)->firstOrFail();

        $this->get(route('admin.quality-documents.sections.create', $standard))
            ->assertOk()
            ->assertSee('Tambah Bab Utama')
            ->assertDontSee('name="parent_id"', false)
            ->assertDontSee('Bab induk');

        $this->post(route('admin.quality-documents.sections.store', $standard), [
            'parent_id' => $chapter->id,
            'chapter_number' => '8',
            'title' => 'Operation',
        ])->assertRedirect();
        $secondChapter = DocumentSection::query()->where('standard_id', $standard->id)->where('chapter_number', '8')->firstOrFail();
        $this->assertNull($secondChapter->parent_id);

        $this->post(route('admin.quality-documents.documents.store', $standard), [
            'category_id' => $qpCategory->id,
            'section_ids' => [$chapter->id],
            'document_code' => 'QP-QA-001',
            'title' => 'Prosedur Pengendalian Dokumen',
            'effective_date' => '2026-08-27',
            'original_file' => UploadedFile::fake()->create('prosedur.pdf', 120, 'application/pdf'),
            'notes' => 'Penerbitan awal.',
        ])->assertRedirect();
        $procedure = Document::query()->where('document_code', 'QP-QA-001')->firstOrFail();
        $this->assertSame('draft', $procedure->status);
        $this->post(route('admin.quality-documents.documents.publish', [$standard, $procedure]))->assertRedirect();

        $this->post(route('admin.quality-documents.documents.store', $standard), [
            'category_id' => $manualCategory->id,
            'section_ids' => [$chapter->id],
            'related_document_ids' => [$procedure->id],
            'document_code' => 'MM-QA-007',
            'title' => 'Manual Mutu Bab 7',
            'effective_date' => '2026-08-27',
            'original_file' => UploadedFile::fake()->create('manual-bab-7.pdf', 180, 'application/pdf'),
            'notes' => 'Draft awal.',
        ])->assertRedirect();

        $manual = Document::query()->where('document_code', 'MM-QA-007')->with(['sections', 'revisions'])->firstOrFail();
        $procedure->refresh()->load('sections');
        $this->assertSame('draft', $manual->status);
        $this->assertSame($chapter->id, $manual->section_id);
        $this->assertTrue($manual->sections->contains($chapter));
        $this->assertTrue($procedure->sections->contains($chapter));
        $this->assertSame(0, $manual->revision_number);
        $this->assertCount(1, $manual->revisions);

        $this->post(route('admin.quality-documents.documents.store', $standard), [
            'category_id' => $manualCategory->id,
            'section_ids' => [$secondChapter->id],
            'document_code' => 'MM-QA-008',
            'title' => 'Manual Mutu Bab 8',
            'original_file' => UploadedFile::fake()->create('manual-bab-8.pdf', 170, 'application/pdf'),
        ])->assertRedirect();
        $secondManual = Document::query()->where('document_code', 'MM-QA-008')->firstOrFail();

        $this->get(route('admin.quality-documents.documents.show', [$standard, $manual]))
            ->assertOk()
            ->assertSee('data-confirm-dialog="publish-quality-document-'.$manual->id.'"', false)
            ->assertSee('id="publish-quality-document-'.$manual->id.'"', false)
            ->assertSee('data-confirm-dialog="delete-quality-document-'.$manual->id.'"', false)
            ->assertSee('id="delete-quality-document-'.$manual->id.'"', false)
            ->assertSee('Terbitkan dokumen?')
            ->assertSee('Hapus Draft dokumen?')
            ->assertDontSee('return confirm(', false);
        $this->get(route('admin.quality-documents.documents.download', [$standard, $manual]))->assertOk();
        $this->get(route('admin.quality-documents.revisions.download', $manual->revisions()->firstOrFail()))->assertOk();

        $this->get(route('admin.quality-documents.standards.show', ['standard' => $standard, 'tab' => 'manual-mutu']))
            ->assertOk()
            ->assertSee('data-confirm-dialog="delete-quality-section-'.$chapter->id.'"', false)
            ->assertSee('id="delete-quality-section-'.$chapter->id.'"', false)
            ->assertSee('Hapus struktur Bab?')
            ->assertSee('Struktur Bab Utama')
            ->assertDontSee('<th>Induk</th>', false)
            ->assertDontSee('return confirm(', false);

        $this->get(route('admin.quality-documents.standards.show', $standard))
            ->assertOk()
            ->assertSee('MM-QA-007')
            ->assertSee('QP-QA-001')
            ->assertSee('7. Support')
            ->assertSee('8. Operation')
            ->assertSee('data-manual-preview="'.route('admin.quality-documents.documents.preview', [$standard, $manual]).'"', false)
            ->assertSee('data-manual-preview="'.route('admin.quality-documents.documents.preview', [$standard, $secondManual]).'"', false)
            ->assertSee('Manual Mutu Draft');

        $oldManualPath = $manual->original_file_path;
        $this->put(route('admin.quality-documents.documents.update', [$standard, $manual]), [
            'document_code' => 'MM-QA-007',
            'title' => 'Manual Mutu Bab 7 — Support',
            'effective_date' => '2026-08-28',
            'section_ids' => [$chapter->id],
            'related_document_ids' => [$procedure->id],
            'original_file' => UploadedFile::fake()->create('manual-bab-7-benar.pdf', 190, 'application/pdf'),
        ])->assertRedirect();
        $manual->refresh();
        $this->assertSame(0, $manual->revision_number);
        $this->assertSame('Manual Mutu Bab 7 — Support', $manual->title);
        Storage::disk('local')->assertMissing($oldManualPath);
        Storage::disk('local')->assertExists($manual->original_file_path);

        $this->post(route('admin.quality-documents.documents.publish', [$standard, $manual]))->assertRedirect();
        $manual->refresh();
        $this->assertSame('active', $manual->status);

        $this->get(route('admin.quality-documents.documents.show', [$standard, $manual]))
            ->assertOk()
            ->assertSee('data-confirm-dialog="archive-quality-document-'.$manual->id.'"', false)
            ->assertSee('id="archive-quality-document-'.$manual->id.'"', false)
            ->assertSee('Arsipkan dokumen?')
            ->assertSee('class="qd-history-date"', false)
            ->assertSee('Oleh')
            ->assertDontSee('return confirm(', false);

        $review = $this->get(route('admin.quality-documents.standards.show', $standard));
        $review->assertOk()
            ->assertSee('data-review-main-document', false)
            ->assertSee('data-review-chapter', false)
            ->assertSee('MM-QA-007')
            ->assertSee('QP-QA-001 · Prosedur Pengendalian Dokumen')
            ->assertSee('target="_blank"', false)
            ->assertSee('7. Support');

        $this->put(route('admin.quality-documents.documents.update', [$standard, $procedure]), [
            'document_code' => 'QP-QA-001',
            'title' => 'Prosedur Pengendalian Dokumen Terkendali',
            'effective_date' => '2026-08-27',
            'section_ids' => [$chapter->id],
        ])->assertRedirect();
        $procedure->refresh();
        $this->assertSame(0, $procedure->revision_number);

        $this->from(route('admin.quality-documents.documents.edit', [$standard, $procedure]))
            ->put(route('admin.quality-documents.documents.update', [$standard, $procedure]), [
                'document_code' => 'QP-QA-001',
                'title' => $procedure->title,
                'section_ids' => [$chapter->id],
                'original_file' => UploadedFile::fake()->create('tidak-boleh.pdf', 100, 'application/pdf'),
            ])->assertSessionHasErrors('original_file');

        $this->post(route('admin.quality-documents.documents.store', $standard), [
            'document_id' => $procedure->id,
            'category_id' => $qpCategory->id,
            'section_ids' => [$chapter->id],
            'document_code' => 'QP-QA-001',
            'title' => $procedure->title,
            'effective_date' => '2026-09-01',
            'original_file' => UploadedFile::fake()->create('prosedur-rev-01.pdf', 130, 'application/pdf'),
            'notes' => 'Menambahkan ketentuan distribusi.',
        ])->assertRedirect();
        $procedure->refresh()->load(['revisions', 'activityLogs']);
        $this->assertSame(1, $procedure->revision_number);
        $this->assertCount(2, $procedure->revisions);
        $this->assertTrue($procedure->activityLogs->contains('action', 'revised'));

        $this->post(route('admin.quality-documents.documents.store', $standard), [
            'category_id' => $manualCategory->id,
            'section_ids' => [$chapter->id],
            'document_code' => 'MM-QA-DUPLICATE',
            'title' => 'Manual Mutu Ganda',
            'original_file' => UploadedFile::fake()->create('ganda.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('section_ids');
    }

    public function test_replacement_manual_after_archive_still_renders_publish_confirmation(): void
    {
        Storage::fake('local');
        $this->seedQualityModule();
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $standard = DocumentStandard::query()->where('slug', '9001')->firstOrFail();
        $manualCategory = DocumentCategory::query()->where('code', 'MM')->firstOrFail();

        $this->post(route('admin.quality-documents.sections.store', $standard), [
            'chapter_number' => '1',
            'title' => 'Ruang Lingkup',
        ])->assertRedirect();
        $chapter = DocumentSection::query()->where('standard_id', $standard->id)->firstOrFail();

        $this->post(route('admin.quality-documents.documents.store', $standard), [
            'category_id' => $manualCategory->id,
            'section_ids' => [$chapter->id],
            'document_code' => 'MM-QA-001',
            'title' => 'Manual Mutu Lama',
            'original_file' => UploadedFile::fake()->create('manual-lama.pdf', 100, 'application/pdf'),
        ])->assertRedirect();
        $oldManual = Document::query()->where('document_code', 'MM-QA-001')->firstOrFail();

        $this->post(route('admin.quality-documents.documents.publish', [$standard, $oldManual]))->assertRedirect();
        $this->post(route('admin.quality-documents.documents.archive', [$standard, $oldManual]))->assertRedirect();
        $this->assertSame('archived', $oldManual->fresh()->status);

        $this->post(route('admin.quality-documents.documents.store', $standard), [
            'category_id' => $manualCategory->id,
            'section_ids' => [$chapter->id],
            'document_code' => 'MM-QA-001-BARU',
            'title' => 'Manual Mutu Pengganti',
            'original_file' => UploadedFile::fake()->create('manual-pengganti.pdf', 110, 'application/pdf'),
        ])->assertRedirect();
        $newManual = Document::query()->where('document_code', 'MM-QA-001-BARU')->firstOrFail();

        $this->assertSame('draft', $newManual->status);
        $this->assertSame($chapter->id, $newManual->section_id);

        $this->get(route('admin.quality-documents.documents.show', [$standard, $newManual]))
            ->assertOk()
            ->assertSee('data-confirm-dialog="publish-quality-document-'.$newManual->id.'"', false)
            ->assertSee('id="publish-quality-document-'.$newManual->id.'"', false)
            ->assertSee('Terbitkan dokumen?')
            ->assertDontSee('data-confirmed=', false);
    }

    private function seedQualityModule(): void
    {
        $this->seed([RolePermissionSeeder::class, QualityDocumentSeeder::class]);
    }
}
