<?php

return [
    'seed' => [
        'name' => env('ADMIN_NAME', 'Administrator PT. Alpha Teknik Pratama'),
        'email' => env('ADMIN_EMAIL', 'admin@welding-school.test'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'permission_labels' => [
        ...(require __DIR__.'/practice.php')['permissions'],
        ...(require __DIR__.'/attendance.php')['permissions'],
        ...(require __DIR__.'/training_materials.php')['permissions'],
        'budgets.view' => 'Melihat RAB dan harga pelatihan',
        'budgets.manage' => 'Menyusun dan mengajukan revisi RAB',
        'budgets.approve' => 'Menyetujui RAB dan harga final',
        ...(require __DIR__.'/batch_operations.php')['permissions'],
        'admin.access' => 'Akses dashboard admin',
        'users.view' => 'Melihat pengguna',
        'users.manage' => 'Mengelola pengguna',
        'roles.view' => 'Melihat role',
        'roles.manage' => 'Mengelola role dan izin',
        'locations.view' => 'Melihat master lokasi',
        'locations.manage' => 'Mengelola master lokasi',
        'applications.view' => 'Melihat pendaftaran',
        'applications.approve' => 'Menyetujui atau menolak pendaftaran',
        'programs.view' => 'Melihat program pelatihan',
        'programs.manage' => 'Mengelola program pelatihan',
        'batches.view' => 'Melihat batch',
        'batches.manage' => 'Mengelola batch',
        'activities.view' => 'Melihat aktivitas',
        'activities.manage' => 'Mengelola dan menerbitkan aktivitas',
        'quality-documents.view' => 'Melihat dan membuka preview dokumen Quality',
        'quality-documents.manage' => 'Menambah, mengelola, dan mengunduh dokumen Quality',
        'quality-documents.structure.manage' => 'Mengelola standar serta bab dokumen Quality',
        'assets.view' => 'Melihat aset dan label aset',
        'assets.manage' => 'Mengelola data aset',
        'assets.inspect' => 'Melakukan inspeksi aset',
        'storage.view' => 'Melihat dashboard dan data Storage',
        'storage.items.manage' => 'Mengelola master consumable',
        'storage.transactions.manage' => 'Mencatat penerimaan dan pengeluaran barang',
        'storage.loans.manage' => 'Mencatat pinjaman aset ke luar area',
        'storage.stocktakes.manage' => 'Melakukan dan menyelesaikan stock opname',
        'storage.reports.view' => 'Melihat laporan Storage',
        'employees.view' => 'Melihat data karyawan',
        'employees.manage' => 'Mengelola data karyawan',
        'employees.verify' => 'Memverifikasi dokumen persyaratan karyawan',
    ],

    'permission_groups' => [
        'practice' => [
            'label' => 'Tugas dan logbook',
            'description' => 'Aktivitas per sesi, pemeriksaan hasil, dan jam praktik terverifikasi.',
            'permissions' => array_keys((require __DIR__.'/practice.php')['permissions']),
        ],
        'attendance' => [
            'label' => 'Pelaksanaan dan absensi',
            'description' => 'Pelaksanaan sesi, area selfie, pemeriksaan dan koreksi absensi.',
            'permissions' => array_keys((require __DIR__.'/attendance.php')['permissions']),
        ],
        'training-materials' => [
            'label' => 'Materi pelatihan',
            'description' => 'Library, revisi, susunan program, dan publikasi materi batch.',
            'permissions' => array_keys((require __DIR__.'/training_materials.php')['permissions']),
        ],
        'budgets' => [
            'label' => 'Anggaran / RAB',
            'description' => 'Anggaran satu atau beberapa batch dan penetapan harga final.',
            'permissions' => ['budgets.view', 'budgets.manage', 'budgets.approve'],
        ],
        'operations' => [
            'label' => 'Operasional Batch',
            'description' => 'Tim pelaksana, jadwal sesi, checklist kesiapan, dan status operasional.',
            'permissions' => array_keys((require __DIR__.'/batch_operations.php')['permissions']),
        ],
        'system' => [
            'label' => 'Akses internal',
            'description' => 'Izin dasar untuk masuk ke portal dan dashboard internal.',
            'permissions' => [
                'admin.access',
            ],
        ],
        'access' => [
            'label' => 'Pengguna & role',
            'description' => 'Pengelolaan akun staf, peserta, role, dan hak akses.',
            'permissions' => [
                'users.view',
                'users.manage',
                'roles.view',
                'roles.manage',
                'locations.view',
                'locations.manage',
            ],
        ],
        'employees' => [
            'label' => 'Kepegawaian & SDM',
            'description' => 'Pengelolaan data karyawan, biodata, riwayat pendidikan, dan berkas kepegawaian.',
            'permissions' => [
                'employees.view',
                'employees.manage',
                'employees.verify',
            ],
        ],
        'applications' => [
            'label' => 'Pendaftaran peserta',
            'description' => 'Peninjauan dan persetujuan pendaftaran pelatihan.',
            'permissions' => [
                'applications.view',
                'applications.approve',
            ],
        ],
        'training' => [
            'label' => 'Program & pelatihan',
            'description' => 'Pengelolaan program dan batch pelatihan.',
            'permissions' => [
                'programs.view',
                'programs.manage',
                'batches.view',
                'batches.manage',
            ],
        ],
        'content' => [
            'label' => 'Aktivitas & konten',
            'description' => 'Pengelolaan kabar dan aktivitas akademi.',
            'permissions' => [
                'activities.view',
                'activities.manage',
            ],
        ],
        'quality-documents' => [
            'label' => 'Quality Documents',
            'description' => 'Standar ISO, struktur bab, dokumen terkendali, dan histori revisi.',
            'permissions' => [
                'quality-documents.view',
                'quality-documents.manage',
                'quality-documents.structure.manage',
            ],
        ],
        'assets' => [
            'label' => 'Aset & inventaris',
            'description' => 'Data aset, label, serta proses inspeksi peralatan.',
            'permissions' => [
                'assets.view',
                'assets.manage',
                'assets.inspect',
            ],
        ],
        'storage' => [
            'label' => 'Storage & consumable',
            'description' => 'Stok consumable, transaksi, stock opname, dan pinjaman aset keluar area.',
            'permissions' => [
                'storage.view',
                'storage.items.manage',
                'storage.transactions.manage',
                'storage.loans.manage',
                'storage.stocktakes.manage',
                'storage.reports.view',
            ],
        ],
    ],
];
