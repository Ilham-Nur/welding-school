<?php

namespace Tests\Feature;

use App\Models\BatchSession;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\TrainingApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BatchOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $trainer;

    private User $coordinator;

    private TrainingBatch $batch;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
        $this->trainer = User::factory()->create(['name' => 'Trainer Operasional', 'status' => 'active']);
        $this->trainer->assignRole('trainer');
        $this->coordinator = User::factory()->create(['name' => 'Penanggung Jawab Operasional', 'status' => 'active']);
        $this->coordinator->assignRole('penanggung-jawab');
        $this->batch = $this->makeBatch();
        $this->location = Location::create(['name' => 'Workshop A', 'is_active' => true, 'is_storage' => false]);
    }

    public function test_all_operational_pages_render_and_do_not_write_on_reads(): void
    {
        $this->actingAs($this->admin)->get(route('admin.operations.index'))->assertOk()->assertSee($this->batch->name);
        foreach (['overview', 'participants', 'team', 'schedule', 'checklist', 'history'] as $tab) {
            $this->get(route('admin.operations.show', ['batch' => $this->batch, 'tab' => $tab]))->assertOk()->assertSee('Operasional Batch');
        }
        $this->get(route('admin.operations.sessions.create', $this->batch))->assertOk();
        $this->assertDatabaseCount('batch_checklist_items', 3);
        $this->assertDatabaseCount('batch_activity_logs', 0);
    }

    public function test_batch_has_no_main_location_input_and_sessions_do_not_inherit_legacy_location(): void
    {
        // Preserve old data, but do not use it as an operational setting anymore.
        $this->batch->forceFill(['location_id' => $this->location->id])->save();
        $this->actingAs($this->admin)->get(route('admin.operations.index'))
            ->assertOk()->assertDontSee('<th>Lokasi</th>', false);
        $this->get(route('admin.operations.show', $this->batch))->assertOk()
            ->assertDontSee('Lokasi utama')->assertDontSee('name="location_id"', false)
            ->assertDontSee('Lokasi batch aktif ditentukan')->assertDontSee($this->location->name);
        $this->get(route('admin.operations.sessions.create', $this->batch))->assertOk()
            ->assertSee('Lokasi sesi (ruang / workshop)')
            ->assertViewHas('session', fn (BatchSession $session) => $session->location_id === null);
        $this->assertSame($this->location->id, $this->batch->fresh()->location_id);
    }

    public function test_operational_notes_are_removed_but_legacy_data_is_preserved(): void
    {
        $this->batch->forceFill(['operational_notes' => 'Catatan lama yang disimpan'])->save();
        $this->actingAs($this->admin)->get(route('admin.operations.show', $this->batch))
            ->assertOk()->assertDontSee('Catatan operasional')->assertDontSee('name="operational_notes"', false)
            ->assertDontSee('Catatan lama yang disimpan');
        $this->putJson(route('admin.operations.show', $this->batch), ['operational_notes' => 'Tidak boleh diubah'])
            ->assertStatus(405);
        $this->assertSame('Catatan lama yang disimpan', $this->batch->fresh()->operational_notes);
    }

    public function test_ready_running_and_reopening_do_not_require_a_reason_and_have_confirmation_dialogs(): void
    {
        $this->readyFixture();
        $route = route('admin.operations.status', $this->batch);
        $this->actingAs($this->admin)->get(route('admin.operations.show', $this->batch))->assertOk()
            ->assertSee('data-modal-open="confirm-batch-status-'.$this->batch->id.'-ready"', false)
            ->assertSee('Ubah status menjadi Siap Dilaksanakan?')
            ->assertDontSee('data-ops-status-reason', false);
        $this->patch($route, ['operational_status' => 'ready'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('ready', $this->batch->fresh()->operational_status);
        $this->get(route('admin.operations.show', $this->batch))->assertOk()->assertSee('Ubah status menjadi Berjalan?');
        $this->patch($route, ['operational_status' => 'running', 'reason' => ''])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('running', $this->batch->fresh()->operational_status);
        $this->patch($route, ['operational_status' => 'preparation'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('preparation', $this->batch->fresh()->operational_status);
        $this->assertSame(3, $this->batch->activityLogs()->where('action', 'status.changed')->whereNull('reason')->count());
    }

    public function test_postponement_and_cancellation_require_reasons_and_keep_them_in_history(): void
    {
        $this->actingAs($this->admin);
        $route = route('admin.operations.status', $this->batch);
        foreach (['postponed', 'cancelled'] as $target) {
            foreach ([null, '', '   '] as $reason) {
                $this->patchJson($route, ['operational_status' => $target, 'reason' => $reason])
                    ->assertUnprocessable()->assertJsonValidationErrors('reason');
            }
            $this->patch($route, ['operational_status' => $target, 'reason' => 'Keputusan penyelenggara'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($target, $this->batch->fresh()->operational_status);
        }
        $this->assertSame(2, $this->batch->activityLogs()->where('action', 'status.changed')->where('reason', 'Keputusan penyelenggara')->count());
    }

    public function test_destructive_operational_actions_collect_reasons_in_targeted_modals(): void
    {
        $this->assignTeam();
        $session = $this->batch->sessions()->create($this->sessionData());
        $otherSession = $this->batch->sessions()->create($this->sessionData(['title' => 'Sesi berikutnya', 'status' => 'draft']));
        $assignment = $this->batch->assignments()->where('role', 'lead_trainer')->firstOrFail();
        $cases = [
            ['schedule', 'cancel-session-'.$session->id, route('admin.operations.sessions.cancel', [$this->batch, $session]), 'PATCH'],
            ['schedule', 'cancel-session-'.$otherSession->id, route('admin.operations.sessions.cancel', [$this->batch, $otherSession]), 'PATCH'],
            ['team', 'end-assignment-'.$assignment->id, route('admin.operations.team.destroy', [$this->batch, $assignment]), 'DELETE'],
            ['overview', 'confirm-batch-status-'.$this->batch->id.'-cancelled', route('admin.operations.status', $this->batch), 'PATCH'],
            ['overview', 'confirm-batch-status-'.$this->batch->id.'-postponed', route('admin.operations.status', $this->batch), 'PATCH'],
        ];
        $this->actingAs($this->admin);
        foreach ($cases as [$tab, $id, $action, $method]) {
            $response = $this->get(route('admin.operations.show', [$this->batch, 'tab' => $tab]))->assertOk();
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $this->assertCount(1, $xpath->query('//dialog[@id="'.$id.'"]//form[@action="'.$action.'"]'));
            $this->assertCount(1, $xpath->query('//dialog[@id="'.$id.'"]//textarea[@name="reason"][@required][@maxlength="1000"]'));
            $this->assertCount(1, $xpath->query('//dialog[@id="'.$id.'"]//input[@name="_method"][@value="'.$method.'"]'));
            $this->assertCount(1, $xpath->query('//dialog[@id="'.$id.'"]//button[@type="submit"][@form="'.$id.'-form"]'));
            $this->assertCount(0, $xpath->query('//details//textarea[@name="reason"] | //details//input[@name="reason"]'));
            if ($tab !== 'overview') {
                $response->assertSee('data-modal-open="'.$id.'"', false);
            }
        }
        $this->assertDatabaseCount('batch_activity_logs', 0);
        $session->update(['execution_status' => 'running']);
        $this->get(route('admin.operations.show', [$this->batch, 'tab' => 'schedule']))->assertOk()
            ->assertDontSee('id="cancel-session-'.$session->id.'"', false);
        $otherSession->update(['status' => 'cancelled']);
        $this->get(route('admin.operations.show', [$this->batch, 'tab' => 'schedule']))->assertOk()
            ->assertDontSee('id="cancel-session-'.$otherSession->id.'"', false);
        $this->actingAs($this->trainer)->get(route('admin.operations.show', [$this->batch, 'tab' => 'team']))->assertOk()
            ->assertDontSee('data-modal-open="end-assignment-', false);
        $this->batch->update(['operational_status' => 'completed']);
        $this->actingAs($this->admin)->get(route('admin.operations.show', [$this->batch, 'tab' => 'team']))->assertOk()
            ->assertDontSee('data-modal-open="end-assignment-', false);
    }

    public function test_failed_cancellation_keeps_reason_only_in_its_own_modal(): void
    {
        $this->assignTeam();
        $session = $this->batch->sessions()->create($this->sessionData());
        $other = $this->batch->sessions()->create($this->sessionData(['title' => 'Sesi lain', 'status' => 'draft']));
        $response = $this->actingAs($this->admin)->withSession(['_old_input' => [
            '_action_modal' => 'cancel-session-'.$session->id, 'reason' => 'Alasan khusus sesi pertama',
        ]])->get(route('admin.operations.show', [$this->batch, 'tab' => 'schedule']))->assertOk()
            ->assertSee('Alasan khusus sesi pertama');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame('Alasan khusus sesi pertama', trim($xpath->query('//dialog[@id="cancel-session-'.$session->id.'"]//textarea')->item(0)->textContent));
        $reason = $xpath->query('//dialog[@id="cancel-session-'.$other->id.'"]//textarea')->item(0);
        $this->assertSame('', trim($reason->textContent));
    }

    public function test_session_cancellation_requires_a_valid_reason_and_records_it_once(): void
    {
        $this->assignTeam();
        $session = $this->batch->sessions()->create($this->sessionData());
        $url = route('admin.operations.sessions.cancel', [$this->batch, $session]);
        $this->actingAs($this->admin);
        foreach ([null, '', '   ', str_repeat('a', 1001)] as $reason) {
            $this->patchJson($url, ['reason' => $reason])->assertUnprocessable()->assertJsonValidationErrors('reason');
            $this->assertSame('published', $session->fresh()->status);
        }
        $this->assertSame(0, $this->batch->activityLogs()->where('action', 'session.cancelled')->count());
        $this->patch($url, ['_action_modal' => 'cancel-session-'.$session->id, 'reason' => 'Trainer berhalangan hadir'])
            ->assertRedirect(route('admin.operations.show', [$this->batch, 'tab' => 'schedule']))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $session->fresh()->status);
        $this->assertSame(1, $this->batch->activityLogs()->where('action', 'session.cancelled')->where('reason', 'Trainer berhalangan hadir')->count());
    }

    public function test_session_location_is_required_and_inactive_session_location_blocks_readiness(): void
    {
        $this->readyFixture();
        $this->assertNull($this->batch->fresh()->location_id);
        $this->actingAs($this->admin)->get(route('admin.operations.show', $this->batch))
            ->assertOk()->assertViewHas('readiness', fn (array $checks) => count($checks) === 7 && collect($checks)->every('done'));
        $this->postJson(route('admin.operations.sessions.store', $this->batch), $this->sessionData(['location_id' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('location_id');
        $this->location->update(['is_active' => false]);
        $this->patchJson(route('admin.operations.status', $this->batch), ['operational_status' => 'ready', 'reason' => 'Periksa kesiapan'])
            ->assertUnprocessable()->assertJsonValidationErrors('operational_status');
        $this->location->update(['is_active' => true]);
        $this->patch(route('admin.operations.status', $this->batch), ['operational_status' => 'ready', 'reason' => 'Lokasi sesi aktif'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('ready', $this->batch->fresh()->operational_status);
    }

    public function test_participant_and_unassigned_staff_cannot_access_batch(): void
    {
        $participant = User::factory()->create();
        $participant->assignRole('participant');
        $this->actingAs($participant)->get(route('admin.operations.index'))->assertForbidden();
        $this->actingAs($this->trainer)->get(route('admin.operations.show', $this->batch))->assertForbidden();
        $this->get(route('admin.operations.index'))->assertOk()->assertDontSee($this->batch->name);
        $this->batch->assignments()->create(['user_id' => $this->trainer->id, 'role' => 'lead_trainer']);
        $this->get(route('admin.operations.index'))->assertOk()->assertSee($this->batch->name);
        $this->get(route('admin.operations.show', $this->batch))->assertOk();
        $this->patchJson(route('admin.operations.status', $this->batch), ['operational_status' => 'cancelled', 'reason' => 'Test'])->assertForbidden();
        $this->trainer->update(['status' => 'inactive']);
        $this->get(route('admin.operations.show', $this->batch))->assertForbidden();
    }

    public function test_active_paid_roster_excludes_cancelled_and_refunded_enrollments_and_other_batches(): void
    {
        $active = $this->enroll($this->batch, 'paid', 'active', 'Peserta Aktif Lunas');
        $this->enroll($this->batch, 'refunded', 'active', 'Peserta Refund');
        $this->enroll($this->batch, 'paid', 'cancelled', 'Peserta Batal');
        $this->enroll($this->makeBatch(), 'paid', 'active', 'Peserta Batch Lain');
        $this->assertSame(1, $this->batch->activePaidEnrollments()->count());
        $this->actingAs($this->admin)->get(route('admin.operations.show', ['batch' => $this->batch, 'tab' => 'participants']))
            ->assertOk()->assertSee($active->enrollment_number)->assertSee('Peserta Refund')->assertSee('Dikembalikan')->assertDontSee('Peserta Batch Lain');
    }

    public function test_team_assignment_requires_eligible_staff_and_unique_lead_and_logs_changes(): void
    {
        $route = route('admin.operations.team.store', $this->batch);
        $this->actingAs($this->admin)->post($route, ['user_id' => $this->trainer->id, 'role' => 'lead_trainer', 'reason' => 'Penugasan awal'])->assertRedirect();
        $this->assertDatabaseHas('batch_assignments', ['training_batch_id' => $this->batch->id, 'user_id' => $this->trainer->id, 'role' => 'lead_trainer']);
        $this->assertDatabaseHas('batch_activity_logs', ['actor_id' => $this->admin->id, 'action' => 'team.assigned', 'reason' => 'Penugasan awal']);
        $this->postJson($route, ['user_id' => $this->coordinator->id, 'role' => 'lead_trainer', 'reason' => 'Duplikat'])->assertUnprocessable()->assertJsonValidationErrors('role');
        $participant = User::factory()->create();
        $this->postJson($route, ['user_id' => $participant->id, 'role' => 'assistant_trainer', 'reason' => 'Tidak valid'])->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->coordinator->update(['status' => 'inactive']);
        $this->postJson($route, ['user_id' => $this->coordinator->id, 'role' => 'coordinator', 'reason' => 'Tidak aktif'])->assertUnprocessable();
    }

    public function test_assignment_reason_is_optional_for_create_and_update_but_history_is_kept(): void
    {
        $route = route('admin.operations.team.store', $this->batch);
        $this->actingAs($this->admin)->post($route, ['user_id' => $this->trainer->id, 'role' => 'lead_trainer'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post($route, ['user_id' => $this->coordinator->id, 'role' => 'coordinator', 'reason' => ''])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post($route, ['user_id' => $this->trainer->id, 'role' => 'assistant_trainer', 'reason' => null])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('batch_assignments', 2);
        $this->assertDatabaseHas('batch_assignments', ['training_batch_id' => $this->batch->id, 'user_id' => $this->trainer->id, 'role' => 'assistant_trainer']);
        $this->assertSame(3, $this->batch->activityLogs()->where('action', 'team.assigned')->where('actor_id', $this->admin->id)->whereNull('reason')->count());
        $this->postJson($route, ['user_id' => $this->trainer->id, 'role' => 'assistant_trainer', 'reason' => str_repeat('a', 1001)])
            ->assertUnprocessable()->assertJsonValidationErrors('reason');
        $assignment = $this->batch->assignments()->where('user_id', $this->trainer->id)->firstOrFail();
        $this->deleteJson(route('admin.operations.team.destroy', [$this->batch, $assignment]))
            ->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->get(route('admin.operations.show', ['batch' => $this->batch, 'tab' => 'team']))
            ->assertOk()->assertSee('Alasan / catatan penugasan (opsional)');
    }

    public function test_assigned_trainer_can_publish_session_but_not_manage_team(): void
    {
        $this->assignTeam();
        $this->actingAs($this->trainer)->post(route('admin.operations.sessions.store', $this->batch), $this->sessionData())->assertRedirect();
        $this->assertDatabaseHas('batch_sessions', ['training_batch_id' => $this->batch->id, 'status' => 'published']);
        $this->postJson(route('admin.operations.team.store', $this->batch), ['user_id' => $this->coordinator->id, 'role' => 'coordinator', 'reason' => 'Tidak boleh'])->assertForbidden();
    }

    public function test_session_overlap_checks_trainer_location_and_batch_and_allows_adjacent_sessions(): void
    {
        $this->assignTeam();
        $this->actingAs($this->admin)->post(route('admin.operations.sessions.store', $this->batch), $this->sessionData())->assertRedirect();
        $other = $this->makeBatch();
        $other->assignments()->create(['user_id' => $this->trainer->id, 'role' => 'lead_trainer']);
        $secondRoom = Location::create(['name' => 'Workshop B', 'is_active' => true]);
        $this->postJson(route('admin.operations.sessions.store', $other), $this->sessionData(['location_id' => $secondRoom->id]))->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $other->assignments()->create(['user_id' => $this->coordinator->id, 'role' => 'assistant_trainer']);
        $this->postJson(route('admin.operations.sessions.store', $other), $this->sessionData(['trainer_id' => $this->coordinator->id]))->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->batch->assignments()->where('user_id', $this->coordinator->id)->update(['role' => 'assistant_trainer']);
        $this->postJson(route('admin.operations.sessions.store', $this->batch), $this->sessionData(['trainer_id' => $this->coordinator->id, 'location_id' => $secondRoom->id]))->assertUnprocessable();
        $this->post(route('admin.operations.sessions.store', $this->batch), $this->sessionData(['starts_at' => today()->format('Y-m-d').'T10:00', 'ends_at' => today()->format('Y-m-d').'T11:00']))->assertRedirect();
        $this->assertDatabaseCount('batch_sessions', 2);
    }

    public function test_draft_can_overlap_but_publication_cannot_and_session_must_be_within_batch_dates(): void
    {
        $this->assignTeam();
        $this->actingAs($this->admin)->post(route('admin.operations.sessions.store', $this->batch), $this->sessionData())->assertRedirect();
        $this->post(route('admin.operations.sessions.store', $this->batch), $this->sessionData(['status' => 'draft']))->assertRedirect();
        $draft = BatchSession::latest('id')->first();
        $this->putJson(route('admin.operations.sessions.update', [$this->batch, $draft]), $this->sessionData(['reason' => 'Publikasi']))->assertUnprocessable();
        $this->postJson(route('admin.operations.sessions.store', $this->batch), $this->sessionData(['starts_at' => today()->subDay()->format('Y-m-d').'T08:00', 'ends_at' => today()->subDay()->format('Y-m-d').'T10:00']))->assertUnprocessable();
        $this->postJson(route('admin.operations.sessions.store', $this->batch), $this->sessionData(['ends_at' => today()->format('Y-m-d').'T07:00']))->assertUnprocessable();
    }

    public function test_children_from_another_batch_cannot_be_modified(): void
    {
        $other = $this->makeBatch();
        $item = $other->checklistItems()->first();
        $assignment = $other->assignments()->create(['user_id' => $this->trainer->id, 'role' => 'lead_trainer']);
        $session = $other->sessions()->create($this->sessionData());
        $this->actingAs($this->admin)->putJson(route('admin.operations.checklist.update', [$this->batch, $item]), ['status' => 'completed', 'notes' => 'Test'])->assertNotFound();
        $this->deleteJson(route('admin.operations.team.destroy', [$this->batch, $assignment]), ['reason' => 'Test'])->assertNotFound();
        $this->putJson(route('admin.operations.sessions.update', [$this->batch, $session]), $this->sessionData(['reason' => 'Test']))->assertNotFound();
        $this->patchJson(route('admin.operations.sessions.cancel', [$this->batch, $session]), ['reason' => 'Test'])->assertNotFound();
    }

    public function test_readiness_and_status_transitions_require_real_data_and_no_skips(): void
    {
        $route = route('admin.operations.status', $this->batch);
        $this->actingAs($this->admin)->patchJson($route, ['operational_status' => 'running', 'reason' => 'Lompat'])->assertUnprocessable();
        $this->patchJson($route, ['operational_status' => 'ready', 'reason' => 'Belum lengkap'])->assertUnprocessable();
        $this->readyFixture();
        $this->patch($route, ['operational_status' => 'ready', 'reason' => 'Siap diperiksa'])->assertRedirect();
        $this->assertSame('ready', $this->batch->fresh()->operational_status);
        $this->patch($route, ['operational_status' => 'running', 'reason' => 'Pelatihan dimulai'])->assertRedirect();
        $this->assertSame('running', $this->batch->fresh()->operational_status);
        $this->assertSame('closed', $this->batch->fresh()->status);
        $this->patchJson($route, ['operational_status' => 'completed', 'reason' => 'Lompat'])->assertUnprocessable();
        $this->assertDatabaseHas('batch_activity_logs', ['action' => 'status.changed', 'reason' => 'Pelatihan dimulai']);
    }

    public function test_refund_or_inactive_trainer_after_ready_blocks_start(): void
    {
        $this->readyFixture();
        $this->batch->update(['operational_status' => 'ready']);
        Invoice::query()->update(['status' => 'refunded']);
        $this->actingAs($this->admin)->patchJson(route('admin.operations.status', $this->batch), ['operational_status' => 'running', 'reason' => 'Mulai'])->assertUnprocessable();
        Invoice::query()->update(['status' => 'paid']);
        $this->trainer->update(['status' => 'inactive']);
        $this->patchJson(route('admin.operations.status', $this->batch), ['operational_status' => 'running', 'reason' => 'Mulai'])->assertUnprocessable();
    }

    public function test_start_before_batch_date_is_rejected(): void
    {
        $this->readyFixture();
        $this->batch->update(['start_date' => today()->addDay(), 'operational_status' => 'ready']);
        $this->batch->sessions()->update(['starts_at' => today()->addDay()->setTime(8, 0), 'ends_at' => today()->addDay()->setTime(10, 0)]);
        $this->actingAs($this->admin)->patchJson(route('admin.operations.status', $this->batch), ['operational_status' => 'running', 'reason' => 'Terlalu awal'])->assertUnprocessable();
    }

    public function test_checklist_completion_needs_notes_and_pic_must_be_assigned(): void
    {
        $this->assignTeam();
        $item = $this->batch->checklistItems()->first();
        $route = route('admin.operations.checklist.update', [$this->batch, $item]);
        $this->actingAs($this->trainer)->putJson($route, ['status' => 'completed'])->assertUnprocessable()->assertJsonValidationErrors('notes');
        $this->putJson($route, ['status' => 'completed', 'notes' => 'Sudah dicek', 'assigned_to' => $this->admin->id])->assertUnprocessable();
        $this->put($route, ['status' => 'completed', 'notes' => 'Sudah dicek', 'assigned_to' => $this->trainer->id])->assertRedirect();
        $this->assertSame($this->trainer->id, $item->fresh()->completed_by);
        $this->assertNotNull($item->fresh()->completed_at);
        $this->put($route, ['status' => 'blocked', 'notes' => 'Perlu penggantian', 'assigned_to' => $this->trainer->id])->assertRedirect();
        $this->assertNull($item->fresh()->completed_at);
    }

    public function test_editing_ready_batch_returns_it_to_preparation_with_history(): void
    {
        $this->readyFixture();
        $this->batch->update(['operational_status' => 'ready']);
        $session = $this->batch->sessions()->first();
        $this->actingAs($this->admin)->put(route('admin.operations.sessions.update', [$this->batch, $session]), $this->sessionData(['title' => 'Jadwal direvisi', 'reason' => 'Penyesuaian materi']))->assertRedirect();
        $this->assertSame('preparation', $this->batch->fresh()->operational_status);
        $this->assertDatabaseHas('batch_activity_logs', ['action' => 'status.invalidated']);
        $this->get(route('admin.operations.show', ['batch' => $this->batch, 'tab' => 'history']))->assertOk()->assertSee('Penyesuaian materi');
    }

    public function test_trainer_with_sessions_cannot_be_removed_until_sessions_cancelled_even_if_account_inactive(): void
    {
        $this->assignTeam();
        $this->batch->sessions()->create($this->sessionData());
        $assignment = $this->batch->assignments()->where('role', 'lead_trainer')->first();
        $this->actingAs($this->admin)->deleteJson(route('admin.operations.team.destroy', [$this->batch, $assignment]), ['reason' => 'Ganti Trainer'])->assertUnprocessable();
        $this->trainer->update(['status' => 'inactive']);
        $session = $this->batch->sessions()->first();
        $this->patch(route('admin.operations.sessions.cancel', [$this->batch, $session]), ['reason' => 'Trainer tidak aktif'])->assertRedirect();
        $this->delete(route('admin.operations.team.destroy', [$this->batch, $assignment]), ['reason' => 'Ganti Trainer'])->assertRedirect();
        $this->assertDatabaseMissing('batch_assignments', ['id' => $assignment->id]);
    }

    public function test_cancellation_closes_registration_releases_sessions_and_reopening_requires_permission(): void
    {
        $this->readyFixture();
        $route = route('admin.operations.status', $this->batch);
        $this->actingAs($this->coordinator)->patch($route, ['operational_status' => 'cancelled', 'reason' => 'Dibatalkan penyelenggara'])->assertRedirect();
        $this->assertSame('closed', $this->batch->fresh()->status);
        $this->assertSame('cancelled', $this->batch->sessions()->first()->status);
        $this->assertSame(1, $this->batch->activePaidEnrollments()->count());
        $this->patchJson($route, ['operational_status' => 'preparation', 'reason' => 'Buka'])->assertForbidden();
        $this->actingAs($this->admin)->putJson(route('admin.operations.checklist.update', [$this->batch, $this->batch->checklistItems()->first()]), ['status' => 'pending'])->assertUnprocessable();
        $this->patch($route, ['operational_status' => 'preparation', 'reason' => 'Dijadwalkan ulang'])->assertRedirect();
        $this->assertSame('preparation', $this->batch->fresh()->operational_status);
        $this->assertSame('closed', $this->batch->fresh()->status);
    }

    public function test_master_batch_cannot_shrink_below_roster_or_bypass_operational_lifecycle(): void
    {
        $this->readyFixture();
        $data = $this->batch->only(['training_program_id', 'code', 'name', 'capacity', 'status']);
        $data += ['start_date' => today()->toDateString(), 'end_date' => today()->addDays(7)->toDateString()];
        $this->actingAs($this->admin)->putJson(route('admin.batches.update', $this->batch), array_merge($data, ['status' => 'completed']))->assertUnprocessable();
        $this->enroll($this->batch);
        $this->putJson(route('admin.batches.update', $this->batch), array_merge($data, ['capacity' => 1]))->assertUnprocessable();
        $this->putJson(route('admin.batches.update', $this->batch), array_merge($data, ['start_date' => today()->addDay()->toDateString()]))->assertUnprocessable();
        $this->delete(route('admin.batches.destroy', $this->batch))->assertSessionHasErrors('batch');
    }

    public function test_session_edit_and_populated_roster_views_render(): void
    {
        $this->readyFixture();
        $session = $this->batch->sessions()->first();
        $this->actingAs($this->admin)->get(route('admin.operations.sessions.edit', [$this->batch, $session]))->assertOk()->assertSee('Praktik dasar');
        foreach (['overview', 'participants', 'team', 'schedule', 'checklist'] as $tab) {
            $response = $this->get(route('admin.operations.show', ['batch' => $this->batch, 'tab' => $tab]))->assertOk();
            if (getenv('OPS_CAPTURE_DIR')) {
                file_put_contents(getenv('OPS_CAPTURE_DIR').'/'.$tab.'.html', $response->getContent());
            }
        }
    }

    public function test_closed_operational_batches_reject_new_invoices_and_checkout_without_gateway_calls(): void
    {
        config()->set('services.midtrans.server_key', 'SB-Mid-server-test');
        Http::fake();
        $enrollment = $this->enroll($this->batch, 'unpaid');
        $participant = User::findOrFail($enrollment->user_id);
        $participant->assignRole('participant');
        $invoice = Invoice::where('training_application_id', $enrollment->training_application_id)->firstOrFail();
        $invoice->update(['due_at' => now()->addDay()]);

        foreach (['cancelled', 'completed'] as $status) {
            $this->batch->update(['operational_status' => $status]);
            $this->actingAs($participant)->postJson(route('invoices.store'), [
                'agreement' => true, 'training_application_id' => $enrollment->training_application_id,
            ])->assertUnprocessable()->assertJsonValidationErrors('application');
            $this->postJson(route('payments.store'), [
                'invoice_id' => $invoice->id, 'payment_method' => 'qris',
            ])->assertUnprocessable()->assertJsonValidationErrors('invoice');
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('unpaid', $invoice->fresh()->status);
    }

    public function test_staff_with_operational_records_cannot_be_deleted(): void
    {
        $this->admin->givePermissionTo('users.manage');
        $this->assignTeam();
        $this->actingAs($this->admin)->deleteJson(route('admin.users.destroy', $this->trainer))
            ->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->batch->sessions()->create($this->sessionData(['status' => 'cancelled']));
        $this->batch->assignments()->where('user_id', $this->trainer->id)->delete();
        $this->deleteJson(route('admin.users.destroy', $this->trainer))
            ->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->assertDatabaseHas('users', ['id' => $this->trainer->id]);
    }

    private function makeBatch(): TrainingBatch
    {
        $code = Str::upper(Str::random(8));
        $program = TrainingProgram::create(['code' => $code, 'title' => 'Program SMAW 3G', 'category' => 'Welding', 'duration_hours' => 40, 'price' => 3500000, 'status' => 'active']);

        return TrainingBatch::create(['training_program_id' => $program->id, 'code' => $code.'-B1', 'name' => 'Batch '.$code, 'start_date' => today(), 'end_date' => today()->addDays(7), 'capacity' => 12, 'status' => 'open']);
    }

    private function assignTeam(): void
    {
        $this->batch->assignments()->create(['user_id' => $this->trainer->id, 'role' => 'lead_trainer']);
        $this->batch->assignments()->create(['user_id' => $this->coordinator->id, 'role' => 'coordinator']);
    }

    private function readyFixture(): void
    {
        $this->assignTeam();
        $this->batch->sessions()->create($this->sessionData());
        $this->enroll($this->batch);
        $this->batch->checklistItems()->update(['status' => 'completed', 'notes' => 'Diperiksa sebelum pelatihan', 'completed_by' => $this->admin->id, 'completed_at' => now()]);
    }

    private function sessionData(array $overrides = []): array
    {
        return array_merge(['title' => 'Praktik dasar', 'type' => 'practice', 'trainer_id' => $this->trainer->id, 'location_id' => $this->location->id, 'starts_at' => today()->format('Y-m-d').'T08:00', 'ends_at' => today()->format('Y-m-d').'T10:00', 'status' => 'published', 'notes' => 'Praktik pengelasan dasar'], $overrides);
    }

    private function enroll(TrainingBatch $batch, string $invoiceStatus = 'paid', string $status = 'active', string $name = 'Peserta Pelatihan'): Enrollment
    {
        $user = User::factory()->create(['name' => $name]);
        $application = TrainingApplication::create(['registration_number' => 'REG-'.Str::random(10), 'user_id' => $user->id, 'training_program_id' => $batch->training_program_id, 'training_batch_id' => $batch->id, 'status' => 'approved']);
        Invoice::create(['invoice_number' => 'INV-'.Str::random(10), 'training_application_id' => $application->id, 'user_id' => $user->id, 'subtotal' => 3500000, 'discount_amount' => 0, 'total_amount' => 3500000, 'currency' => 'IDR', 'status' => $invoiceStatus, 'issued_at' => now(), 'paid_at' => $invoiceStatus === 'paid' ? now() : null]);

        return Enrollment::create(['enrollment_number' => 'ENR-'.Str::random(10), 'training_application_id' => $application->id, 'user_id' => $user->id, 'training_program_id' => $batch->training_program_id, 'training_batch_id' => $batch->id, 'status' => $status, 'enrolled_at' => now()]);
    }
}
