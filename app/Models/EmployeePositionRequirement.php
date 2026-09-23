<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePositionRequirement extends Model
{
    public const CATEGORIES = [
        'education' => 'Pendidikan / Ijazah',
        'certificate' => 'Sertifikat',
        'experience' => 'Pengalaman Kerja',
        'other' => 'Dokumen Pendukung',
    ];

    public const STATUSES = [
        'missing' => 'Belum diunggah',
        'pending' => 'Menunggu verifikasi',
        'valid' => 'Valid',
        'rejected' => 'Ditolak',
        'expired' => 'Kedaluwarsa',
        'incomplete' => 'Masa berlaku belum diisi',
    ];

    protected $guarded = [];

    protected $attributes = [
        'is_required' => true,
        'has_expiry' => false,
        'requires_verification' => true,
        'version' => 1,
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'has_expiry' => 'boolean', 'requires_verification' => 'boolean', 'version' => 'integer'];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(EmployeePosition::class, 'employee_position_id');
    }

    public function documentStatus(?EmployeeDocument $document): string
    {
        if (! $document) {
            return 'missing';
        }
        if ($document->verification_status === 'rejected' && $document->requirement_version === $this->version) {
            return 'rejected';
        }
        if ($this->has_expiry && ! $document->expires_at) {
            return 'incomplete';
        }
        if ($document->expires_at && $document->expires_at->lt(today())) {
            return 'expired';
        }
        if ($this->requires_verification && ($document->verification_status !== 'approved' || $document->requirement_version !== $this->version)) {
            return 'pending';
        }

        return 'valid';
    }
}
