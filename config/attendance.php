<?php

return [
    'permissions' => [
        'operations.execution' => 'Memulai dan menyelesaikan sesi pelatihan',
        'attendance.area' => 'Menentukan area absensi sesi',
        'attendance.manage' => 'Memeriksa dan memfinalisasi absensi',
        'attendance.correct' => 'Mengoreksi absensi yang sudah difinalisasi',
    ],
    'trainer_permissions' => ['operations.execution', 'attendance.area', 'attendance.manage'],
    'challenge_seconds' => 120,
    'location_max_age_seconds' => 30,
    'max_accuracy_meters' => 50,
];
