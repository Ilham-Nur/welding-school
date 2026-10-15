<?php

namespace Tests\Feature;

use App\Models\BatchSession;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\SessionAttendance;
use App\Models\TrainingApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $trainer;

    private User $participant;

    private TrainingBatch $batch;

    private BatchSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-06 08:00:00', config('app.timezone')));
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
        $this->trainer = User::factory()->create(['status' => 'active']);
        $this->trainer->assignRole('trainer');
        $program = TrainingProgram::create(['code' => 'SELFIE', 'title' => 'Praktik Welding', 'category' => 'Welding', 'duration_hours' => 40, 'price' => 5000000, 'status' => 'active']);
        $this->batch = $program->batches()->create(['code' => 'SELFIE-01', 'name' => 'Batch Selfie', 'start_date' => today(), 'end_date' => today()->addWeek(), 'capacity' => 15, 'status' => 'closed', 'operational_status' => 'running']);
        $this->batch->assignments()->create(['user_id' => $this->trainer->id, 'role' => 'lead_trainer']);
        $location = Location::create(['name' => 'Workshop Selfie', 'is_active' => true]);
        $this->session = $this->batch->sessions()->create(['title' => 'Praktik hari pertama', 'type' => 'practice', 'trainer_id' => $this->trainer->id, 'location_id' => $location->id, 'starts_at' => now(), 'ends_at' => now()->addHours(2), 'status' => 'published']);
        $this->participant = $this->enroll();
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

    private function adminRoute(string $action, ?SessionAttendance $record = null): string
    {
        return route('admin.operations.execution.'.$action, $record ? [$this->batch, $this->session, $record] : [$this->batch, $this->session]);
    }

    private function area(): array
    {
        return ['attendance_latitude' => -6.2, 'attendance_longitude' => 106.8, 'attendance_radius_meters' => 100, 'late_grace_minutes' => 15, 'attendance_area_confirmed' => true];
    }

    private function start(): void
    {
        $this->actingAs($this->trainer)->put($this->adminRoute('area'), $this->area())->assertRedirect()->assertSessionHasNoErrors();
        $this->post($this->adminRoute('start'))->assertRedirect()->assertSessionHasNoErrors();
    }

    private function record(?User $user = null): SessionAttendance
    {
        return $this->session->attendances()->whereHas('enrollment', fn ($q) => $q->where('user_id', ($user ?? $this->participant)->id))->firstOrFail();
    }

    private function evidence(string $kind = 'in'): array
    {
        $response = $this->actingAs($this->participant)->postJson(route('participant.attendance.challenge', [$this->batch, $this->session]), ['kind' => $kind])->assertOk();

        return ['kind' => $kind, 'token' => $response->json('token'), 'latitude' => -6.2, 'longitude' => 106.8, 'accuracy' => 5, 'location_timestamp' => now()->getTimestampMs(), 'photo' => UploadedFile::fake()->image('selfie.jpg', 480, 640)];
    }

    private function capture(array $data)
    {
        return $this->postJson(route('participant.attendance.store', [$this->batch, $this->session]), $data);
    }

    public function test_maps_area_resolution_requires_authorized_trainer_and_only_previews_before_confirmation(): void
    {
        Http::fake();
        $url = route('admin.operations.execution.area.resolve', [$this->batch, $this->session]);
        $this->actingAs($this->participant)->postJson($url, ['maps_location' => '-6.2,106.8'])->assertForbidden();
        $outsider = User::factory()->create(['status' => 'active']);
        $outsider->assignRole('trainer');
        $this->actingAs($outsider)->postJson($url, ['maps_location' => '-6.2,106.8'])->assertForbidden();
        $this->actingAs($this->trainer)->postJson($url, ['maps_location' => '-6.2,106.8'])->assertOk()
            ->assertJsonPath('latitude', -6.2)->assertJsonPath('longitude', 106.8)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertNull($this->session->fresh()->attendance_latitude);
        $this->assertDatabaseCount('batch_activity_logs', 0);
        $data = $this->area();
        unset($data['attendance_area_confirmed']);
        $this->putJson($this->adminRoute('area'), $data)->assertUnprocessable()->assertJsonValidationErrors('attendance_area_confirmed');
        $this->assertNull($this->session->fresh()->attendance_latitude);
        $this->start();
        $this->postJson($url, ['maps_location' => '-6.2,106.8'])->assertUnprocessable();
        $this->actingAs($this->participant)->get(route('participant.attendance.show', [$this->batch, $this->session]))->assertOk()
            ->assertSee('Buka lokasi absensi di Google Maps')->assertSee('https://www.google.com/maps/search/?api=1&amp;query=', false);
        Http::assertNothingSent();
    }

    public function test_complete_selfie_flow_private_photos_review_finalization_and_summary(): void
    {
        $this->actingAs($this->trainer)->get($this->adminRoute('show'))->assertOk()->assertSee('Gunakan lokasi saya');
        $this->actingAs($this->participant)->get(route('participant.attendance.index', $this->batch))->assertOk()->assertSee('Belum ada rekap')
            ->assertSee('data-participant-shell', false)->assertSee('Menu peserta')->assertSee($this->participant->email)
            ->assertSee('training-access-tabs', false)->assertSee('participant_view=training', false)
            ->assertDontSee('class="site-header"', false)->assertDontSee('class="public-nav"', false)->assertDontSee('class="site-footer"', false);
        $this->assertDatabaseCount('session_attendances', 0);
        $this->start();
        $this->actingAs($this->participant)->get(route('participant.attendance.show', [$this->batch, $this->session]))->assertOk()
            ->assertSee('data-selfie-attendance', false)->assertDontSee('type="file"', false)
            ->assertSee('data-participant-shell', false)->assertSee('Menu peserta')->assertSee('Profil &amp; Data Diri', false)
            ->assertSee('training-access-tabs', false)->assertSee('participant-shell.js', false)
            ->assertDontSee('class="site-header"', false)->assertDontSee('class="public-nav"', false)->assertDontSee('class="site-footer"', false);
        $this->capture($this->evidence())->assertOk();
        $record = $this->record();
        Storage::disk('local')->assertExists($record->in_photo_path);
        $this->get(route('participant.attendance.photo', [$this->batch, $this->session, $record, 'in']))->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
        $this->travel(90)->minutes();
        $this->capture($this->evidence('out'))->assertOk();
        $this->actingAs($this->trainer)->put($this->adminRoute('review', $record), ['status' => 'present', 'review_notes' => 'Kedua selfie sesuai identitas peserta.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(90, $record->fresh()->attended_minutes);
        $this->post($this->adminRoute('complete'), ['execution_notes' => 'Praktik selesai sesuai materi.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->post($this->adminRoute('finalize'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($this->session->fresh()->attendance_finalized_at);
        $this->get($this->adminRoute('show'))->assertOk()->assertSee('Difinalisasi');
        $this->actingAs($this->participant)->get(route('participant.attendance.index', $this->batch))->assertOk()->assertViewHas('summary', fn ($s) => $s['percentage'] === 100.0 && $s['practice_minutes'] === 90);
        $this->getJson(route('applications.current'))->assertOk()->assertJsonPath('application.attendance_url', route('participant.attendance.index', $this->batch));
    }

    public function test_server_rejects_outside_area_uncertain_stale_and_invalid_photos_without_writes(): void
    {
        $this->start();
        $data = $this->evidence();
        foreach ([['latitude' => -6.3], ['accuracy' => 500], ['latitude' => -6.1992, 'accuracy' => 20], ['location_timestamp' => now()->subMinute()->getTimestampMs()], ['location_timestamp' => now()->addMinute()->getTimestampMs()]] as $change) {
            $this->capture([...$data, ...$change])->assertUnprocessable()->assertJsonValidationErrors('location');
        }
        $this->capture([...$data, 'photo' => UploadedFile::fake()->createWithContent('selfie.jpg', 'not an image')])->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->capture([...$data, 'photo' => UploadedFile::fake()->image('selfie.jpg', 2000, 2000)])->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->assertNull($this->record()->checked_in_at);
        $this->assertCount(0, Storage::disk('local')->allFiles());
        $this->capture($data)->assertOk();
    }

    public function test_challenges_expire_bind_to_user_and_kind_and_prevent_duplicate_capture(): void
    {
        $other = $this->enroll();
        $this->start();
        $data = $this->evidence();
        $this->actingAs($other);
        $this->capture($data)->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->actingAs($this->participant);
        $this->capture([...$data, 'kind' => 'out'])->assertUnprocessable()->assertJsonValidationErrors('attendance');
        $this->travel(121)->seconds();
        $this->capture([...$data, 'location_timestamp' => now()->getTimestampMs()])->assertUnprocessable()->assertJsonValidationErrors('token');
        $fresh = $this->evidence();
        $this->capture($fresh)->assertOk();
        $this->capture($fresh)->assertUnprocessable()->assertJsonValidationErrors('attendance');
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->assertNotNull(DB::table('attendance_challenges')->where('session_attendance_id', $this->record()->id)->first()->consumed_at);
    }

    public function test_participant_and_unassigned_staff_cannot_operate_or_access_other_evidence(): void
    {
        $other = $this->enroll();
        $this->start();
        $this->capture($this->evidence())->assertOk();
        $record = $this->record();
        $staff = User::factory()->create(['status' => 'active']);
        $staff->assignRole('trainer');
        $this->actingAs($staff)->get($this->adminRoute('show'))->assertForbidden();
        $this->post($this->adminRoute('start'))->assertForbidden();
        $this->actingAs($this->participant)->put($this->adminRoute('review', $record), ['status' => 'present', 'review_notes' => 'Manipulasi'])->assertForbidden();
        $this->actingAs($other)->get(route('participant.attendance.photo', [$this->batch, $this->session, $record, 'in']))->assertNotFound();
        $this->actingAs($this->participant);
        foreach (['refunded', 'unpaid'] as $status) {
            Invoice::where('user_id', $this->participant->id)->update(['status' => $status]);
            $this->get(route('participant.attendance.index', $this->batch))->assertForbidden();
            $this->get(route('participant.attendance.photo', [$this->batch, $this->session, $record, 'in']))->assertForbidden();
        }
    }

    public function test_session_start_requires_open_batch_published_session_area_date_and_preserves_roster(): void
    {
        $this->actingAs($this->trainer)->post($this->adminRoute('start'))->assertSessionHasErrors('area');
        $this->put($this->adminRoute('area'), [...$this->area(), 'attendance_radius_meters' => 10000])->assertSessionHasErrors('attendance_radius_meters');
        $this->put($this->adminRoute('area'), $this->area())->assertSessionHasNoErrors();
        foreach ([['status' => 'draft'], ['status' => 'cancelled'], ['status' => 'published', 'starts_at' => now()->addHour()], ['starts_at' => now()->subDay()]] as $changes) {
            $this->session->update($changes);
            $this->post($this->adminRoute('start'))->assertSessionHasErrors('session');
        }
        $this->session->update(['starts_at' => now()]);
        $this->batch->update(['operational_status' => 'ready']);
        $this->post($this->adminRoute('start'))->assertSessionHasErrors('session');
        $this->batch->update(['operational_status' => 'running']);
        $this->post($this->adminRoute('start'))->assertSessionHasNoErrors();
        $this->post($this->adminRoute('start'))->assertSessionHasErrors('session');
        $this->put($this->adminRoute('area'), $this->area())->assertSessionHasErrors('session');
        $this->patch(route('admin.operations.sessions.cancel', [$this->batch, $this->session]), ['reason' => 'Hapus bukti'])->assertSessionHasErrors('session');
        $new = $this->enroll();
        $this->actingAs($new)->postJson(route('participant.attendance.challenge', [$this->batch, $this->session]), ['kind' => 'in'])->assertNotFound();
        $this->assertDatabaseCount('session_attendances', 1);
    }

    public function test_no_manual_present_finalization_requires_review_and_correction_requires_permission_and_reason(): void
    {
        $this->start();
        $record = $this->record();
        $this->put($this->adminRoute('review', $record), ['status' => 'present', 'review_notes' => 'Tanpa bukti'])->assertSessionHasErrors('status');
        $this->post($this->adminRoute('finalize'))->assertSessionHasErrors('attendance');
        $this->post($this->adminRoute('complete'), ['execution_notes' => 'Sesi berlangsung.'])->assertSessionHasNoErrors();
        $this->post($this->adminRoute('finalize'))->assertSessionHasErrors('attendance');
        $this->put($this->adminRoute('review', $record), ['status' => 'sick', 'review_notes' => 'Peserta melapor sakit.'])->assertSessionHasNoErrors();
        $this->post($this->adminRoute('finalize'))->assertSessionHasNoErrors();
        $this->put($this->adminRoute('review', $record), ['status' => 'absent', 'review_notes' => 'Koreksi'])->assertForbidden();
        $this->actingAs($this->admin)->put($this->adminRoute('review', $record), ['status' => 'permission', 'review_notes' => ''])->assertSessionHasErrors('review_notes');
        $this->put($this->adminRoute('review', $record), ['status' => 'permission', 'review_notes' => 'Koreksi berdasarkan surat izin.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('batch_activity_logs', ['action' => 'attendance.corrected', 'reason' => 'Koreksi berdasarkan surat izin.']);
        $this->assertSame(0, $record->fresh()->attended_minutes);
    }

    public function test_missing_checkout_blocks_completion_and_no_capture_after_session_closes_or_batch_postponed(): void
    {
        $this->start();
        $this->capture($this->evidence())->assertOk();
        $data = $this->evidence('out');
        $this->actingAs($this->trainer)->post($this->adminRoute('complete'), ['execution_notes' => 'Tutup'])->assertSessionHasErrors('attendance');
        $this->batch->update(['operational_status' => 'postponed']);
        $this->actingAs($this->participant);
        $this->capture($data)->assertUnprocessable()->assertJsonValidationErrors('session');
        $this->batch->update(['operational_status' => 'running']);
        $this->travel(10)->minutes();
        $this->capture($this->evidence('out'))->assertOk();
        $this->actingAs($this->trainer)->post($this->adminRoute('complete'), ['execution_notes' => 'Selesai'])->assertSessionHasNoErrors();
        $this->actingAs($this->participant)->postJson(route('participant.attendance.challenge', [$this->batch, $this->session]), ['kind' => 'in'])->assertUnprocessable();
    }

    public function test_late_flag_server_clock_and_nonfinalized_sessions_do_not_inflate_summary(): void
    {
        $this->start();
        $this->travel(16)->minutes();
        $this->capture([...$this->evidence(), 'checked_in_at' => now()->subHours(3), 'status' => 'present'])->assertOk();
        $this->assertTrue($this->record()->is_late);
        $this->assertSame('pending', $this->record()->status);
        $this->assertTrue($this->record()->checked_in_at->equalTo(now()));
        $this->get(route('participant.attendance.index', $this->batch))->assertViewHas('summary', fn ($s) => $s['sessions'] === 0 && $s['practice_minutes'] === 0);
    }

    public function test_cross_batch_route_bindings_and_revoked_accounts_are_rejected(): void
    {
        $this->start();
        $otherBatch = $this->batch->replicate(['code']);
        $otherBatch->code = 'OTHER-SELFIE';
        $otherBatch->save();
        $this->actingAs($this->admin)->get(route('admin.operations.execution.show', [$otherBatch, $this->session]))->assertNotFound();
        $this->actingAs($this->participant)->postJson(route('participant.attendance.challenge', [$otherBatch, $this->session]), ['kind' => 'in'])->assertNotFound();
        $this->participant->update(['status' => 'inactive']);
        $this->postJson(route('participant.attendance.challenge', [$this->batch, $this->session]), ['kind' => 'in'])->assertForbidden();
    }

    public function test_started_schedule_is_immutable_completed_session_survives_batch_cancellation_and_reviewer_cannot_be_deleted(): void
    {
        $this->start();
        $data = $this->session->only(['title', 'type', 'trainer_id', 'location_id', 'status']);
        $this->put(route('admin.operations.sessions.update', [$this->batch, $this->session]), [
            ...$data, 'starts_at' => now()->format('Y-m-d\TH:i'), 'ends_at' => now()->addHours(2)->format('Y-m-d\TH:i'), 'reason' => 'Mengubah sesi berjalan',
        ])->assertSessionHasErrors('session');
        $reviewer = User::factory()->create(['status' => 'active']);
        $reviewer->assignRole('admin');
        $this->actingAs($reviewer)->put($this->adminRoute('review', $this->record()), ['status' => 'absent', 'review_notes' => 'Tidak mengikuti sesi.'])->assertSessionHasNoErrors();
        $this->post($this->adminRoute('complete'), ['execution_notes' => 'Sesi selesai.'])->assertSessionHasNoErrors();
        $this->post($this->adminRoute('finalize'))->assertSessionHasNoErrors();
        $this->admin->givePermissionTo('users.manage');
        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $reviewer))->assertSessionHasErrors('user');
        $this->patch(route('admin.operations.status', $this->batch), ['operational_status' => 'cancelled', 'reason' => 'Batch dibatalkan setelah sesi pertama.'])->assertSessionHasNoErrors();
        $this->assertSame('published', $this->session->fresh()->status);
        $this->assertSame('completed', $this->session->fresh()->execution_status);
        $this->assertDatabaseCount('session_attendances', 1);
    }
}
