<?php

namespace Database\Seeders;

use App\Models\TrainingProgram;
use Illuminate\Database\Seeder;

class TrainingProgramSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        TrainingProgram::query()->upsert([
            ['code' => 'SMAW-3G', 'title' => 'SMAW Welder 3G', 'category' => 'Shielded Metal Arc Welding', 'duration_hours' => 80, 'price' => 3500000, 'status' => 'active', 'start_date' => '2026-08-10', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'FCAW-3G', 'title' => 'FCAW Welder 3G', 'category' => 'Flux Cored Arc Welding', 'duration_hours' => 80, 'price' => 3800000, 'status' => 'active', 'start_date' => '2026-09-07', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'GTAW-6G', 'title' => 'GTAW Welder 6G', 'category' => 'Gas Tungsten Arc Welding', 'duration_hours' => 96, 'price' => 4800000, 'status' => 'draft', 'start_date' => '2026-10-05', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'GMAW-2G', 'title' => 'GMAW Welder 2G', 'category' => 'Gas Metal Arc Welding', 'duration_hours' => 72, 'price' => 3600000, 'status' => 'active', 'start_date' => '2026-09-21', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'WQT-6G', 'title' => 'Welder Qualification Test 6G', 'category' => 'Uji Kompetensi', 'duration_hours' => 16, 'price' => 2000000, 'status' => 'closed', 'start_date' => '2026-08-03', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'WI-BASIC', 'title' => 'Welding Inspector Dasar', 'category' => 'Inspeksi dan Mutu', 'duration_hours' => 40, 'price' => 3000000, 'status' => 'active', 'start_date' => '2026-11-02', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'K3-WLD', 'title' => 'K3 Pengelasan Industri', 'category' => 'Keselamatan Kerja', 'duration_hours' => 24, 'price' => 1500000, 'status' => 'draft', 'start_date' => null, 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['title', 'category', 'duration_hours', 'price', 'status', 'start_date', 'updated_at']);
    }
}
