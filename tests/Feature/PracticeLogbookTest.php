<?php

namespace Tests\Feature;

use App\Models\BatchSession;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PracticeSubmission;
use App\Models\TrainingApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PracticeLogbookTest extends TestCase
{
    use RefreshDatabase;

    private User $trainer;
    private User $participant;
    private TrainingBatch $batch;
    private BatchSession $session;
    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
        $this->trainer = User::factory()->create(['status' => 'active']);
        $this->trainer->assignRole('trainer');
        $program = TrainingProgram::create(['code' => 'LOG', 'title' => 'Praktik Welding', 'category' => 'Welding', 'duration_hours' => 40, 'price' => 5000000, 'status' => 'active']);
        $this->batch = $program->batches()->create(['code' => 'LOG-01', 'name' => 'Batch Logbook', 'start_date' => today(), 'end_date' => today()->addWeek(), 'capacity' => 15, 'status' => 'closed', 'operational_status' => 'running']);
        $this->batch->assignments()->create(['user_id' => $this->trainer->id, 'role' => 'lead_trainer']);
        $location = Location::create(['name' => 'Workshop Logbook', 'is_active' => true]);
        $this->session = $this->batch->sessions()->create(['title' => 'Praktik FCAW 3G', 'type' => 'practice', 'trainer_id' => $this->trainer->id, 'location_id' => $location->id, 'starts_at' => now(), 'ends_at' => now()->addHours(2), 'status' => 'published', 'execution_status' => 'running']);
        $this->participant = $this->enroll();
        $this->enrollment = $this->batch->enrollments()->where('user_id', $this->participant->id)->firstOrFail();
    }

    private function enroll(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('participant');
        $application = TrainingApplication::create(['registration_number' => 'REG-'.Str::random(8), 'user_id' => $user->id, 'training_program_id' => $this->batch->training_program_id, 'training_batch_id' => $this->batch->id, 'status' => 'approved']);
        Invoice::create(['invoice_number' => 'INV-'.Str::random(8), 'training_application_id' => $application->id, 'user_id' => $user->id, 'subtotal' => 5000000, 'total_amount' => 5000000, 'discount_amount' => 0, 'currency' => 'IDR', 'status' => 'paid', 'issued_at' => now(), 'paid_at' => now()]);
        Enrollment::create(['enrollment_number' => 'ENR-'.Str::random(8), 'training_application_id' => $application->id, 'user_id' => $user->id, 'training_program_id' => $this->batch->training_program_id, 'training_batch_id' => $this->batch->id, 'status' => 'active', 'enrolled_at' => now()]);

        return $user;
    }

    private function internal(string $action, ?PracticeSubmission $record = null): string
    {
        return route('admin.operations.practice.'.$action, $record ? [$this->batch, $this->session, $record] : [$this->batch, $this->session]);
    }

    private function configure(bool $photo = true): void
    {
        $this->actingAs($this->trainer)->put($this->internal('configure'), ['title' => 'Buat sambungan 3G', 'instructions' => 'Ikuti job sheet dan foto hasil las.', 'photo_required' => $photo, 'effective_minutes' => 90])->assertRedirect()->assertSessionHasNoErrors();
        $this->session->unsetRelation('practiceTask');
    }

    private function participantRoute(string $action): string
    {
        return route('participant.practice.'.$action, [$this->batch, $this->session->practiceTask]);
    }

    private function data(array $extra = []): array
    {
        $record = $this->record();

        return ['action' => 'submit', 'outcome' => 'completed', 'version' => $record?->version ?? 0, 'request_id' => (string) Str::uuid(), ...$extra];
    }

    private function record(): ?PracticeSubmission
    {
        return PracticeSubmission::where('enrollment_id', $this->enrollment->id)->first();
    }

    private function submit(array $extra = []): PracticeSubmission
    {
        $this->actingAs($this->participant)->postJson($this->participantRoute('store'), $this->data(['photo' => UploadedFile::fake()->image('hasil.jpg'), ...$extra]))->assertOk();

        return $this->record();
    }

    public function test_two_inputs_create_one_task_and_logbook_with_private_evidence(): void
    {
        $this->configure();
        $this->actingAs($this->participant)->get($this->participantRoute('show'))->assertOk()->assertSee('data-participant-shell', false)
            ->assertSee('data-practice-form', false)->assertSee('Kirim hasil aktivitas')->assertDontSee('class="site-header"', false);
        $record = $this->submit(['user_id' => 999, 'enrollment_id' => 999, 'verified_minutes' => 500]);
        $this->assertSame('submitted', $record->status);
        $this->assertSame('completed', $record->outcome);
        $this->assertNull($record->notes);
        $this->assertNull($record->verified_minutes);
        $this->assertDatabaseCount('practice_submissions', 1);
        $this->assertDatabaseCount('practice_submission_events', 1);
        Storage::disk('local')->assertExists($record->photo_path);
        $this->get($this->participantRoute('photo'))->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('participant.practice.index', $this->batch))->assertOk()->assertSee('Menunggu pemeriksaan')->assertViewHas('summary', fn ($s) => $s['submitted'] === 1 && $s['minutes'] === 0);
        $this->getJson(route('applications.current'))->assertOk()->assertJsonPath('application.practice_url', route('participant.practice.index', $this->batch));
    }

    public function test_draft_survives_refresh_and_retry_is_idempotent(): void
    {
        $this->configure();
        $data = $this->data(['action' => 'draft', 'outcome' => null, 'notes' => 'Catatan sementara', 'photo' => UploadedFile::fake()->image('hasil.jpg')]);
        $url = $this->participantRoute('store');
        $this->actingAs($this->participant)->postJson($url, $data)->assertOk()->assertJsonPath('record.version', 1);
        $this->postJson($url, $data)->assertOk()->assertJsonPath('record.version', 1);
        $this->assertDatabaseCount('practice_submissions', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('practice_submission_events', 0);
        $this->get($this->participantRoute('show'))->assertOk()->assertSee('Catatan sementara');
        $this->actingAs($this->trainer)->get($this->internal('show'))->assertOk()->assertSee('Draf pribadi peserta')->assertDontSee('Catatan sementara');
        $this->get($this->internal('photo', $this->record()))->assertNotFound();
        $this->actingAs($this->participant)->postJson($url, $this->data())->assertOk()->assertJsonPath('record.status', 'submitted');
        $this->assertDatabaseCount('practice_submissions', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_validation_requires_only_applicable_fields_and_rejects_invalid_photos(): void
    {
        $this->configure();
        $url = $this->participantRoute('store');
        $this->actingAs($this->participant)->postJson($url, $this->data(['outcome' => null]))->assertUnprocessable()->assertJsonValidationErrors('outcome');
        $this->postJson($url, $this->data())->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->postJson($url, $this->data(['outcome' => 'in_progress', 'obstacle' => 'other']))->assertUnprocessable()->assertJsonValidationErrors('notes');
        $this->postJson($url, $this->data(['photo' => UploadedFile::fake()->createWithContent('hasil.jpg', 'not an image')]))->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->postJson($url, $this->data(['photo' => UploadedFile::fake()->image('hasil.jpg')->size(6000)]))->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->assertDatabaseCount('practice_submissions', 0);
        $this->postJson($url, $this->data(['outcome' => 'not_started', 'obstacle' => 'equipment']))->assertOk();
        $this->assertNull($this->record()->photo_path);
        $this->assertSame('equipment', $this->record()->obstacle);
    }

    public function test_optional_photo_and_completed_result_do_not_require_notes_or_obstacles(): void
    {
        $this->configure(false);
        $this->actingAs($this->participant)->postJson($this->participantRoute('store'), $this->data(['obstacle' => 'other']))->assertOk();
        $this->assertNull($this->record()->obstacle);
        $this->assertNull($this->record()->notes);
    }

    public function test_revision_and_resubmission_reuse_the_record_and_keep_history(): void
    {
        $this->configure();
        $record = $this->submit();
        $oldPhoto = $record->photo_path;
        $this->actingAs($this->trainer)->putJson($this->internal('review', $record), ['status' => 'revision', 'version' => $record->version])->assertUnprocessable()->assertJsonValidationErrors('review_notes');
        $this->put($this->internal('review', $record), ['status' => 'revision', 'version' => $record->version, 'review_notes' => 'Foto bagian sambungan lebih dekat.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->participant)->get($this->participantRoute('show'))->assertOk()->assertSee('Foto bagian sambungan lebih dekat.');
        $this->submit(['notes' => 'Sudah diperbaiki']);
        $this->assertSame($record->id, $this->record()->id);
        $this->assertDatabaseCount('practice_submissions', 1);
        $this->assertDatabaseCount('practice_submission_events', 3);
        Storage::disk('local')->assertExists($oldPhoto);
        $record = $this->record();
        $this->actingAs($this->trainer)->put($this->internal('review', $record), ['status' => 'accepted', 'version' => $record->version])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('accepted', $record->fresh()->status);
        $this->assertNull($record->fresh()->verified_minutes);
        $this->actingAs($this->participant)->postJson($this->participantRoute('store'), $this->data(['action' => 'draft']))->assertUnprocessable();
    }

    public function test_stale_versions_do_not_overwrite_drafts_or_review_results(): void
    {
        $this->configure(false);
        $url = $this->participantRoute('store');
        $this->actingAs($this->participant)->postJson($url, $this->data(['action' => 'draft']))->assertOk();
        $this->postJson($url, $this->data(['version' => 0, 'notes' => 'Tab lama']))->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->assertNull($this->record()->notes);
        $this->postJson($url, $this->data())->assertOk();
        $record = $this->record();
        $this->actingAs($this->trainer)->putJson($this->internal('review', $record), ['status' => 'accepted', 'version' => 1])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->assertSame('submitted', $record->fresh()->status);
    }

    public function test_practice_minutes_require_explicit_review_and_final_present_attendance(): void
    {
        $this->configure();
        $record = $this->submit();
        $review = ['status' => 'accepted', 'version' => $record->version, 'verified_minutes' => 90];
        $this->actingAs($this->trainer)->putJson($this->internal('review', $record), $review)->assertUnprocessable()->assertJsonValidationErrors('verified_minutes');
        $this->session->update(['execution_status' => 'completed', 'attendance_finalized_at' => now()]);
        $attendance = $this->session->attendances()->create(['enrollment_id' => $this->enrollment->id, 'status' => 'present', 'attended_minutes' => 100]);
        $this->putJson($this->internal('review', $record), [...$review, 'verified_minutes' => 101])->assertUnprocessable()->assertJsonValidationErrors('verified_minutes');
        $this->put($this->internal('review', $record), $review)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(90, $record->fresh()->verified_minutes);
        $this->actingAs($this->participant)->get(route('participant.practice.index', $this->batch))->assertOk()->assertViewHas('summary', fn ($s) => $s['minutes'] === 90 && $s['accepted'] === 1);
        // Corrections to absences invalidate only the duration, not the accepted activity evidence.
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->put(route('admin.operations.execution.review', [$this->batch, $this->session, $attendance]), ['status' => 'absent', 'review_notes' => 'Koreksi identitas.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($record->fresh()->verified_minutes);
        $this->assertSame('accepted', $record->fresh()->status);
        $this->assertDatabaseHas('practice_submission_events', ['practice_submission_id' => $record->id, 'action' => 'hours_invalidated']);
    }

    public function test_accepted_notes_can_receive_duration_later_without_participant_resubmission(): void
    {
        $this->configure();
        $record = $this->submit(['outcome' => 'in_progress']);
        $this->actingAs($this->trainer)->put($this->internal('review', $record), ['status' => 'accepted', 'version' => $record->version])->assertRedirect()->assertSessionHasNoErrors();
        $this->session->update(['execution_status' => 'completed', 'attendance_finalized_at' => now()]);
        $this->session->attendances()->create(['enrollment_id' => $this->enrollment->id, 'status' => 'present', 'attended_minutes' => 100]);
        $this->put($this->internal('review', $record), ['status' => 'accepted', 'version' => $record->fresh()->version, 'verified_minutes' => 75])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(75, $record->fresh()->verified_minutes);
        $this->assertDatabaseCount('practice_submissions', 1);
    }

    public function test_access_is_scoped_to_active_paid_enrollment_and_assigned_trainers(): void
    {
        $this->configure();
        $record = $this->submit();
        $other = $this->enroll();
        $this->actingAs($other)->get($this->participantRoute('photo'))->assertNotFound();
        $this->get($this->participantRoute('show'))->assertOk()->assertDontSee($record->photo_path);
        $this->get($this->internal('show'))->assertForbidden();
        $outsider = User::factory()->create(['status' => 'active']);
        $outsider->assignRole('trainer');
        $this->actingAs($outsider)->get($this->internal('show'))->assertForbidden();
        $this->putJson($this->internal('review', $record), ['status' => 'accepted', 'version' => $record->version])->assertForbidden();
        $this->actingAs($this->participant);
        $this->enrollment->trainingApplication->invoice->update(['status' => 'refunded']);
        $this->get($this->participantRoute('show'))->assertForbidden();
        $this->postJson($this->participantRoute('store'), $this->data())->assertForbidden();
        $this->enrollment->trainingApplication->invoice->update(['status' => 'paid']);
        $this->participant->update(['status' => 'inactive']);
        $this->get($this->participantRoute('photo'))->assertForbidden();
    }

    public function test_session_and_task_cannot_be_crossed_between_batches(): void
    {
        $this->configure();
        $record = $this->submit();
        $otherBatch = $this->batch->replicate();
        $otherBatch->code = 'LOG-02';
        $otherBatch->save();
        $this->actingAs($this->trainer)->get(route('admin.operations.practice.show', [$otherBatch, $this->session]))->assertNotFound();
        $otherSession = $this->session->replicate();
        $otherSession->title = 'Sesi lain';
        $otherSession->save();
        $this->putJson(route('admin.operations.practice.review', [$this->batch, $otherSession, $record]), ['status' => 'accepted', 'version' => $record->version])->assertNotFound();
        $this->get(route('admin.operations.practice.photo', [$this->batch, $otherSession, $record]))->assertNotFound();
    }

    public function test_activity_is_locked_after_any_draft_and_closed_sessions_cannot_submit(): void
    {
        $this->configure(false);
        $url = $this->participantRoute('store');
        $this->session->update(['execution_status' => 'scheduled']);
        $this->actingAs($this->participant)->postJson($url, $this->data())->assertUnprocessable()->assertJsonValidationErrors('task');
        $this->session->update(['execution_status' => 'running']);
        $this->postJson($url, $this->data(['action' => 'draft']))->assertOk();
        $this->actingAs($this->trainer)->putJson($this->internal('configure'), ['title' => 'Instruksi berbeda', 'instructions' => 'Ganti', 'photo_required' => true])->assertUnprocessable()->assertJsonValidationErrors('task');
        $this->assertDatabaseCount('session_practice_tasks', 1);
        $this->batch->update(['operational_status' => 'postponed']);
        $this->actingAs($this->participant)->postJson($url, $this->data())->assertUnprocessable();
        $this->batch->update(['operational_status' => 'running']);
        $this->session->update(['status' => 'cancelled']);
        $this->postJson($url, $this->data())->assertNotFound();
    }

    public function test_configuration_duration_is_only_an_optional_bound_practice_reference(): void
    {
        $this->actingAs($this->trainer)->putJson($this->internal('configure'), ['title' => 'Latihan', 'instructions' => 'Ikuti instruktur', 'photo_required' => true, 'effective_minutes' => 150])->assertUnprocessable()->assertJsonValidationErrors('effective_minutes');
        $this->session->update(['type' => 'theory']);
        $this->putJson($this->internal('configure'), ['title' => 'Teori', 'instructions' => 'Diskusi', 'photo_required' => false, 'effective_minutes' => 30])->assertUnprocessable()->assertJsonValidationErrors('effective_minutes');
        $this->put($this->internal('configure'), ['title' => 'Teori', 'instructions' => 'Diskusi', 'photo_required' => false])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($this->session->practiceTask->effective_minutes);
    }

    public function test_empty_logbook_does_not_create_dummy_records_and_guest_redirects(): void
    {
        $this->get(route('participant.practice.index', $this->batch))->assertRedirect(route('login'));
        $this->actingAs($this->participant)->get(route('participant.practice.index', $this->batch))->assertOk()->assertSee('Belum ada aktivitas');
        $this->assertDatabaseCount('practice_submissions', 0);
        $this->actingAs($this->trainer)->get($this->internal('show'))->assertOk()->assertSee('Terbitkan aktivitas');
    }
}
