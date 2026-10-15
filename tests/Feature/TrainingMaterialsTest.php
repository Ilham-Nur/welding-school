<?php

namespace Tests\Feature;

use App\Models\BatchTrainingMaterial;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\TrainingApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingMaterialRevision;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\Training\BatchOperations;
use App\Services\Training\MaterialAccess;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrainingMaterialsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $trainer;

    private TrainingBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
        $this->trainer = User::factory()->create(['status' => 'active']);
        $this->trainer->assignRole('trainer');
        $program = TrainingProgram::create(['code' => 'FCAW', 'title' => 'FCAW Welder 3G', 'category' => 'Welding', 'duration_hours' => 40, 'price' => 5000000, 'status' => 'active']);
        $this->batch = TrainingBatch::create(['training_program_id' => $program->id, 'code' => 'FCAW-01', 'name' => 'FCAW September', 'start_date' => today(), 'end_date' => today()->addDays(7), 'capacity' => 15, 'status' => 'open']);
        $this->batch->assignments()->create(['user_id' => $this->trainer->id, 'role' => 'lead_trainer']);
    }

    private function material(): TrainingMaterialRevision
    {
        $this->actingAs($this->admin)->post(route('admin.training-materials.store'), $this->data())->assertRedirect();
        $revision = TrainingMaterialRevision::latest('id')->firstOrFail();
        foreach (['internal' => 'Panduan jawaban trainer', 'participant' => 'Modul peserta'] as $audience => $label) {
            $this->post(route('admin.training-materials.upload', $revision), ['label' => $label, 'audience' => $audience, 'file' => UploadedFile::fake()->createWithContent($audience.'.txt', 'Isi '.$label)])->assertRedirect();
        }

        return $revision;
    }

    private function data(): array
    {
        return ['code' => 'TR-'.Str::random(6), 'title' => 'Keselamatan dan praktik FCAW', 'kind' => 'practice', 'objectives' => 'Mengenali keselamatan dan urutan praktik.', 'topics' => 'Persiapan alat dan pekerjaan', 'method' => 'guided_practice', 'guidance' => 'PANDUAN RAHASIA TRAINER', 'duration_minutes' => 90, 'program_ids' => [$this->batch->training_program_id], 'change_notes' => 'Versi awal'];
    }

    private function makeSession()
    {
        $location = Location::create(['name' => 'Workshop A', 'is_active' => true]);

        return $this->batch->sessions()->create(['title' => 'Praktik FCAW hari pertama', 'type' => 'practice', 'trainer_id' => $this->trainer->id, 'location_id' => $location->id, 'starts_at' => today()->setTime(8, 0), 'ends_at' => today()->setTime(10, 0), 'status' => 'published']);
    }

    private function participant(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('participant');
        $application = TrainingApplication::create(['registration_number' => 'REG-'.Str::random(8), 'user_id' => $user->id, 'training_program_id' => $this->batch->training_program_id, 'training_batch_id' => $this->batch->id, 'status' => 'approved']);
        Invoice::create(['invoice_number' => 'INV-'.Str::random(8), 'training_application_id' => $application->id, 'user_id' => $user->id, 'subtotal' => 5000000, 'total_amount' => 5000000, 'discount_amount' => 0, 'currency' => 'IDR', 'status' => 'paid', 'issued_at' => now(), 'paid_at' => now()]);
        Enrollment::create(['enrollment_number' => 'ENR-'.Str::random(8), 'training_application_id' => $application->id, 'user_id' => $user->id, 'training_program_id' => $this->batch->training_program_id, 'training_batch_id' => $this->batch->id, 'status' => 'active', 'enrolled_at' => now()]);

        return $user;
    }

    private function attach(TrainingMaterialRevision $revision): BatchTrainingMaterial
    {
        $this->actingAs($this->admin)->post(route('admin.training-materials.publish', $revision))->assertRedirect();
        $this->post(route('admin.operations.materials.store', $this->batch), ['training_material_revision_id' => $revision->id])->assertRedirect();

        return $this->batch->materials()->latest('id')->firstOrFail();
    }

    private function settings($session, bool $visible = true): array
    {
        return ['batch_session_id' => $session->id, 'position' => 1, 'participant_visible' => $visible];
    }

    public function test_library_program_batch_and_participant_flow_with_all_pages(): void
    {
        $revision = $this->material();
        $this->post(route('admin.training-materials.publish', $revision))->assertRedirect();
        $program = $this->batch->trainingProgram;
        $plan = ['training_material_revision_id' => $revision->id, 'position' => 1, 'method' => 'demonstration', 'duration_minutes' => 120];
        $this->post(route('admin.training-materials.program.add', $program), $plan)->assertRedirect();
        $this->post(route('admin.operations.materials.import', $this->batch))->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.operations.materials.import', $this->batch))->assertSessionHasErrors('materials');
        $this->assertDatabaseCount('batch_training_materials', 1);
        $item = $this->batch->materials()->firstOrFail();
        $session = $this->makeSession();
        $this->actingAs($this->trainer)->put(route('admin.operations.materials.update', [$this->batch, $item]), $this->settings($session))->assertRedirect();
        $this->assertTrue(app(MaterialAccess::class)->readiness($this->batch->fresh())['done']);
        $this->actingAs($this->admin);
        $pages = [
            'library' => route('admin.training-materials.index'), 'create' => route('admin.training-materials.create'),
            'detail' => route('admin.training-materials.show', $revision), 'programs' => route('admin.training-materials.programs'),
            'program' => route('admin.training-materials.program', $program), 'batch' => route('admin.operations.show', [$this->batch, 'tab' => 'materials']),
            'batch-edit' => route('admin.operations.materials.edit', [$this->batch, $item]),
            'program-edit' => route('admin.training-materials.program.edit', [$program, $program->materialPlan()->firstOrFail()]),
            'checklist' => route('admin.operations.show', [$this->batch, 'tab' => 'checklist']), 'trainer' => route('admin.operations.materials.show', [$this->batch, $item]),
        ];
        foreach ($pages as $name => $url) {
            $response = $this->get($url)->assertOk();
            $this->capture($name, $response->getContent());
        }
        $file = $revision->files()->where('audience', 'participant')->firstOrFail();
        $this->get(route('admin.training-materials.file', [$revision, $file, 'preview' => 1]))->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $user = $this->participant();
        $response = $this->actingAs($user)->get(route('participant.materials.index', $this->batch))->assertOk()->assertSee('Modul peserta')->assertDontSee('PANDUAN RAHASIA TRAINER')->assertDontSee('Panduan jawaban trainer');
        $response->assertSee('data-participant-shell', false)->assertSee('Menu peserta')->assertSee($user->email)
            ->assertSee('training-access-tabs', false)->assertSee('participant_view=training-detail', false)
            ->assertSee('training_batch='.$this->batch->id, false)->assertSee('training_section=schedule', false)
            ->assertSee(route('participant.attendance.index', $this->batch), false)
            ->assertDontSee('class="site-header"', false)->assertDontSee('class="public-nav"', false)->assertDontSee('class="site-footer"', false);
        $this->capture('participant', $response->getContent());
        $this->get(route('participant.materials.file', [$this->batch, $item, $file]))->assertOk();
        $internal = $revision->files()->where('audience', 'internal')->firstOrFail();
        $this->get(route('participant.materials.file', [$this->batch, $item, $internal]))->assertNotFound();
    }

    public function test_batch_settings_use_pinned_material_method_and_duration(): void
    {
        $revision = $this->material();
        $item = $this->attach($revision);
        $session = $this->makeSession();
        // Old overrides must not appear in batch or participant views.
        $item->update(['method' => 'discussion', 'duration_minutes' => 999]);
        foreach ([route('admin.operations.materials.edit', [$this->batch, $item]), route('admin.operations.materials.show', [$this->batch, $item]), route('admin.operations.show', [$this->batch, 'tab' => 'materials'])] as $url) {
            $this->get($url)->assertOk()->assertSee('Praktik terbimbing')->assertSee('90 menit')
                ->assertDontSee('999 menit')->assertDontSee('name="method"', false)->assertDontSee('name="duration_minutes"', false);
        }
        $this->put(route('admin.operations.materials.update', [$this->batch, $item]), [
            ...$this->settings($session), 'position' => 3, 'method' => 'discussion', 'duration_minutes' => 999,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $saved = $item->fresh();
        $this->assertSame('guided_practice', $saved->method);
        $this->assertEquals(90, $saved->duration_minutes);
        $this->assertEquals(3, $saved->position);
        $this->assertEquals($session->id, $saved->batch_session_id);
        $this->assertTrue($saved->participant_visible);
        $participant = $this->participant();
        $this->actingAs($participant)->get(route('participant.materials.index', $this->batch))->assertOk()
            ->assertSee('Praktik terbimbing')->assertSee('90 menit')->assertDontSee('999 menit');
    }

    public function test_program_import_is_available_only_when_batch_materials_are_empty(): void
    {
        $url = route('admin.operations.show', [$this->batch, 'tab' => 'materials']);
        $this->actingAs($this->admin)->get($url)->assertOk()->assertSee('Ambil susunan program');
        $this->post(route('admin.operations.materials.import', $this->batch))->assertSessionHasErrors('materials');
        $this->assertSame(0, $this->batch->materials()->count());
        $revision = $this->material();
        $this->post(route('admin.training-materials.publish', $revision))->assertRedirect();
        $this->post(route('admin.training-materials.program.add', $this->batch->trainingProgram), [
            'training_material_revision_id' => $revision->id, 'position' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.operations.materials.import', $this->batch))->assertRedirect()->assertSessionHasNoErrors();
        $this->get($url)->assertOk()->assertDontSee('Ambil susunan program')->assertSee('Tambah materi dari library');
        $item = $this->batch->materials()->firstOrFail();
        $this->delete(route('admin.operations.materials.destroy', [$this->batch, $item]))->assertRedirect();
        $this->get($url)->assertOk()->assertSee('Ambil susunan program');
        $this->post(route('admin.operations.materials.import', $this->batch))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $this->batch->materials()->count());
        $this->postJson(route('admin.operations.materials.import', $this->batch))->assertUnprocessable()->assertJsonValidationErrors('materials');
        $item = $this->batch->materials()->firstOrFail();
        $this->delete(route('admin.operations.materials.destroy', [$this->batch, $item]))->assertRedirect();
        $this->post(route('admin.operations.materials.store', $this->batch), ['training_material_revision_id' => $revision->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $this->batch->materials()->count());
        $this->get($url)->assertOk()->assertDontSee('Ambil susunan program');
        $this->postJson(route('admin.operations.materials.import', $this->batch))->assertUnprocessable()->assertJsonValidationErrors('materials');
        $other = TrainingBatch::create(['training_program_id' => $this->batch->training_program_id, 'code' => 'IMPORT-NEW', 'name' => 'Batch baru', 'start_date' => today(), 'capacity' => 10, 'status' => 'draft']);
        $this->post(route('admin.operations.materials.import', $other))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $other->materials()->count());
    }

    public function test_program_plan_inherits_method_and_duration_from_selected_revision(): void
    {
        $revision = $this->material();
        $this->post(route('admin.training-materials.publish', $revision))->assertRedirect();
        $program = $this->batch->trainingProgram;
        $this->post(route('admin.training-materials.program.add', $program), [
            'training_material_revision_id' => $revision->id, 'position' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $item = $program->materialPlan()->firstOrFail();
        $this->assertSame($revision->method, $item->method);
        $this->assertEquals($revision->duration_minutes, $item->duration_minutes);
        $this->put(route('admin.training-materials.program.update', [$program, $item]), [
            'training_material_revision_id' => $revision->id, 'position' => 2,
            'method' => 'theory', 'duration_minutes' => 999,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($revision->method, $item->fresh()->method);
        $this->assertEquals($revision->duration_minutes, $item->fresh()->duration_minutes);
        foreach ([route('admin.training-materials.program', $program), route('admin.training-materials.program.edit', [$program, $item])] as $url) {
            $this->get($url)->assertOk()->assertDontSee('name="method"', false)->assertDontSee('name="duration_minutes"', false);
        }
        $this->post(route('admin.operations.materials.import', $this->batch))->assertRedirect();
        $batchItem = $this->batch->materials()->firstOrFail();
        $this->assertSame($revision->method, $batchItem->method);
        $this->assertEquals($revision->duration_minutes, $batchItem->duration_minutes);
    }

    public function test_published_revisions_are_immutable_and_new_revision_does_not_change_existing_batch(): void
    {
        $revision = $this->material();
        $item = $this->attach($revision);
        $file = $revision->files()->firstOrFail();
        $this->put(route('admin.training-materials.update', $revision), collect($this->data())->except('code')->all())->assertForbidden();
        $this->delete(route('admin.training-materials.remove-file', [$revision, $file]))->assertForbidden();
        $this->post(route('admin.training-materials.revise', $revision))->assertRedirect();
        $draft = TrainingMaterialRevision::latest('id')->firstOrFail();
        $this->assertEquals(2, $draft->number);
        $response = $this->get(route('admin.training-materials.edit', $draft))->assertOk();
        $this->capture('edit', $response->getContent());
        $this->put(route('admin.training-materials.update', $draft), collect($this->data())->except('code')->all() + [])->assertRedirect();
        $this->post(route('admin.training-materials.revise', $revision))->assertSessionHasErrors('revision');
        $this->post(route('admin.training-materials.publish', $draft))->assertRedirect();
        $this->assertSame('archived', $revision->fresh()->status);
        $this->assertSame($revision->id, $item->fresh()->training_material_revision_id);
        $this->get(route('admin.operations.materials.file', [$this->batch, $item, $file]))->assertOk();
        $this->post(route('admin.operations.materials.store', $this->batch), ['training_material_revision_id' => $revision->id])->assertSessionHasErrors('materials');
        $this->post(route('admin.training-materials.archive', $draft))->assertRedirect();
        $this->assertSame('archived', $draft->fresh()->status);
    }

    public function test_permissions_protect_library_and_batch_files_and_empty_parent_menu(): void
    {
        $revision = $this->material();
        $file = $revision->files()->firstOrFail();
        $this->actingAs($this->trainer)->get(route('admin.training-materials.show', $revision))->assertForbidden();
        $this->get(route('admin.training-materials.index'))->assertOk()->assertDontSee($revision->title)->assertSee('data-label="Training"', false)->assertDontSee('data-label="Quality Record"', false);
        $this->post(route('admin.training-materials.store'), $this->data())->assertForbidden();
        $this->post(route('admin.training-materials.publish', $revision))->assertForbidden();
        $item = $this->attach($revision);
        $this->actingAs($this->trainer)->get(route('admin.operations.materials.file', [$this->batch, $item, $file]))->assertOk();
        $this->batch->assignments()->delete();
        $this->get(route('admin.operations.materials.show', [$this->batch, $item]))->assertForbidden();
        $this->get(route('admin.operations.materials.file', [$this->batch, $item, $file]))->assertForbidden();
        $this->trainer->update(['status' => 'inactive']);
        $this->get(route('admin.training-materials.index'))->assertForbidden();
    }

    public function test_participant_access_is_revoked_for_unpublished_session_unpaid_cancelled_and_inactive(): void
    {
        $revision = $this->material();
        $item = $this->attach($revision);
        $session = $this->makeSession();
        $file = $revision->files()->where('audience', 'participant')->firstOrFail();
        $participant = $this->participant();
        $url = route('participant.materials.file', [$this->batch, $item, $file]);
        $this->actingAs($participant)->get($url)->assertNotFound();
        $this->actingAs($this->admin)->put(route('admin.operations.materials.update', [$this->batch, $item]), $this->settings($session))->assertRedirect();
        $this->actingAs($participant)->get($url)->assertOk();
        $session->update(['status' => 'cancelled']);
        $this->get($url)->assertNotFound();
        $session->update(['status' => 'published']);
        Invoice::where('user_id', $participant->id)->update(['status' => 'refunded']);
        $this->get($url)->assertForbidden();
        Invoice::where('user_id', $participant->id)->update(['status' => 'paid']);
        Enrollment::where('user_id', $participant->id)->update(['status' => 'cancelled']);
        $this->get($url)->assertForbidden();
        Enrollment::where('user_id', $participant->id)->update(['status' => 'active']);
        $this->batch->update(['operational_status' => 'cancelled']);
        $this->get($url)->assertForbidden();
        $this->batch->update(['operational_status' => 'running']);
        $participant->update(['status' => 'inactive']);
        $this->get($url)->assertForbidden();
        $stranger = User::factory()->create(['status' => 'active']);
        $this->actingAs($stranger)->get($url)->assertForbidden();
    }

    public function test_material_readiness_cannot_be_faked_and_foreign_session_or_file_is_rejected(): void
    {
        $revision = $this->material();
        $item = $this->attach($revision);
        $session = $this->makeSession();
        $this->batch->checklistItems()->update(['status' => 'completed']);
        $checks = app(BatchOperations::class)->readiness($this->batch->fresh());
        $this->assertFalse(collect($checks)->firstWhere('title', 'Materi pelatihan siap')['done']);
        $check = $this->batch->checklistItems()->where('key', 'materials')->firstOrFail();
        $this->put(route('admin.operations.checklist.update', [$this->batch, $check]), ['status' => 'completed', 'notes' => 'manual'])->assertSessionHasErrors('materials');
        $this->put(route('admin.operations.materials.update', [$this->batch, $item]), $this->settings($session))->assertRedirect();
        $other = TrainingBatch::create(['training_program_id' => $this->batch->training_program_id, 'code' => 'OTHER', 'name' => 'Other', 'start_date' => today(), 'capacity' => 10, 'status' => 'draft']);
        $foreign = $session->replicate();
        $foreign->training_batch_id = $other->id;
        $foreign->save();
        $this->put(route('admin.operations.materials.update', [$this->batch, $item]), $this->settings($foreign))->assertSessionHasErrors('batch_session_id');
        $this->get(route('admin.operations.materials.show', [$other, $item]))->assertNotFound();
        $this->batch->update(['operational_status' => 'ready']);
        $this->delete(route('admin.operations.materials.destroy', [$this->batch, $item]))->assertRedirect();
        $this->assertSame('preparation', $this->batch->fresh()->operational_status);
        $this->assertTrue($this->batch->fresh()->materials_managed);
        $this->assertFalse(app(MaterialAccess::class)->readiness($this->batch->fresh())['done']);
    }

    public function test_invalid_upload_and_publication_without_file_are_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.training-materials.store'), $this->data())->assertRedirect();
        $revision = TrainingMaterialRevision::firstOrFail();
        $this->post(route('admin.training-materials.publish', $revision))->assertSessionHasErrors('files');
        $this->post(route('admin.training-materials.upload', $revision), ['label' => 'unsafe', 'audience' => 'participant', 'file' => UploadedFile::fake()->createWithContent('bad.html', '<script>alert(1)</script>')])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('training_material_files', 0);
        $this->post(route('admin.operations.materials.store', $this->batch), ['training_material_revision_id' => $revision->id])->assertSessionHasErrors('materials');
    }

    public function test_batch_publication_requires_participant_file_and_closed_batch_rejects_mutation(): void
    {
        $revision = $this->material();
        $revision->files()->where('audience', 'participant')->delete();
        $item = $this->attach($revision);
        $session = $this->makeSession();
        $this->put(route('admin.operations.materials.update', [$this->batch, $item]), $this->settings($session))->assertSessionHasErrors('participant_visible');
        $this->put(route('admin.operations.materials.update', [$this->batch, $item]), $this->settings($session, false))->assertRedirect();
        Storage::disk('local')->delete($revision->files()->firstOrFail()->path);
        $this->assertFalse(app(MaterialAccess::class)->readiness($this->batch->fresh())['done']);
        $this->batch->update(['operational_status' => 'completed']);
        $this->put(route('admin.operations.materials.update', [$this->batch, $item]), $this->settings($session, false))->assertSessionHasErrors('batch');
        $this->delete(route('admin.operations.materials.destroy', [$this->batch, $item]))->assertSessionHasErrors('batch');
        $this->assertDatabaseHas('batch_training_materials', ['id' => $item->id]);
    }

    public function test_archived_program_import_is_atomic_and_participant_keeps_pinned_revision(): void
    {
        $revision = $this->material();
        $item = $this->attach($revision);
        $session = $this->makeSession();
        $this->put(route('admin.operations.materials.update', [$this->batch, $item]), $this->settings($session))->assertRedirect();
        $program = $this->batch->trainingProgram;
        $this->post(route('admin.training-materials.program.add', $program), ['training_material_revision_id' => $revision->id, 'position' => 1, 'method' => 'theory', 'duration_minutes' => 60])->assertRedirect();
        $this->post(route('admin.training-materials.archive', $revision))->assertRedirect();
        $other = TrainingBatch::create(['training_program_id' => $program->id, 'code' => 'NEW', 'name' => 'New batch', 'start_date' => today(), 'capacity' => 10, 'status' => 'draft']);
        $this->post(route('admin.operations.materials.import', $other))->assertSessionHasErrors('materials');
        $this->assertSame(0, $other->materials()->count());
        $this->assertFalse($other->fresh()->materials_managed);
        $participant = $this->participant();
        $file = $revision->files()->where('audience', 'participant')->firstOrFail();
        $this->actingAs($participant)->get(route('participant.materials.file', [$this->batch, $item, $file]))->assertOk();
    }

    private function capture(string $name, string $html): void
    {
        if (getenv('MATERIAL_CAPTURE_DIR')) {
            file_put_contents(getenv('MATERIAL_CAPTURE_DIR').'/'.$name.'.html', $html);
        }
    }

    public function test_publication_prerequisites_show_specific_fixes_before_a_failed_submission(): void
    {
        $revision = $this->material();
        $revision->files()->where('audience', 'participant')->delete();
        $item = $this->attach($revision);
        $this->get(route('admin.operations.show', [$this->batch, 'tab' => 'materials']))->assertOk()
            ->assertSee('Syarat belum lengkap')->assertSee('Pilih sesi untuk materi ini')->assertSee('Belum ada file berakses Peserta');
        $this->get(route('admin.operations.materials.edit', [$this->batch, $item]))->assertOk()
            ->assertSee('Syarat agar materi bisa dibagikan')->assertSee('data-material-publication', false)->assertSee('material-publication.js');
        $this->putJson(route('admin.operations.materials.update', [$this->batch, $item]), ['position' => 1, 'participant_visible' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('participant_visible');
        $this->assertFalse((bool) $item->fresh()->participant_visible);
    }

    public function test_batch_material_actions_use_consistent_buttons_and_preserve_visibility_rules(): void
    {
        $revision = $this->material();
        $item = $this->attach($revision);
        $file = $revision->files()->where('audience', 'participant')->firstOrFail();
        Storage::disk('local')->delete($file->path);
        $url = route('admin.operations.show', [$this->batch, 'tab' => 'materials']);

        $actions = function () use ($url): \DOMNodeList {
            $response = $this->get($url)->assertOk();
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());

            return (new \DOMXPath($document))->query('//div[@class="tm-batch-actions"]/a');
        };

        $buttons = $actions();
        $this->assertCount(3, $buttons);
        foreach ($buttons as $button) {
            $this->assertSame('button button--outline admin-button', $button->getAttribute('class'));
        }
        $this->assertSame(route('admin.operations.materials.show', [$this->batch, $item]), $buttons->item(0)->getAttribute('href'));
        $this->assertSame(route('admin.operations.materials.edit', [$this->batch, $item]), $buttons->item(1)->getAttribute('href'));
        $this->assertSame(route('admin.training-materials.show', $revision), $buttons->item(2)->getAttribute('href'));

        $this->batch->update(['operational_status' => 'completed']);
        $buttons = $actions();
        $this->assertCount(2, $buttons);
        $this->assertStringNotContainsString('Atur materi', $buttons->item(0)->textContent.$buttons->item(1)->textContent);

        Storage::disk('local')->put($file->path, 'Modul peserta');
        $this->assertCount(1, $actions());
    }

    public function test_missing_participant_file_is_explained_and_cannot_be_published(): void
    {
        $revision = $this->material();
        $file = $revision->files()->where('audience', 'participant')->firstOrFail();
        $item = $this->attach($revision);
        Storage::disk('local')->delete($file->path);
        $session = $this->makeSession();
        $this->putJson(route('admin.operations.materials.update', [$this->batch, $item]), $this->settings($session))
            ->assertUnprocessable()->assertJsonValidationErrors('participant_visible');
        $checks = app(MaterialAccess::class)->publicationChecks($item->fresh(), $session);
        $this->assertFalse(collect($checks)->firstWhere('key', 'available-files')['done']);
        $this->assertFalse((bool) $item->fresh()->participant_visible);
    }
}
