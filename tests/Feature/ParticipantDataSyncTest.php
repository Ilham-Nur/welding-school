<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\TrainingApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantDataSyncTest extends TestCase
{
    use RefreshDatabase;

    private function batch(): TrainingBatch
    {
        $program = TrainingProgram::create(['code' => 'SYNC', 'title' => 'Program Internal', 'category' => 'Welding', 'duration_hours' => 48, 'price' => 4000000, 'status' => 'active']);

        return $program->batches()->create(['code' => 'SYNC-B1', 'name' => 'Batch Internal', 'start_date' => today()->addMonth(), 'capacity' => 5, 'status' => 'open', 'operational_status' => 'preparation', 'budget_required' => false]);
    }

    private function application(User $user, TrainingBatch $batch): TrainingApplication
    {
        return TrainingApplication::create(['registration_number' => 'REG-'.$user->id, 'user_id' => $user->id, 'training_program_id' => $batch->training_program_id, 'training_batch_id' => $batch->id, 'status' => 'approved', 'submitted_at' => now(), 'quoted_price' => 3900000, 'quoted_administration_fee' => 0, 'personal_data_snapshot' => ['full_name' => 'Nama Saat Mendaftar']]);
    }

    public function test_catalog_excludes_full_expired_and_unavailable_batches_and_refreshes(): void
    {
        $batch = $this->batch();
        $user = User::factory()->create();
        $this->application($user, $batch);
        $this->actingAs($user)->getJson(route('applications.current'))->assertOk()
            ->assertJsonPath('catalog.programs.0.duration_hours', 48)
            ->assertJsonPath('catalog.programs.0.batches.0.applications_count', 1);

        foreach ([['capacity' => 1], ['capacity' => 5, 'registration_deadline' => today()->subDay()], ['registration_deadline' => null, 'operational_status' => 'running'], ['operational_status' => 'preparation', 'status' => 'draft']] as $changes) {
            $batch->update($changes);
            $this->getJson(route('applications.current'))->assertJsonCount(0, 'catalog.programs.0.batches');
            $this->get('/')->assertViewHas('catalog', fn ($catalog) => $catalog->first()['batches']->isEmpty());
        }
        $batch->update(['status' => 'open', 'registration_deadline' => today()->addWeek()]);
        $this->getJson(route('applications.current'))->assertJsonCount(1, 'catalog.programs.0.batches');
    }

    public function test_participant_sees_current_batch_and_only_published_sessions_for_their_paid_enrollment(): void
    {
        $batch = $this->batch();
        $user = User::factory()->create(['status' => 'active']);
        $application = $this->application($user, $batch);
        $other = User::factory()->create();
        $this->application($other, $batch);
        $trainer = User::factory()->create(['name' => 'Trainer Internal']);
        $location = Location::create(['name' => 'Workshop Aktual', 'is_active' => true]);
        foreach (['published', 'draft', 'cancelled'] as $status) {
            $batch->sessions()->create(['title' => 'Sesi '.$status, 'type' => 'practice', 'status' => $status, 'trainer_id' => $trainer->id, 'location_id' => $location->id, 'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(2), 'notes' => 'Catatan internal rahasia']);
        }
        $invoice = Invoice::create(['invoice_number' => 'INV-SYNC', 'training_application_id' => $application->id, 'user_id' => $user->id, 'subtotal' => 3900000, 'total_amount' => 3800000, 'discount_amount' => 100000, 'currency' => 'IDR', 'status' => 'unpaid', 'issued_at' => now()]);
        $enrollment = Enrollment::create(['enrollment_number' => 'ENR-SYNC', 'training_application_id' => $application->id, 'user_id' => $user->id, 'training_program_id' => $batch->training_program_id, 'training_batch_id' => $batch->id, 'status' => 'active', 'enrolled_at' => now()]);
        $this->actingAs($user)->getJson(route('applications.current'))->assertOk()
            ->assertJsonPath('application.training_access', false)->assertJsonCount(0, 'application.sessions');

        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        $batch->update(['name' => 'Batch Diubah Admin', 'operational_status' => 'running']);
        $batch->trainingProgram->update(['title' => 'Judul Diubah Admin', 'status' => 'closed']);
        $this->getJson(route('applications.current'))->assertOk()->assertJsonCount(1, 'applications')
            ->assertJsonPath('application.training_access', true)
            ->assertJsonPath('application.program.title', 'Judul Diubah Admin')
            ->assertJsonPath('application.batch.name', 'Batch Diubah Admin')
            ->assertJsonPath('application.batch.operational_label', 'Berjalan')
            ->assertJsonPath('application.participant.full_name', 'Nama Saat Mendaftar')
            ->assertJsonPath('application.invoice.total_amount', 3800000)
            ->assertJsonPath('application.sessions.0.trainer', 'Trainer Internal')
            ->assertJsonPath('application.sessions.0.location', 'Workshop Aktual')
            ->assertJsonCount(1, 'application.sessions')
            ->assertDontSee('Catatan internal rahasia')->assertDontSee('Sesi draft')->assertDontSee('Sesi cancelled');

        $batch->update(['operational_status' => 'cancelled']);
        $this->getJson(route('applications.current'))->assertJsonPath('application.training_access', false)
            ->assertJsonPath('application.batch.operational_label', 'Dibatalkan')->assertJsonCount(0, 'application.sessions');
        $batch->update(['operational_status' => 'completed']);
        $enrollment->update(['status' => 'completed']);
        $this->getJson(route('applications.current'))->assertJsonCount(1, 'applications')
            ->assertJsonPath('application.enrollment.status', 'completed')->assertJsonPath('application.training_access', false);
    }
}
