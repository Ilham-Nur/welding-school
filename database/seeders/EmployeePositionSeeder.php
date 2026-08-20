<?php

namespace Database\Seeders;

use App\Models\EmployeePosition;
use Illuminate\Database\Seeder;

class EmployeePositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Direktur', 'code' => 'DIR', 'description' => 'Pimpinan Utama Perusahaan & Akademi', 'display_order' => 1],
            ['name' => 'Manager Operasional', 'code' => 'OPS', 'description' => 'Pengelola operasional pelatihan dan workshop', 'display_order' => 2],
            ['name' => 'Instruktur Welder', 'code' => 'INS', 'description' => 'Tenaga pendidik & pelatih teknik pengelasan', 'display_order' => 3],
            ['name' => 'Welding Inspector', 'code' => 'WI', 'description' => 'Pemeriksa dan penguji kualitas hasil las (NDT/DT)', 'display_order' => 4],
            ['name' => 'Senior Welder', 'code' => 'SW', 'description' => 'Welder spesialis 6G / GTAW / SMAW / GMAW', 'display_order' => 5],
            ['name' => 'Staff HR & SDM', 'code' => 'HRD', 'description' => 'Pengelola kepegawaian dan kepersonaliaan', 'display_order' => 6],
            ['name' => 'Staff Keuangan & Akuntansi', 'code' => 'FIN', 'description' => 'Pengelola kas, faktur, dan pembukuan', 'display_order' => 7],
            ['name' => 'Staff Administrasi & IT', 'code' => 'ADM', 'description' => 'Pengelola data akademik dan sistem informasi', 'display_order' => 8],
            ['name' => 'Teknisi & Maintenance', 'code' => 'TECH', 'description' => 'Perawatan mesin las, genset, dan fasilitas bengkel', 'display_order' => 9],
        ];

        foreach ($positions as $pos) {
            EmployeePosition::query()->firstOrCreate(
                ['name' => $pos['name']],
                [
                    'code' => $pos['code'],
                    'description' => $pos['description'],
                    'is_active' => true,
                    'display_order' => $pos['display_order'],
                ]
            );
        }
    }
}
