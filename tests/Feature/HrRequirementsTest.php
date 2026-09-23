<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeePosition;
use App\Models\EmployeePositionRequirement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HrRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private EmployeePosition $position;

    private Employee $employee;

    private EmployeePositionRequirement $requirement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
        $this->position = EmployeePosition::create(['name' => 'Instruktur Welding', 'department' => 'Pelatihan']);
        $this->employee = Employee::create(['full_name' => 'Budi Instruktur', 'employment_status' => 'tetap', 'position_id' => $this->position->id, 'position' => $this->position->name]);
        $this->requirement = $this->position->requirements()->create($this->requirementData());
        Storage::fake('public');
    }

    private function requirementData(array $overrides = []): array
    {
        return array_replace(['name' => 'Sertifikat Welder 6G', 'category' => 'certificate', 'description' => 'Kompetensi pengelasan 6G', 'is_required' => true, 'has_expiry' => true, 'requires_verification' => true], $overrides);
    }

    private function upload(array $overrides = []): EmployeeDocument
    {
        $this->post(route('admin.employees.documents.store', $this->employee), array_replace([
            'document_label' => 'Sertifikat 6G',
            'requirement_id' => $this->requirement->id,
            'document_file' => UploadedFile::fake()->create('certificate.pdf', 20, 'application/pdf'),
            'expires_at' => today()->addYear()->format('Y-m-d'),
        ], $overrides))->assertSessionHasNoErrors()->assertRedirect();

        return $this->employee->documents()->latest('id')->firstOrFail();
    }

    public function test_hr_pages_show_requirements_and_missing_employee_status(): void
    {
        $this->get(route('admin.employee-positions.index'))->assertOk()->assertSee('HR Management')->assertSee('Pelatihan')->assertSee('1 Persyaratan');
        $this->get(route('admin.employee-positions.requirements.index', $this->position))->assertOk()->assertSee('Sertifikat Welder 6G')->assertSee('Tambah Persyaratan');
        $this->get(route('admin.employees.show', $this->employee))->assertOk()->assertSee('Belum diunggah')->assertSee('0/1 wajib');
        $this->get(route('admin.employees.index'))->assertOk()->assertSee('Belum lengkap');
    }

    public function test_requirement_crud_validates_fields_and_preserves_evidence_on_delete(): void
    {
        $url = route('admin.employee-positions.requirements.store', $this->position);
        $this->post($url, $this->requirementData(['category' => 'invalid']))->assertSessionHasErrors('category');
        $this->post($url, $this->requirementData(['name' => 'Ijazah SMK', 'category' => 'education', 'has_expiry' => false]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_position_requirements', ['name' => 'Ijazah SMK', 'employee_position_id' => $this->position->id]);
        $document = $this->upload();
        $this->delete(route('admin.employee-positions.requirements.destroy', [$this->position, $this->requirement]))->assertRedirect();
        $this->assertNull($document->fresh()->requirement_id);
        Storage::disk('public')->assertExists(preg_replace('/^storage\//', '', $document->file_path));
    }

    public function test_upload_verify_and_expiry_determine_completeness(): void
    {
        $this->travelTo(now()->startOfDay());
        $document = $this->upload(['expires_at' => today()->format('Y-m-d')]);
        $this->assertSame('pending', $this->requirement->documentStatus($document));
        $this->patch(route('admin.employees.documents.verify', [$this->employee, $document]), ['verification_status' => 'approved'])->assertSessionHasNoErrors();
        $document->refresh();
        $this->assertEquals($this->admin->id, $document->verified_by);
        $this->assertSame('valid', $this->requirement->documentStatus($document));
        $this->assertStringContainsString('Lengkap', $this->employee->fresh()->requirementSummary());
        $this->travel(1)->days();
        $this->assertSame('expired', $this->requirement->documentStatus($document));
        $this->assertStringContainsString('Belum lengkap', $this->employee->fresh()->requirementSummary());
        $this->patch(route('admin.employees.documents.verify', [$this->employee, $document]), ['verification_status' => 'approved'])->assertSessionHasErrors('expires_at');
        $this->travelBack();
    }

    public function test_rejection_requires_notes_and_relink_resets_review(): void
    {
        $document = $this->upload();
        $url = route('admin.employees.documents.verify', [$this->employee, $document]);
        $this->patch($url, ['verification_status' => 'rejected'])->assertSessionHasErrors('verification_notes');
        $this->patch($url, ['verification_status' => 'rejected', 'verification_notes' => 'Sertifikat tidak terbaca'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $this->requirement->documentStatus($document->fresh()));
        $this->get(route('admin.employees.show', $this->employee))->assertOk()->assertSee('Ditolak')->assertSee('Sertifikat tidak terbaca');
        $this->put(route('admin.employees.documents.requirement', [$this->employee, $document]), [
            'requirement_id' => $this->requirement->id, 'expires_at' => today()->addYears(2)->format('Y-m-d'),
        ])->assertSessionHasNoErrors();
        $this->assertNull($document->fresh()->verified_at);
        $this->assertSame('pending', $this->requirement->documentStatus($document->fresh()));
    }

    public function test_expiring_requirement_requires_date_and_rejects_foreign_position(): void
    {
        $payload = ['document_label' => 'Bukti', 'document_file' => UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf'), 'requirement_id' => $this->requirement->id];
        $this->post(route('admin.employees.documents.store', $this->employee), $payload)->assertSessionHasErrors('expires_at');
        $otherPosition = EmployeePosition::create(['name' => 'Akuntan']);
        $otherRequirement = $otherPosition->requirements()->create($this->requirementData());
        $payload['requirement_id'] = $otherRequirement->id;
        $this->post(route('admin.employees.documents.store', $this->employee), $payload)->assertSessionHasErrors('requirement_id');
        $this->assertSame(0, $this->employee->documents()->count());
    }

    public function test_old_documents_can_be_linked_and_optional_requirements_do_not_block_completion(): void
    {
        $this->requirement->update(['has_expiry' => false, 'requires_verification' => false]);
        $this->position->requirements()->create($this->requirementData(['name' => 'Sertifikat tambahan', 'is_required' => false]));
        $document = $this->upload(['requirement_id' => null, 'expires_at' => null]);
        $this->assertStringContainsString('Belum lengkap', $this->employee->fresh()->requirementSummary());
        $this->put(route('admin.employees.documents.requirement', [$this->employee, $document]), ['requirement_id' => $this->requirement->id])->assertSessionHasNoErrors();
        $this->assertSame('valid', $this->requirement->documentStatus($document->fresh()));
        $this->assertSame('Lengkap · 1/1 wajib', $this->employee->fresh()->requirementSummary());
    }

    public function test_changed_requirements_need_reverification_but_noop_edits_do_not(): void
    {
        $document = $this->upload();
        $this->patch(route('admin.employees.documents.verify', [$this->employee, $document]), ['verification_status' => 'approved']);
        $url = route('admin.employee-positions.requirements.update', [$this->position, $this->requirement]);
        $this->put($url, $this->requirementData())->assertSessionHasNoErrors();
        $this->assertSame('valid', $this->requirement->fresh()->documentStatus($document->fresh()));
        $this->put($url, $this->requirementData(['description' => 'Sertifikat wajib dari lembaga terakreditasi']))->assertSessionHasNoErrors();
        $this->assertSame('pending', $this->requirement->fresh()->documentStatus($document->fresh()));
    }

    public function test_pending_renewal_keeps_valid_evidence_and_position_change_changes_checklist(): void
    {
        $document = $this->upload();
        $this->patch(route('admin.employees.documents.verify', [$this->employee, $document]), ['verification_status' => 'approved']);
        $this->upload();
        $this->assertSame('Lengkap · 1/1 wajib', $this->employee->fresh()->requirementSummary());
        $otherPosition = EmployeePosition::create(['name' => 'Supervisor']);
        $otherPosition->requirements()->create($this->requirementData(['name' => 'Sertifikat Supervisor']));
        $this->put(route('admin.employees.update', $this->employee), ['full_name' => $this->employee->full_name, 'employment_status' => 'tetap', 'position' => $otherPosition->name])->assertSessionHasNoErrors();
        $this->get(route('admin.employees.show', $this->employee))->assertOk()->assertSee('Sertifikat Supervisor')->assertSee('Jabatan sebelumnya')->assertSee('Belum lengkap');
        $this->patch(route('admin.employees.documents.verify', [$this->employee, $document]), ['verification_status' => 'approved'])->assertNotFound();
    }

    public function test_nested_routes_reject_records_from_another_employee_or_position(): void
    {
        $document = $this->upload();
        $other = Employee::create(['full_name' => 'Karyawan Lain', 'employment_status' => 'tetap', 'position_id' => $this->position->id]);
        $this->patch(route('admin.employees.documents.verify', [$other, $document]), ['verification_status' => 'approved'])->assertNotFound();
        $this->put(route('admin.employees.documents.requirement', [$other, $document]), ['requirement_id' => $this->requirement->id])->assertNotFound();
        $otherPosition = EmployeePosition::create(['name' => 'Posisi Lain']);
        $this->put(route('admin.employee-positions.requirements.update', [$otherPosition, $this->requirement]), $this->requirementData())->assertNotFound();
        $this->delete(route('admin.employee-positions.requirements.destroy', [$otherPosition, $this->requirement]))->assertNotFound();
    }

    public function test_read_only_hr_user_can_view_but_cannot_change_or_verify(): void
    {
        $document = $this->upload();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['admin.access', 'employees.view']);
        $this->actingAs($viewer);
        $this->get(route('admin.employee-positions.requirements.index', $this->position))->assertOk()->assertDontSee('Tambah Persyaratan');
        $this->get(route('admin.employees.show', $this->employee))->assertOk()->assertDontSee('Simpan Verifikasi');
        $this->post(route('admin.employee-positions.requirements.store', $this->position), $this->requirementData())->assertForbidden();
        $this->patch(route('admin.employees.documents.verify', [$this->employee, $document]), ['verification_status' => 'approved'])->assertForbidden();
        $this->put(route('admin.employees.documents.requirement', [$this->employee, $document]), [])->assertForbidden();
    }

    private function employeePayload(array $overrides = []): array
    {
        return array_replace([
            'full_name' => 'Karyawan Form Baru',
            'employment_status' => 'tetap',
            'position' => $this->position->name,
        ], $overrides);
    }

    public function test_employee_creation_uploads_requirements_and_allows_missing_required_evidence(): void
    {
        $this->position->requirements()->create($this->requirementData(['name' => 'Ijazah SMK', 'has_expiry' => false]));
        $this->post(route('admin.employees.store'), $this->employeePayload([
            'requirement_documents' => [$this->requirement->id => [
                'source' => 'upload', 'expires_at' => today()->addYear()->format('Y-m-d'),
                'file' => UploadedFile::fake()->create('sertifikat.pdf', 10, 'application/pdf'),
                'verification_status' => 'approved',
            ]],
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $employee = Employee::where('full_name', 'Karyawan Form Baru')->firstOrFail();
        $document = $employee->documents()->firstOrFail();
        $this->assertSame($this->requirement->id, $document->requirement_id);
        $this->assertSame('pending', $document->verification_status);
        $this->assertSame('Belum lengkap · 0/2 wajib', $employee->requirementSummary());
        Storage::disk('public')->assertExists(preg_replace('/^storage\//', '', $document->file_path));
        $this->get(route('admin.employees.create'))->assertOk()->assertSee('Dokumen Persyaratan Jabatan')->assertSee('requirement_documents['.$this->requirement->id.'][source]', false);
        $this->get(route('admin.employees.edit', $employee))->assertOk()->assertSee('sertifikat.pdf');
        $this->get(route('admin.employees.show', $employee))->assertOk()->assertSee('Aksi Verifikasi')->assertSee('verify-document-'.$document->id)->assertDontSee('Unggah Bukti Persyaratan')->assertDontSee('id="create-document"', false);
    }

    public function test_replacing_evidence_in_edit_resets_review_and_archives_old_file(): void
    {
        $document = $this->upload();
        $this->patch(route('admin.employees.documents.verify', [$this->employee, $document]), ['verification_status' => 'approved'])->assertSessionHasNoErrors();
        $this->put(route('admin.employees.update', $this->employee), $this->employeePayload([
            'requirement_documents' => [$this->requirement->id => ['source' => 'keep', 'expires_at' => $document->expires_at->format('Y-m-d')]],
        ]))->assertSessionHasNoErrors();
        $this->assertSame('approved', $document->fresh()->verification_status);
        $this->put(route('admin.employees.update', $this->employee), $this->employeePayload([
            'requirement_documents' => [$this->requirement->id => [
                'source' => 'upload', 'expires_at' => today()->addYears(2)->format('Y-m-d'),
                'file' => UploadedFile::fake()->create('perpanjangan.pdf', 10, 'application/pdf'),
            ]],
        ]))->assertSessionHasNoErrors();
        $replacement = $this->employee->documents()->where('requirement_id', $this->requirement->id)->firstOrFail();
        $this->assertNotSame($document->id, $replacement->id);
        $this->assertNull($document->fresh()->requirement_id);
        $this->assertNull($replacement->verified_by);
        $this->assertSame('pending', $replacement->verification_status);
        Storage::disk('public')->assertExists(preg_replace('/^storage\//', '', $document->file_path));
        $this->patch(route('admin.employees.documents.verify', [$this->employee, $document]), ['verification_status' => 'approved'])->assertNotFound();
    }

    public function test_new_last_education_file_is_reused_then_replacement_requires_new_review(): void
    {
        $this->requirement->update(['has_expiry' => false]);
        $this->post(route('admin.employees.store'), $this->employeePayload([
            'last_education_file' => UploadedFile::fake()->create('ijazah.pdf', 10, 'application/pdf'),
            'requirement_documents' => [$this->requirement->id => ['source' => 'last_education']],
        ]))->assertSessionHasNoErrors();
        $employee = Employee::where('full_name', 'Karyawan Form Baru')->firstOrFail();
        $document = $employee->documents()->firstOrFail();
        $this->assertSame($employee->last_education_file_path, $document->file_path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->patch(route('admin.employees.documents.verify', [$employee, $document]), ['verification_status' => 'approved'])->assertSessionHasNoErrors();
        $oldToken = $document->fresh()->reviewToken();
        $this->put(route('admin.employees.update', $employee), $this->employeePayload([
            'last_education_file' => UploadedFile::fake()->create('ijazah-baru.pdf', 10, 'application/pdf'),
            'requirement_documents' => [$this->requirement->id => ['source' => 'keep']],
        ]))->assertSessionHasNoErrors();
        $this->assertSame('pending', $document->fresh()->verification_status);
        $this->assertSame($employee->fresh()->last_education_file_path, $document->fresh()->file_path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->patch(route('admin.employees.documents.verify', [$employee, $document]), ['verification_status' => 'approved', 'review_token' => $oldToken])->assertSessionHasErrors('review_token');
        $this->put(route('admin.employees.update', $employee), $this->employeePayload(['remove_last_education_file' => true]))->assertSessionHasNoErrors();
        $this->assertSame(0, $employee->documents()->count());
        $this->assertSame('missing', $employee->fresh()->requirementChecklist()->first()['status']);
    }

    public function test_existing_education_and_general_documents_can_be_used_without_copying_files(): void
    {
        $file = UploadedFile::fake()->create('pendidikan.pdf', 10, 'application/pdf');
        $path = 'storage/'.$file->store('employees/educations', 'public');
        $education = $this->employee->educations()->create(['institution_name' => 'SMK Contoh', 'file_path' => $path, 'file_name' => 'pendidikan.pdf']);
        $this->put(route('admin.employees.update', $this->employee), $this->employeePayload([
            'requirement_documents' => [$this->requirement->id => ['source' => 'education:'.$education->id, 'expires_at' => today()->addYear()->format('Y-m-d')]],
        ]))->assertSessionHasNoErrors();
        $evidence = $this->employee->documents()->firstOrFail();
        $this->assertSame($path, $evidence->file_path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->delete(route('admin.employees.documents.destroy', [$this->employee, $evidence]));
        Storage::disk('public')->assertExists(preg_replace('/^storage\//', '', $path));
        $general = $this->upload(['requirement_id' => null]);
        $this->put(route('admin.employees.update', $this->employee), $this->employeePayload([
            'requirement_documents' => [$this->requirement->id => ['source' => 'document:'.$general->id, 'expires_at' => today()->addYear()->format('Y-m-d')]],
        ]))->assertSessionHasNoErrors();
        $linked = $this->employee->documents()->where('requirement_id', $this->requirement->id)->firstOrFail();
        $this->assertSame($general->file_path, $linked->file_path);
        $this->delete(route('admin.employees.documents.destroy', [$this->employee, $general]));
        Storage::disk('public')->assertExists(preg_replace('/^storage\//', '', $linked->file_path));
    }

    public function test_employee_form_rejects_other_employees_evidence_and_wrong_position_requirements(): void
    {
        $other = Employee::create(['full_name' => 'Pemilik Lain', 'employment_status' => 'tetap']);
        $document = $other->documents()->create(['document_label' => 'Pribadi', 'file_path' => 'storage/private.pdf', 'file_name' => 'private.pdf']);
        $this->put(route('admin.employees.update', $this->employee), $this->employeePayload([
            'requirement_documents' => [$this->requirement->id => ['source' => 'document:'.$document->id]],
        ]))->assertSessionHasErrors('requirement_documents.'.$this->requirement->id.'.source');
        $this->post(route('admin.employees.store'), $this->employeePayload([
            'position' => '', 'requirement_documents' => [$this->requirement->id => ['source' => 'upload']],
        ]))->assertSessionHasErrors('requirement_documents.'.$this->requirement->id.'.source');
        $this->post(route('admin.employees.store'), $this->employeePayload([
            'requirement_documents' => [$this->requirement->id => ['source' => 'upload']],
        ]))->assertSessionHasErrors('requirement_documents.'.$this->requirement->id.'.file');
        $this->post(route('admin.employees.store'), $this->employeePayload([
            'requirement_documents' => [$this->requirement->id => ['source' => 'upload', 'file' => UploadedFile::fake()->create('certificate.pdf', 10, 'application/pdf')]],
        ]))->assertSessionHasErrors('requirement_documents.'.$this->requirement->id.'.expires_at');
        $this->assertDatabaseMissing('employees', ['full_name' => 'Karyawan Form Baru']);
    }

    public function test_edit_permission_does_not_grant_review_and_reviewer_does_not_need_edit_permission(): void
    {
        $document = $this->upload();
        $editor = User::factory()->create();
        $editor->givePermissionTo(['admin.access', 'employees.view', 'employees.manage']);
        $this->actingAs($editor);
        $this->get(route('admin.employees.show', $this->employee))->assertOk()->assertDontSee('Aksi Verifikasi')->assertDontSee('Simpan Verifikasi');
        $this->patch(route('admin.employees.documents.verify', [$this->employee, $document]), ['verification_status' => 'approved'])->assertForbidden();
        $reviewer = User::factory()->create();
        $reviewer->givePermissionTo(['admin.access', 'employees.view', 'employees.verify']);
        $this->actingAs($reviewer);
        $this->get(route('admin.employees.show', $this->employee))->assertOk()->assertSee('Aksi Verifikasi')->assertSee('Simpan Verifikasi');
        $this->get(route('admin.employees.edit', $this->employee))->assertForbidden();
        $this->patch(route('admin.employees.documents.verify', [$this->employee, $document]), ['verification_status' => 'rejected', 'verification_notes' => 'Mohon unggah scan yang jelas'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $document->fresh()->verification_status);
    }

    public function test_edit_form_saves_additional_documents_and_education_with_employee_data(): void
    {
        $this->put(route('admin.employees.update', $this->employee), $this->employeePayload([
            'documents' => [['label' => 'Kontrak Kerja', 'file' => UploadedFile::fake()->create('kontrak.pdf', 10, 'application/pdf')]],
            'educations' => [['institution_name' => 'SMK Contoh', 'education_level' => 'SMK', 'file' => UploadedFile::fake()->create('ijazah.pdf', 10, 'application/pdf')]],
        ]))->assertSessionHasNoErrors();
        $this->assertSame('Kontrak Kerja', $this->employee->documents()->firstOrFail()->document_label);
        $education = $this->employee->educations()->firstOrFail();
        $this->assertSame('SMK Contoh', $education->institution_name);
        $this->assertCount(2, Storage::disk('public')->allFiles());
        $this->get(route('admin.employees.show', $this->employee))->assertOk()->assertDontSee('id="create-education"', false);
        $this->put(route('admin.employees.update', $this->employee), $this->employeePayload([
            'requirement_documents' => [$this->requirement->id => ['source' => 'education:'.$education->id, 'expires_at' => today()->addYear()->format('Y-m-d')]],
        ]))->assertSessionHasNoErrors();
        $this->delete(route('admin.employees.educations.destroy', [$this->employee, $education]))->assertSessionHasNoErrors();
        $this->assertFalse($this->employee->documents()->where('requirement_id', $this->requirement->id)->exists());
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }
}
