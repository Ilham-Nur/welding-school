<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEducation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_employee_index_is_accessible_by_admin(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.employees.index'));
        $response->assertOk();
        $response->assertSee('Data dan Manajemen Karyawan');
        $response->assertSee('Total Karyawan');
    }

    public function test_employee_code_is_automatically_generated(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.employees.store'), [
            'full_name' => 'Karyawan Pertama',
            'employment_status' => 'tetap',
        ]);

        $first = Employee::where('full_name', 'Karyawan Pertama')->first();
        $this->assertNotNull($first);
        $this->assertEquals('ATP001', $first->employee_code);

        $this->post(route('admin.employees.store'), [
            'full_name' => 'Karyawan Kedua',
            'employment_status' => 'kontrak',
        ]);

        $second = Employee::where('full_name', 'Karyawan Kedua')->first();
        $this->assertNotNull($second);
        $this->assertEquals('ATP002', $second->employee_code);
    }

    public function test_admin_can_create_employee_with_photo_education_and_document(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $photo = UploadedFile::fake()->image('profile.jpg', 300, 300);
        $lastEduFile = UploadedFile::fake()->create('ijazah-terakhir.pdf', 100, 'application/pdf');
        $initialEduFile = UploadedFile::fake()->create('ijazah-s1.pdf', 150, 'application/pdf');
        $initialDocFile = UploadedFile::fake()->create('ktp.pdf', 80, 'application/pdf');

        $payload = [
            'full_name' => 'Wahyu Adi Kesuma',
            'gender' => 'laki-laki',
            'birth_place' => 'Palembang',
            'birth_date' => '1985-05-13',
            'position' => 'Direktur',
            'phone' => '0812-7006-2718',
            'emergency_contact_name' => 'Hanum',
            'emergency_contact_phone' => '081267394003',
            'full_address' => 'Tanjung Buntung Blk A1 No 10 RT.08 Rw. 02',
            'identity_number' => '2171091305859009',
            'bpjs_ketenagakerjaan_number' => '1234567890',
            'bpjs_kesehatan_number' => '0987654321',
            'marital_status' => 'kawin',
            'nationality' => 'Indonesia',
            'religion' => 'Islam',
            'important_information' => 'Sedang Melajutkan pendidikan SI ( ilmu Komunikasi )',
            'last_education' => 'S1 Ilmu Komunikasi',
            'hire_date' => '2022-12-01',
            'employment_status' => 'kontrak',
            'photo' => $photo,
            'last_education_file' => $lastEduFile,
            'initial_education_institution' => 'S1 Ilmu Administrasi Negara',
            'initial_education_level' => 'S1',
            'initial_education_major' => 'Administrasi Negara',
            'initial_education_start_year' => '2004',
            'initial_education_end_year' => '2008',
            'initial_education_file' => $initialEduFile,
            'initial_document_label' => 'KTP',
            'initial_document_file' => $initialDocFile,
        ];

        $response = $this->post(route('admin.employees.store'), $payload);

        $employee = Employee::query()->where('employee_code', 'ATP001')->first();
        $this->assertNotNull($employee);
        $response->assertRedirect(route('admin.employees.show', $employee));

        $this->assertEquals('Wahyu Adi Kesuma', $employee->full_name);
        $this->assertEquals('Direktur', $employee->position);
        $this->assertEquals('kontrak', $employee->employment_status);
        $this->assertEquals('2171091305859009', $employee->identity_number);
        $this->assertNotNull($employee->photo_path);
        $this->assertNotNull($employee->last_education_file_path);

        // Verify educations
        $this->assertCount(1, $employee->educations);
        $edu = $employee->educations->first();
        $this->assertEquals('S1 Ilmu Administrasi Negara', $edu->institution_name);
        $this->assertNotNull($edu->file_path);

        // Verify documents
        $this->assertCount(1, $employee->documents);
        $doc = $employee->documents->first();
        $this->assertEquals('KTP', $doc->document_label);
        $this->assertNotNull($doc->file_path);
        $this->assertGreaterThan(0, $doc->file_size);
    }

    public function test_admin_can_create_employee_with_multiple_documents_and_educations(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $doc1 = UploadedFile::fake()->create('ktp.pdf', 50, 'application/pdf');
        $doc2 = UploadedFile::fake()->create('sertifikat-a.pdf', 100, 'application/pdf');
        $doc3 = UploadedFile::fake()->create('sertifikat-b.pdf', 120, 'application/pdf');

        $edu1 = UploadedFile::fake()->create('ijazah-smk.pdf', 70, 'application/pdf');
        $edu2 = UploadedFile::fake()->create('ijazah-s1.pdf', 90, 'application/pdf');

        $payload = [
            'full_name' => 'Bambang Sudirman',
            'employment_status' => 'tetap',
            'position' => 'Senior Welding Engineer',
            'last_education' => 'S1 Teknik Metalurgi',
            'documents' => [
                ['label' => 'KTP', 'file' => $doc1],
                ['label' => 'Sertifikat Welder 6G', 'file' => $doc2],
                ['label' => 'Sertifikat Welding Inspector', 'file' => $doc3],
            ],
            'educations' => [
                [
                    'institution_name' => 'SMK Negeri 2 Palembang',
                    'education_level' => 'SMK',
                    'major' => 'Teknik Pengelasan',
                    'start_year' => '2012',
                    'end_year' => '2015',
                    'file' => $edu1,
                ],
                [
                    'institution_name' => 'Universitas Indonesia',
                    'education_level' => 'S1',
                    'major' => 'Teknik Metalurgi & Material',
                    'start_year' => '2015',
                    'end_year' => '2019',
                    'file' => $edu2,
                ],
            ],
        ];

        $response = $this->post(route('admin.employees.store'), $payload);

        $employee = Employee::where('full_name', 'Bambang Sudirman')->first();
        $this->assertNotNull($employee);
        $response->assertRedirect(route('admin.employees.show', $employee));

        // Verify all 3 documents were uploaded and linked in one process
        $this->assertCount(3, $employee->documents);
        $labels = $employee->documents->pluck('document_label')->all();
        $this->assertContains('KTP', $labels);
        $this->assertContains('Sertifikat Welder 6G', $labels);
        $this->assertContains('Sertifikat Welding Inspector', $labels);

        // Verify all 2 educations were created in one process
        $this->assertCount(2, $employee->educations);
        $institutions = $employee->educations->pluck('institution_name')->all();
        $this->assertContains('SMK Negeri 2 Palembang', $institutions);
        $this->assertContains('Universitas Indonesia', $institutions);
    }

    public function test_admin_can_view_employee_details(): void
    {
        $this->actingAs($this->admin);

        $employee = Employee::query()->create([
            'employee_code' => 'GGI-002',
            'full_name' => 'Budi Santoso',
            'gender' => 'laki-laki',
            'position' => 'Senior Welder 6G',
            'employment_status' => 'tetap',
            'phone' => '08123456789',
        ]);

        $response = $this->get(route('admin.employees.show', $employee));
        $response->assertOk();
        $response->assertSee('Budi Santoso');
        $response->assertSee('Senior Welder 6G');
        $response->assertSee('GGI-002');
        $response->assertSee('Tetap');
    }

    public function test_admin_can_update_employee(): void
    {
        $this->actingAs($this->admin);

        $employee = Employee::query()->create([
            'employee_code' => 'GGI-003',
            'full_name' => 'Rina Wijaya',
            'gender' => 'perempuan',
            'position' => 'Staff Admin',
            'employment_status' => 'kontrak',
        ]);

        $response = $this->put(route('admin.employees.update', $employee), [
            'employee_code' => 'GGI-003',
            'full_name' => 'Rina Wijaya, S.Kom',
            'gender' => 'perempuan',
            'position' => 'HR & Admin Manager',
            'employment_status' => 'tetap',
        ]);

        $response->assertRedirect(route('admin.employees.show', $employee));
        $employee->refresh();
        $this->assertEquals('Rina Wijaya, S.Kom', $employee->full_name);
        $this->assertEquals('HR & Admin Manager', $employee->position);
        $this->assertEquals('tetap', $employee->employment_status);
    }

    public function test_admin_can_upload_and_adjust_employee_photo_preserving_original(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $rawPhoto = UploadedFile::fake()->image('raw-original-camera.jpg', 1920, 1080);
        $croppedPhoto = UploadedFile::fake()->image('foto-karyawan-crop.jpg', 800, 800);

        $response = $this->post(route('admin.employees.store'), [
            'full_name' => 'Doni Kusuma',
            'employment_status' => 'tetap',
            'original_photo' => $rawPhoto,
            'photo' => $croppedPhoto,
        ]);

        $employee = Employee::where('full_name', 'Doni Kusuma')->first();
        $this->assertNotNull($employee);
        $response->assertRedirect(route('admin.employees.show', $employee));

        $this->assertNotNull($employee->photo_path);
        $this->assertNotNull($employee->original_photo_path);
        $this->assertNotEquals($employee->photo_path, $employee->original_photo_path);

        $this->assertNotNull($employee->photoUrl());
        $this->assertNotNull($employee->originalPhotoUrl());
    }

    public function test_admin_can_add_and_delete_education(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $employee = Employee::query()->create([
            'employee_code' => 'GGI-004',
            'full_name' => 'Andi Rusli',
            'employment_status' => 'tetap',
        ]);

        $file = UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf');

        $response = $this->post(route('admin.employees.educations.store', $employee), [
            'institution_name' => 'Politeknik Negeri Batam',
            'education_level' => 'D3',
            'major' => 'Teknik Mesin',
            'start_year' => '2015',
            'end_year' => '2018',
            'grade' => '3.65',
            'education_file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertCount(1, $employee->educations);
        $edu = $employee->educations()->first();
        $this->assertEquals('Politeknik Negeri Batam', $edu->institution_name);

        // Delete education
        $delResponse = $this->delete(route('admin.employees.educations.destroy', [$employee, $edu]));
        $delResponse->assertRedirect();
        $this->assertCount(0, $employee->fresh()->educations);
    }

    public function test_admin_can_upload_and_delete_document(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $employee = Employee::query()->create([
            'employee_code' => 'GGI-005',
            'full_name' => 'Eko Prasetyo',
            'employment_status' => 'kontrak',
        ]);

        $file = UploadedFile::fake()->create('sertifikat-6g.pdf', 200, 'application/pdf');

        $response = $this->post(route('admin.employees.documents.store', $employee), [
            'document_label' => 'Sertifikat Welder 6G BNSP',
            'document_file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertCount(1, $employee->documents);
        $doc = $employee->documents()->first();
        $this->assertEquals('Sertifikat Welder 6G BNSP', $doc->document_label);
        $this->assertNotNull($doc->file_path);

        // Delete document
        $delResponse = $this->delete(route('admin.employees.documents.destroy', [$employee, $doc]));
        $delResponse->assertRedirect();
        $this->assertCount(0, $employee->fresh()->documents);
    }

    public function test_admin_can_preview_and_download_document(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $employee = Employee::query()->create([
            'employee_code' => 'GGI-006',
            'full_name' => 'Citra Dewi',
            'employment_status' => 'tetap',
        ]);

        $file = UploadedFile::fake()->create('skck.pdf', 100, 'application/pdf');
        $filePath = 'storage/'.$file->store('employees/documents', 'public');

        $doc = $employee->documents()->create([
            'document_label' => 'SKCK',
            'file_path' => $filePath,
            'file_name' => 'skck.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 102400,
        ]);

        // Preview
        $previewResponse = $this->get(route('admin.employees.documents.preview', [$employee, $doc]));
        $previewResponse->assertOk();
        $this->assertStringContainsString('application/pdf', $previewResponse->headers->get('Content-Type'));

        // Download
        $downloadResponse = $this->get(route('admin.employees.documents.download', [$employee, $doc]));
        $downloadResponse->assertOk();
        $this->assertStringContainsString('attachment', (string) $downloadResponse->headers->get('Content-Disposition'));
    }

    public function test_admin_can_delete_employee_and_cascade_cleanup(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $photo = UploadedFile::fake()->image('avatar.jpg');
        $photoPath = 'storage/'.$photo->store('employees/photos', 'public');

        $employee = Employee::query()->create([
            'employee_code' => 'GGI-007',
            'full_name' => 'Fajar Nugraha',
            'employment_status' => 'tetap',
            'photo_path' => $photoPath,
        ]);

        $employee->educations()->create([
            'institution_name' => 'SMK Negeri 1',
        ]);

        $employee->documents()->create([
            'document_label' => 'KTP',
            'file_path' => 'storage/dummy.pdf',
            'file_name' => 'dummy.pdf',
        ]);

        $response = $this->delete(route('admin.employees.destroy', $employee));
        $response->assertRedirect(route('admin.employees.index'));

        $this->assertNull(Employee::find($employee->id));
        $this->assertEquals(0, EmployeeEducation::where('employee_id', $employee->id)->count());
        $this->assertEquals(0, EmployeeDocument::where('employee_id', $employee->id)->count());
    }

    public function test_admin_can_manage_master_employee_positions(): void
    {
        $this->actingAs($this->admin);

        // Index
        $indexResponse = $this->get(route('admin.employee-positions.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Master Jabatan / Posisi');

        // Store
        $storeResponse = $this->post(route('admin.employee-positions.store'), [
            'name' => 'Welding QC Specialist',
            'code' => 'WQC',
            'description' => 'Quality Control Pengelasan',
            'is_active' => '1',
            'display_order' => 10,
        ]);
        $storeResponse->assertRedirect(route('admin.employee-positions.index'));

        $position = \App\Models\EmployeePosition::where('name', 'Welding QC Specialist')->first();
        $this->assertNotNull($position);
        $this->assertEquals('WQC', $position->code);
        $this->assertTrue($position->is_active);

        // Update
        $updateResponse = $this->put(route('admin.employee-positions.update', $position), [
            'name' => 'Lead Welding QC Specialist',
            'code' => 'LWQC',
            'description' => 'Koordinator QC Pengelasan',
            'is_active' => '1',
            'display_order' => 5,
        ]);
        $updateResponse->assertRedirect(route('admin.employee-positions.index'));
        $this->assertEquals('Lead Welding QC Specialist', $position->fresh()->name);

        // Destroy
        $destroyResponse = $this->delete(route('admin.employee-positions.destroy', $position));
        $destroyResponse->assertRedirect(route('admin.employee-positions.index'));
        $this->assertNull(\App\Models\EmployeePosition::where('name', 'Lead Welding QC Specialist')->first());
    }

    public function test_employee_creation_and_update_with_master_position(): void
    {
        $this->actingAs($this->admin);

        $pos = \App\Models\EmployeePosition::firstOrCreate(
            ['name' => 'Instruktur Welder Khusus'],
            ['code' => 'IWK', 'is_active' => true]
        );

        $response = $this->post(route('admin.employees.store'), [
            'full_name' => 'Agus Hartono',
            'employment_status' => 'tetap',
            'position' => 'Instruktur Welder Khusus',
        ]);

        $employee = Employee::where('full_name', 'Agus Hartono')->first();
        $this->assertNotNull($employee);
        $this->assertEquals('Instruktur Welder Khusus', $employee->position);
        $this->assertEquals($pos->id, $employee->position_id);
    }

    public function test_unauthorized_user_cannot_access_employee_management(): void
    {
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant);

        $this->get(route('admin.employees.index'))->assertForbidden();
        $this->get(route('admin.employee-positions.index'))->assertForbidden();
        $this->get(route('admin.employees.create'))->assertForbidden();
    }
}
