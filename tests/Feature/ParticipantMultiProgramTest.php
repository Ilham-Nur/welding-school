<?php

namespace Tests\Feature;

use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParticipantMultiProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_apply_to_different_programs_but_not_duplicate_one(): void
    {
        Storage::fake('local');

        $participant = User::factory()->create([
            'username' => 'peserta.multi',
            'name' => 'Peserta Multi Program',
        ]);
        $participant->participantProfile()->create([
            'phone' => '081234567890',
            'identity_type' => 'ktp',
            'identity_number' => '3671000000000002',
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
        [$firstProgram, $firstBatch] = $this->programAndBatch('MULTI-SMAW');
        [$secondProgram, $secondBatch] = $this->programAndBatch('MULTI-FCAW');

        $this->actingAs($participant)
            ->post(route('applications.store'), $this->applicationPayload(
                $firstProgram,
                $firstBatch,
            ))
            ->assertCreated();

        $this->withHeader('Accept', 'application/json')
            ->post(route('applications.store'), $this->applicationPayload(
                $firstProgram,
                $firstBatch,
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('training_program_id');

        $this->withHeader('Accept', 'application/json')
            ->post(route('applications.store'), $this->applicationPayload(
                $secondProgram,
                $secondBatch,
            ))
            ->assertCreated();

        $this->assertDatabaseCount('training_applications', 2);

        $this->getJson(route('applications.current'))
            ->assertOk()
            ->assertJsonCount(2, 'applications')
            ->assertJsonPath('applications.0.training_program_id', $secondProgram->id)
            ->assertJsonPath('applications.1.training_program_id', $firstProgram->id);
    }

    /**
     * @return array{TrainingProgram, TrainingBatch}
     */
    private function programAndBatch(string $code): array
    {
        $program = TrainingProgram::query()->create([
            'code' => $code,
            'title' => "Program {$code}",
            'category' => 'Testing',
            'duration_hours' => 80,
            'price' => 3500000,
            'status' => 'active',
        ]);
        $batch = TrainingBatch::query()->create([
            'training_program_id' => $program->id,
            'code' => "{$code}-B1",
            'name' => "Batch {$code}",
            'registration_deadline' => now()->addWeek(),
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addWeek(),
            'capacity' => 12,
            'status' => 'open',
        ]);

        return [$program, $batch];
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationPayload(
        TrainingProgram $program,
        TrainingBatch $batch,
    ): array {
        return [
            'training_program_id' => $program->id,
            'training_batch_id' => $batch->id,
            'full_name' => 'Peserta Multi Program',
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
                'id' => UploadedFile::fake()->image("ktp-{$program->id}.jpg"),
                'photo' => UploadedFile::fake()->image("foto-{$program->id}.jpg"),
                'education' => UploadedFile::fake()->create(
                    "ijazah-{$program->id}.pdf",
                    100,
                    'application/pdf',
                ),
            ],
        ];
    }
}
