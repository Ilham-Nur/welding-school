@props(['status'])

@php
    $labels = [
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'draft' => 'Draft',
        'open' => 'Dibuka',
        'closed' => 'Ditutup',
        'completed' => 'Selesai',
        'submitted' => 'Menunggu review',
        'under_review' => 'Dalam review',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'pending' => 'Menunggu',
        'valid' => 'Valid',
        'published' => 'Terbit',
        'archived' => 'Diarsipkan',
        'serviceable' => 'Layak pakai',
        'calibrated' => 'Terkalibrasi',
        'calibration_due' => 'Kalibrasi jatuh tempo',
        'out_of_calibration' => 'Tidak terkalibrasi',
        'maintenance' => 'Dalam perawatan',
        'out_of_service' => 'Tidak layak pakai',
        'under_calibration' => 'Dalam kalibrasi',
        'retired' => 'Tidak digunakan',
        'on_loan' => 'Dipinjamkan keluar',
        'posted' => 'Tercatat',
        'counting' => 'Sedang dihitung',
        'returned' => 'Dikembalikan',
    ];
    $tone = match ($status) {
        'active', 'open', 'approved', 'completed', 'valid', 'published', 'serviceable', 'calibrated' => 'success',
        'submitted', 'under_review', 'pending', 'calibration_due' => 'warning',
        'maintenance', 'under_calibration', 'on_loan', 'counting' => 'info',
        'posted', 'returned' => 'success',
        'inactive', 'closed', 'rejected', 'archived', 'out_of_calibration', 'out_of_service' => 'danger',
        default => 'neutral',
    };
@endphp

<span {{ $attributes->class(['admin-status', "admin-status--{$tone}"]) }}>
    {{ trim((string) $slot) !== '' ? $slot : ($labels[$status] ?? str_replace('_', ' ', ucfirst($status))) }}
</span>
