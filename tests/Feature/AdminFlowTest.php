<?php

namespace Tests\Feature;

use App\Models\TrainingApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Notifications\ApplicationStatusUpdatedNotification;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_admin_roles_permissions_programs_and_batches(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()
            ->where('email', config('admin.seed.email'))
            ->firstOrFail();

        $this->assertTrue($admin->hasRole('super-admin'));
        $this->assertTrue($admin->can('roles.manage'));
        $this->assertTrue(Hash::check((string) config('admin.seed.password'), $admin->password));
        $this->assertNotNull($admin->email_verified_at);
        $this->assertDatabaseHas('training_programs', ['code' => 'SMAW-3G']);
        $this->assertDatabaseHas('training_batches', ['code' => 'SMAW-2608']);
    }

    public function test_rerunning_admin_seeder_does_not_reset_existing_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()
            ->where('email', config('admin.seed.email'))
            ->firstOrFail();
        $admin->update(['password' => 'ChangedAdminPassword123']);

        config()->set('admin.seed.password', 'AnotherSeedPassword123');
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('ChangedAdminPassword123', $admin->fresh()->password));
        $this->assertFalse(Hash::check('AnotherSeedPassword123', $admin->fresh()->password));
    }

    public function test_participant_cannot_open_admin_area_but_admin_can(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Approval Pendaftaran');
    }

    public function test_admin_dashboard_uses_shared_ui_components_and_collapsible_sidebar(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $adminStyles = file_get_contents(public_path('templates/welding-school/admin.css'));

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->withSession(['success' => 'Data berhasil disimpan.'])
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->assertSee('ui-table-shell', false)
            ->assertSee('id="create-user"', false)
            ->assertSee('ui-dialog', false)
            ->assertSee('ui-icon', false)
            ->assertSee('admin-action-button--view', false)
            ->assertSee('admin-action-button--edit', false)
            ->assertSee('admin-action-button--delete', false)
            ->assertSee('id="toast-stack"', false)
            ->assertSee('data-flash-toast', false)
            ->assertSee('data-toast="Data berhasil disimpan."', false)
            ->assertDontSee('ui-alert--success', false)
            ->assertSee('data-admin-sidebar-collapse', false)
            ->assertSee('data-label="User Management"', false)
            ->assertDontSee('Lihat tampilan peserta')
            ->assertSee('templates/welding-school/components.js');

        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('ui-table-shell', false)
            ->assertSeeInOrder(['Role', 'Jenis', 'Pengguna', 'Akses diberikan', 'Aksi'])
            ->assertSee('data-role-name="admin"', false)
            ->assertSee('data-role-name="super-admin"', false)
            ->assertDontSee('data-role-name="participant"', false)
            ->assertSee('Detail akses role')
            ->assertSee('data-modal-open="edit-role-', false)
            ->assertSee('admin-role-permission-scroll', false)
            ->assertSeeInOrder([
                'Akses internal',
                'Pengguna &amp; role',
                'Pendaftaran peserta',
                'Program &amp; pelatihan',
                'Aktivitas &amp; konten',
                'Aset &amp; inventaris',
            ], false)
            ->assertSee('data-permission-group="system"', false)
            ->assertSee('data-permission-group="assets"', false)
            ->assertSee('data-permission-group="employees"', false)
            ->assertSee('data-permission-group="storage"', false)
            ->assertSee('Melihat aktivitas')
            ->assertSee('Mengelola dan menerbitkan aktivitas')
            ->assertSee('value="activities.view"', false)
            ->assertSee('value="activities.manage"', false);

        $this->assertTrue($admin->can('activities.view'));
        $this->assertTrue($admin->can('activities.manage'));

        $this->assertIsString($adminStyles);
        $this->assertStringContainsString(
            '.admin-navigation a > span:first-child',
            $adminStyles,
        );
        $this->assertStringContainsString(
            '.admin-sidebar-collapsed .admin-brand > .admin-brand__copy',
            $adminStyles,
        );
        $this->assertStringContainsString(
            '--admin-sidebar-collapsed-width: 68px',
            $adminStyles,
        );
        $this->assertStringContainsString(
            '--admin-topbar-height: 60px',
            $adminStyles,
        );
    }

    public function test_super_admin_can_manage_users_roles_programs_and_batches(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $this->post(route('admin.roles.store'), [
            'name' => 'verifier',
            'permissions' => ['admin.access', 'applications.view', 'applications.approve'],
        ])->assertRedirect();

        $this->post(route('admin.users.store'), [
            'name' => 'Verifikator Satu',
            'email' => 'verifier@example.com',
            'password' => 'Welding123',
            'password_confirmation' => 'Welding123',
            'status' => 'active',
            'role' => 'verifier',
        ])->assertRedirect(route('admin.users.index'));

        $verifier = User::query()->where('email', 'verifier@example.com')->firstOrFail();
        $this->assertTrue($verifier->hasRole('verifier'));

        $this->post(route('admin.programs.store'), [
            'code' => 'smaw-4g',
            'title' => 'SMAW Welder 4G',
            'category' => 'Shielded Metal Arc Welding',
            'duration_hours' => 80,
            'price' => 3900000,
            'status' => 'active',
            'start_date' => '2026-10-01',
        ])->assertRedirect(route('admin.programs.index'));

        $program = TrainingProgram::query()->where('code', 'SMAW-4G')->firstOrFail();

        $this->post(route('admin.batches.store'), [
            'training_program_id' => $program->id,
            'code' => 'smaw4g-2610',
            'name' => 'Batch Oktober 2026',
            'registration_deadline' => '2026-09-25',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-12',
            'capacity' => 12,
            'status' => 'open',
        ])->assertRedirect(route('admin.batches.index'));

        $this->assertDatabaseHas('training_batches', [
            'training_program_id' => $program->id,
            'code' => 'SMAW4G-2610',
            'status' => 'open',
        ]);
    }

    public function test_super_admin_can_delete_unused_records_but_related_training_data_is_protected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $user = User::factory()->create();
        $user->assignRole('participant');

        $this->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        $role = Role::query()->create([
            'name' => 'temporary-reviewer',
            'guard_name' => 'web',
        ]);

        $this->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);

        $program = TrainingProgram::query()->create([
            'code' => 'DELETE-ME',
            'title' => 'Program Tanpa Relasi',
            'category' => 'Testing',
            'duration_hours' => 10,
            'price' => 100000,
            'status' => 'draft',
        ]);
        $batch = TrainingBatch::query()->create([
            'training_program_id' => $program->id,
            'code' => 'DELETE-2601',
            'name' => 'Batch Tanpa Relasi',
            'start_date' => now()->addMonth(),
            'capacity' => 10,
            'status' => 'draft',
        ]);

        $this->from(route('admin.programs.index'))
            ->delete(route('admin.programs.destroy', $program))
            ->assertRedirect(route('admin.programs.index'))
            ->assertSessionHasErrors('program');
        $this->assertDatabaseHas('training_programs', ['id' => $program->id]);

        $this->delete(route('admin.batches.destroy', $batch))
            ->assertRedirect(route('admin.batches.index'));
        $this->assertDatabaseMissing('training_batches', ['id' => $batch->id]);

        $this->delete(route('admin.programs.destroy', $program))
            ->assertRedirect(route('admin.programs.index'));
        $this->assertDatabaseMissing('training_programs', ['id' => $program->id]);
    }

    public function test_participant_submission_enters_admin_queue_and_can_be_approved(): void
    {
        Storage::fake('local');
        Notification::fake();
        $this->seed(RolePermissionSeeder::class);

        $participant = User::factory()->create([
            'username' => 'peserta.submission',
            'name' => 'Peserta Test',
        ]);
        $participant->assignRole('participant');
        $participant->participantProfile()->create([
            'phone' => '081234567890',
            'identity_type' => 'ktp',
            'identity_number' => '3671000000000003',
            'birth_place' => 'Cilegon',
            'birth_date' => '1998-03-10',
            'gender' => 'male',
            'address' => 'Jalan Industri Nomor 1',
            'city' => 'Cilegon',
            'province' => 'Banten',
            'last_education' => 'SMA/SMK',
            'emergency_contact_name' => 'Kontak Darurat',
            'emergency_contact_phone' => '081299999999',
        ]);
        $program = TrainingProgram::query()->create([
            'code' => 'TEST-3G',
            'title' => 'Test Welding 3G',
            'category' => 'Testing',
            'duration_hours' => 40,
            'price' => 1000000,
            'status' => 'active',
            'start_date' => now()->addMonth(),
        ]);
        $batch = TrainingBatch::query()->create([
            'training_program_id' => $program->id,
            'code' => 'TEST-2609',
            'name' => 'Batch Test',
            'registration_deadline' => now()->addWeek(),
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addDays(5),
            'capacity' => 10,
            'status' => 'open',
        ]);

        $this->actingAs($participant)
            ->post(route('applications.store'), [
                'training_program_id' => $program->id,
                'training_batch_id' => $batch->id,
                'full_name' => 'Peserta Test',
                'phone' => '081234567890',
                'birth_place' => 'Cilegon',
                'birth_date' => '1998-03-10',
                'address' => 'Jalan Industri Nomor 1',
                'city' => 'Cilegon',
                'education' => 'SMA/SMK',
                'experience' => 'Kurang dari 1 tahun',
                'emergency_name' => 'Kontak Darurat',
                'emergency_phone' => '081299999999',
                'documents' => [
                    'id' => UploadedFile::fake()->image('ktp.jpg'),
                    'photo' => UploadedFile::fake()->image('foto.jpg'),
                    'education' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('application.status', 'submitted');

        $application = TrainingApplication::query()->firstOrFail();
        $this->assertCount(3, $application->documents);
        $this->assertDatabaseHas('application_status_histories', [
            'training_application_id' => $application->id,
            'to_status' => 'submitted',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $photo = $application->documents()
            ->where('document_type', 'photo')
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee('admin-document-grid', false)
            ->assertSee('preview-document-'.$photo->id, false)
            ->assertSee(route('admin.applications.documents.preview', [$application, $photo]), false)
            ->assertSee(route('admin.applications.documents.download', [$application, $photo]), false)
            ->assertSee('id="approve-application"', false)
            ->assertSee('ui-confirmation', false);

        $this->get(route('admin.applications.documents.preview', [$application, $photo]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->get(route('admin.applications.documents.download', [$application, $photo]))
            ->assertOk()
            ->assertDownload('foto.jpg');

        $this->actingAs($participant)
            ->get(route('admin.applications.documents.preview', [$application, $photo]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.applications.review', $application), [
                'decision' => 'approved',
                'notes' => 'Data dan dokumen lengkap.',
            ])
            ->assertRedirect(route('admin.applications.show', $application));

        $this->assertDatabaseHas('training_applications', [
            'id' => $application->id,
            'status' => 'approved',
            'verified_by' => $admin->id,
        ]);
        Notification::assertSentTo($participant, ApplicationStatusUpdatedNotification::class);
    }

    public function test_rejection_requires_an_admin_note(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $participant = User::factory()->create();
        $participant->assignRole('participant');
        $program = TrainingProgram::query()->create([
            'code' => 'REVIEW',
            'title' => 'Program Review',
            'category' => 'Testing',
            'duration_hours' => 10,
            'price' => 100000,
            'status' => 'active',
        ]);
        $application = TrainingApplication::query()->create([
            'registration_number' => 'WS-REVIEW-001',
            'user_id' => $participant->id,
            'training_program_id' => $program->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.applications.review', $application), [
                'decision' => 'rejected',
                'notes' => '',
            ])
            ->assertSessionHasErrors('notes');

        $this->assertSame('submitted', $application->fresh()->status);
    }
}
