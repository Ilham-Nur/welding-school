<?php

return [
    'seed' => [
        'name' => env('ADMIN_NAME', 'Administrator PT. Alpha Teknik Pratama'),
        'email' => env('ADMIN_EMAIL', 'admin@welding-school.test'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'permission_labels' => [
        'admin.access' => 'Akses dashboard admin',
        'users.view' => 'Melihat pengguna',
        'users.manage' => 'Mengelola pengguna',
        'roles.view' => 'Melihat role',
        'roles.manage' => 'Mengelola role dan izin',
        'applications.view' => 'Melihat pendaftaran',
        'applications.approve' => 'Menyetujui atau menolak pendaftaran',
        'programs.view' => 'Melihat program pelatihan',
        'programs.manage' => 'Mengelola program pelatihan',
        'batches.view' => 'Melihat batch',
        'batches.manage' => 'Mengelola batch',
        'activities.view' => 'Melihat aktivitas',
        'activities.manage' => 'Mengelola dan menerbitkan aktivitas',
    ],
];
