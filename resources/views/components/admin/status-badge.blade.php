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
    ];
    $tone = match ($status) {
        'active', 'open', 'approved', 'completed', 'valid' => 'success',
        'submitted', 'under_review', 'pending' => 'warning',
        'inactive', 'closed', 'rejected' => 'danger',
        default => 'neutral',
    };
@endphp

<span {{ $attributes->class(['admin-status', "admin-status--{$tone}"]) }}>
    {{ trim((string) $slot) !== '' ? $slot : ($labels[$status] ?? str_replace('_', ' ', ucfirst($status))) }}
</span>
