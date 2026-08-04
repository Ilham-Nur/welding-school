<?php

namespace Database\Seeders;

use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use Illuminate\Database\Seeder;

class TrainingBatchSeeder extends Seeder
{
    public function run(): void
    {
        $batches = [
            ['program' => 'SMAW-3G', 'code' => 'SMAW-2608', 'name' => 'Batch Agustus 2026', 'registration_deadline' => '2026-08-05', 'start_date' => '2026-08-10', 'end_date' => '2026-08-21', 'capacity' => 12, 'status' => 'open'],
            ['program' => 'FCAW-3G', 'code' => 'FCAW-2609', 'name' => 'Batch September 2026', 'registration_deadline' => '2026-09-02', 'start_date' => '2026-09-07', 'end_date' => '2026-09-18', 'capacity' => 12, 'status' => 'open'],
            ['program' => 'GMAW-2G', 'code' => 'GMAW-2609', 'name' => 'Batch Weekend September', 'registration_deadline' => '2026-09-01', 'start_date' => '2026-09-05', 'end_date' => '2026-10-04', 'capacity' => 10, 'status' => 'open'],
            ['program' => 'WI-BASIC', 'code' => 'WI-2611', 'name' => 'Batch November 2026', 'registration_deadline' => '2026-10-26', 'start_date' => '2026-11-02', 'end_date' => '2026-11-06', 'capacity' => 15, 'status' => 'open'],
        ];

        foreach ($batches as $batch) {
            $programId = TrainingProgram::query()
                ->where('code', $batch['program'])
                ->value('id');

            if (! $programId) {
                continue;
            }

            TrainingBatch::query()->updateOrCreate(
                ['code' => $batch['code']],
                [
                    'training_program_id' => $programId,
                    'name' => $batch['name'],
                    'registration_deadline' => $batch['registration_deadline'],
                    'start_date' => $batch['start_date'],
                    'end_date' => $batch['end_date'],
                    'capacity' => $batch['capacity'],
                    'status' => $batch['status'],
                ],
            );
        }
    }
}
