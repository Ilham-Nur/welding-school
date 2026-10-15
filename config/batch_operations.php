<?php

return [
    'permissions' => [
        'operations.view' => 'Melihat operasional batch penugasan',
        'operations.manage-all' => 'Mengakses seluruh batch operasional',
        'operations.team' => 'Menugaskan tim pelaksana',
        'operations.schedule' => 'Mengelola jadwal sesi',
        'operations.checklist' => 'Mengelola checklist kesiapan',
        'operations.status' => 'Mengubah status operasional',
        'operations.reopen' => 'Mengembalikan batch ke Persiapan',
    ],
    'roles' => [
        'trainer' => ['practice.manage', 'practice.review', 'operations.execution', 'attendance.area', 'attendance.manage', 'training-materials.view', 'operations.materials', 'admin.access', 'operations.view', 'operations.schedule', 'operations.checklist'],
        'penanggung-jawab' => ['training-materials.view', 'operations.materials', 'admin.access', 'operations.view', 'operations.schedule', 'operations.checklist', 'operations.status'],
    ],
];
